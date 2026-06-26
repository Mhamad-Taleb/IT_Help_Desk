<?php

namespace App\Http\Controllers;

use App\Events\TicketChatMessageSent;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketChatMessage;
use App\Models\TicketMessage;
use App\Models\User;
use App\Support\AuditLogger;
use App\Services\AiTicketClassifierService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    protected const ALLOWED_ATTACHMENT_EXTENSIONS = [
        'pdf', 'txt', 'docx', 'jpg', 'jpeg', 'png',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        $tickets = Ticket::query()
            ->with(['category', 'creator', 'assignee'])
            ->visibleTo($user)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));

                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categoryOptions = Category::query()->active()->ordered()->get();

        $scopeQuery = Ticket::query()->visibleTo($user);

        return view('tickets.index', [
            'categories' => $categoryOptions,
            'priorities' => TicketPriority::cases(),
            'statuses' => TicketStatus::cases(),
            'tickets' => $tickets,
            'totalTickets' => (clone $scopeQuery)->count(),
            'openTickets' => (clone $scopeQuery)->where('status', TicketStatus::Open->value)->count(),
            'resolvedTickets' => (clone $scopeQuery)->where('status', TicketStatus::Resolved->value)->count(),
            'assignedTickets' => (clone $scopeQuery)->whereNotNull('assigned_to')->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('tickets.create', [
            'allowedAttachmentText' => $this->allowedAttachmentText(),
        ]);
    }

    public function store(Request $request, AiTicketClassifierService $classifier): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate(
            array_merge($this->storeRulesFor($user), $this->attachmentRules()),
            $this->attachmentMessages(),
        );
        $classification = $classifier->classify(
            $validated['title'],
            $validated['description'],
        );

        $ticket = Ticket::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $classification['category_id'],
            'priority' => $classification['priority'],
            'status' => TicketStatus::Open,
            'created_by' => $user->id,
            'assigned_to' => null,
        ]);

        AuditLogger::record(
            'ticket.created',
            "{$user->name} created ticket {$ticket->ticket_number}.",
            actor: $user,
            subject: $ticket,
            ticket: $ticket,
            properties: [
                'classification_source' => $classification['source'] ?? 'unknown',
                'classification_reason' => $classification['reason'] ?? null,
                'detected_category' => $classification['category_name'] ?? null,
                'detected_priority' => $classification['priority'] ?? null,
            ],
        );

        if ($request->hasFile('attachments')) {
            $this->persistAttachments($request->file('attachments'), $ticket, $user);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket created successfully.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);

        $ticket->load(['attachments.user', 'category', 'creator', 'assignee', 'messages.user']);
        $visibleMessages = $this->visibleMessagesFor($ticket, $user);

        return view('tickets.show', [
            'allowedAttachmentText' => $this->allowedAttachmentText(),
            'attachments' => $ticket->attachments,
            'assignees' => $this->assignableUsers(),
            'canUploadAttachments' => ! $ticket->status->isFinal(),
            'chatAvailable' => $this->chatAvailable($ticket),
            'chatButtonLabel' => $this->chatButtonLabel($user, $ticket),
            'canDelete' => $this->canDelete($user, $ticket),
            'canManageWorkflow' => $this->canManageWorkflow($user),
            'canUpdate' => $this->canUpdate($user, $ticket),
            'categories' => Category::query()->active()->ordered()->get(),
            'messages' => $visibleMessages,
            'priorities' => TicketPriority::cases(),
            'statuses' => TicketStatus::cases(),
            'ticket' => $ticket,
            'timelineEvents' => $this->buildTimelineEvents($ticket, $visibleMessages, $ticket->attachments),
            'user' => $user,
        ]);
    }

    public function chat(Request $request, Ticket $ticket): View
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);
        abort_unless($this->chatAvailable($ticket), 404);

        $ticket->load(['category', 'creator', 'assignee', 'chatMessages.user']);
        $messages = $ticket->chatMessages->sortBy('created_at')->values();

        return view('tickets.chat', [
            'chatPartnerLabel' => $this->chatButtonLabel($user, $ticket),
            'messages' => $messages,
            'ticket' => $ticket,
            'user' => $user,
        ]);
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCanUpdate($user, $ticket);
        $originalStatus = $ticket->status;
        $originalAssigneeId = $ticket->assigned_to;

        $validated = $request->validate($this->rulesFor($user, $ticket));

        $ticket->fill([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'priority' => $validated['priority'],
        ]);

        if ($this->canManageWorkflow($user)) {
            $ticket->status = $validated['status'];
            $ticket->assigned_to = $validated['assigned_to'] ?? null;

            $status = TicketStatus::from($validated['status']);
            $ticket->resolved_at = in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)
                ? ($ticket->resolved_at ?? now())
                : null;
            $ticket->closed_at = $status === TicketStatus::Closed ? now() : null;
        }

        $ticket->save();
        $deletedCommentsCount = 0;

        if ($ticket->status->isFinal()) {
            $deletedCommentsCount = $ticket->messages()->count();
            $ticket->messages()->delete();
        }

        $description = "{$user->name} updated ticket {$ticket->ticket_number}.";

        if ($originalStatus !== $ticket->status) {
            $description = "{$user->name} changed ticket {$ticket->ticket_number} status from {$originalStatus->label()} to {$ticket->status->label()}.";
        } elseif ($originalAssigneeId !== $ticket->assigned_to) {
            $description = "{$user->name} updated the assignment for ticket {$ticket->ticket_number}.";
        }

        if ($deletedCommentsCount > 0 && $ticket->status->isFinal()) {
            $description .= " {$deletedCommentsCount} ticket comment(s) were cleared after final closure.";
        }

        AuditLogger::record(
            'ticket.updated',
            $description,
            actor: $user,
            subject: $ticket,
            ticket: $ticket,
            properties: [
                'from_status' => $originalStatus->value,
                'to_status' => $ticket->status->value,
                'deleted_comments_count' => $deletedCommentsCount,
            ],
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket updated successfully.');
    }

    public function destroy(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->ensureCanDelete($request->user(), $ticket);

        AuditLogger::record(
            'ticket.deleted',
            "{$request->user()->name} deleted ticket {$ticket->ticket_number}.",
            actor: $request->user(),
            subject: $ticket,
            ticket: $ticket,
            properties: ['ticket_number' => $ticket->ticket_number],
        );

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with('status', 'Ticket deleted successfully.');
    }

    public function storeMessage(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);

        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:4000'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $validated['body'],
            'is_internal' => false,
        ])->load('user');

        AuditLogger::record(
            'ticket.comment_added',
            "{$user->name} added a comment on ticket {$ticket->ticket_number}.",
            actor: $user,
            subject: $ticket,
            ticket: $ticket,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Comment added successfully.',
                'comment' => $this->formatMessagePayload($message),
            ]);
        }

        if ($request->string('redirect_to')->toString() === 'chat') {
            return redirect()
                ->route('tickets.chat', $ticket)
                ->with('status', 'Message sent successfully.');
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Comment added successfully.');
    }

    public function storeChatMessage(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);
        abort_unless($this->chatAvailable($ticket), 404);

        if ($ticket->status->isFinal()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Chat is read-only after the ticket has been resolved or closed.',
                ], 422);
            }

            return back()->withErrors([
                'body' => 'Chat is read-only after the ticket has been resolved or closed.',
            ]);
        }

        $validator = Validator::make($request->all(), [
            'body' => ['required', 'string', 'max:4000'],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $message = TicketChatMessage::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $validator->validated()['body'],
        ])->load('user');

        AuditLogger::record(
            'ticket.chat_message_sent',
            "{$user->name} sent a chat message on ticket {$ticket->ticket_number}.",
            actor: $user,
            subject: $message,
            ticket: $ticket,
            targetUser: $this->chatTargetUser($ticket, $user),
            properties: [
                'ticket_number' => $ticket->ticket_number,
                'chat_message_id' => $message->id,
            ],
        );

        broadcast(new TicketChatMessageSent($message))->toOthers();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Chat message sent successfully.',
                'chat_message' => $this->formatChatMessagePayload($message),
            ]);
        }

        return redirect()
            ->route('tickets.chat', $ticket)
            ->with('status', 'Chat message sent successfully.');
    }

    public function storeAttachment(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->ensureCanView($user, $ticket);

        if ($ticket->status->isFinal()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Files cannot be uploaded after the ticket has been resolved or closed.',
                ], 422);
            }

            return back()->withErrors([
                'attachments' => 'Files cannot be uploaded after the ticket has been resolved or closed.',
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            $this->attachmentRules(required: true),
            $this->attachmentMessages(),
        );

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $attachments = $this->persistAttachments($validated['attachments'], $ticket, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'File uploaded successfully.',
                'attachments' => $attachments->map(fn (TicketAttachment $attachment) => $this->formatAttachmentPayload($attachment))->values(),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'File uploaded successfully.');
    }

    public function downloadAttachment(Request $request, TicketAttachment $attachment)
    {
        $ticket = $attachment->ticket()->with(['creator'])->firstOrFail();
        $this->ensureCanView($request->user(), $ticket);

        AuditLogger::record(
            'ticket.attachment_downloaded',
            "{$request->user()->name} downloaded {$attachment->original_name} from ticket {$ticket->ticket_number}.",
            actor: $request->user(),
            subject: $attachment,
            ticket: $ticket,
        );

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name);
    }

    public function openAttachment(Request $request, TicketAttachment $attachment)
    {
        $ticket = $attachment->ticket()->with(['creator'])->firstOrFail();
        $this->ensureCanView($request->user(), $ticket);

        AuditLogger::record(
            'ticket.attachment_opened',
            "{$request->user()->name} opened {$attachment->original_name} from ticket {$ticket->ticket_number}.",
            actor: $request->user(),
            subject: $attachment,
            ticket: $ticket,
        );

        return response()->file(
            Storage::disk('local')->path($attachment->storage_path),
            [
                'Content-Type' => $attachment->mime_type,
                'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
            ]
        );
    }

    protected function canManageWorkflow(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::Admin,
            UserRole::Manager,
            UserRole::SupportAgent,
        ]);
    }

    protected function canUpdate(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole([UserRole::Admin, UserRole::Manager])) {
            return true;
        }

        if ($user->hasRole(UserRole::SupportAgent)) {
            return $ticket->assigned_to === $user->id || $ticket->assigned_to === null;
        }

        return $ticket->created_by === $user->id && ! $ticket->status->isFinal();
    }

    protected function canDelete(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        return $user->hasRole(UserRole::Employee)
            && $ticket->created_by === $user->id
            && $ticket->status === TicketStatus::Open;
    }

    protected function chatAvailable(Ticket $ticket): bool
    {
        return $ticket->assigned_to !== null;
    }

    protected function chatButtonLabel(User $user, Ticket $ticket): string
    {
        if (! $this->chatAvailable($ticket)) {
            return 'Awaiting Agent';
        }

        if ($user->hasRole(UserRole::Employee)) {
            return 'Chat with '.$ticket->assignee?->name;
        }

        if ($user->hasRole(UserRole::SupportAgent) && $ticket->assigned_to === $user->id) {
            return 'Chat with '.$ticket->creator->name;
        }

        return 'Open Ticket Chat';
    }

    protected function chatTargetUser(Ticket $ticket, User $sender): ?User
    {
        if ($sender->id === $ticket->creator?->id) {
            return $ticket->assignee;
        }

        if ($sender->id === $ticket->assignee?->id) {
            return $ticket->creator;
        }

        return $ticket->creator;
    }

    protected function ensureCanView(User $user, Ticket $ticket): void
    {
        abort_unless(
            Ticket::query()
                ->visibleTo($user)
                ->whereKey($ticket->id)
                ->exists(),
            403,
        );
    }

    protected function ensureCanUpdate(User $user, Ticket $ticket): void
    {
        abort_unless($this->canUpdate($user, $ticket), 403);
    }

    protected function ensureCanDelete(User $user, Ticket $ticket): void
    {
        abort_unless($this->canDelete($user, $ticket), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rulesFor(User $user, ?Ticket $ticket = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ];

        if ($this->canManageWorkflow($user)) {
            $rules['status'] = ['required', Rule::enum(TicketStatus::class)];
            $rules['assigned_to'] = [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->whereIn('role', [
                        UserRole::Admin->value,
                        UserRole::Manager->value,
                        UserRole::SupportAgent->value,
                    ]);
                }),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    protected function storeRulesFor(User $user): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function attachmentRules(bool $required = false): array
    {
        return [
            'attachments' => [$required ? 'required' : 'nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:'.implode(',', self::ALLOWED_ATTACHMENT_EXTENSIONS),
                'max:5120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function attachmentMessages(): array
    {
        return [
            'attachments.required' => 'Please choose at least one file to upload.',
            'attachments.max' => 'You can upload up to 5 files at a time.',
            'attachments.*.mimes' => 'Only PDF, TXT, DOCX, JPG, JPEG, and PNG files are allowed.',
            'attachments.*.max' => 'Each file must be 5 MB or smaller.',
        ];
    }

    protected function assignableUsers()
    {
        return User::query()
            ->supportAssignable()
            ->orderBy('name')
            ->get();
    }

    protected function visibleMessagesFor(Ticket $ticket, User $user)
    {
        return $ticket->messages->values();
    }

    protected function buildTimelineEvents(Ticket $ticket, $messages, $attachments)
    {
        $events = collect([
            [
                'title' => 'Ticket created',
                'description' => 'Request opened by '.$ticket->creator->name.'.',
                'occurred_at' => $ticket->created_at,
                'tone' => 'bg-cyan-400',
                'type' => 'system',
            ],
        ]);

        foreach ($messages->sortBy('created_at') as $message) {
            $events->push([
                'title' => 'Comment added',
                'description' => $message->user->name.' added a ticket comment.',
                'occurred_at' => $message->created_at,
                'tone' => 'bg-blue-400',
                'type' => 'comment',
            ]);
        }

        foreach ($attachments->sortBy('created_at') as $attachment) {
            $events->push([
                'title' => 'File uploaded',
                'description' => $attachment->user->name.' uploaded '.$attachment->original_name.'.',
                'occurred_at' => $attachment->created_at,
                'tone' => 'bg-violet-400',
                'type' => 'attachment',
            ]);
        }

        if ($ticket->resolved_at) {
            $events->push([
                'title' => 'Ticket resolved',
                'description' => 'The issue was marked as resolved.',
                'occurred_at' => $ticket->resolved_at,
                'tone' => 'bg-emerald-400',
                'type' => 'system',
            ]);
        }

        if ($ticket->closed_at) {
            $events->push([
                'title' => 'Ticket closed',
                'description' => 'The workflow was completed and closed.',
                'occurred_at' => $ticket->closed_at,
                'tone' => 'bg-slate-300',
                'type' => 'system',
            ]);
        }

        return $events
            ->sortBy('occurred_at')
            ->values();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    protected function persistAttachments(array $files, Ticket $ticket, User $user)
    {
        $storedAttachments = collect();

        foreach ($files as $file) {
            $path = $file->store("ticket-attachments/{$ticket->id}", 'local');

            $attachment = TicketAttachment::query()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
            ]);

            AuditLogger::record(
                'ticket.attachment_uploaded',
                "{$user->name} uploaded {$attachment->original_name} to ticket {$ticket->ticket_number}.",
                actor: $user,
                subject: $attachment,
                ticket: $ticket,
                properties: [
                    'original_name' => $attachment->original_name,
                    'file_size' => $attachment->file_size,
                ],
            );

            $storedAttachments->push($attachment->loadMissing('user'));
        }

        return $storedAttachments;
    }

    protected function allowedAttachmentText(): string
    {
        return 'Allowed files: PDF, TXT, DOCX, JPG, JPEG, and PNG.';
    }

    protected function formatAttachmentPayload(TicketAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'extension' => strtoupper($attachment->extension),
            'uploaded_at' => $attachment->created_at->format('d M Y, h:i A'),
            'user_name' => $attachment->user?->name ?? 'Unknown',
            'human_size' => $attachment->humanReadableSize(),
            'download_url' => route('tickets.attachments.download', $attachment),
            'open_url' => route('tickets.attachments.open', $attachment),
        ];
    }

    protected function formatMessagePayload(TicketMessage $message): array
    {
        return [
            'id' => $message->id,
            'user_name' => $message->user->name,
            'role_label' => $message->user->role->label(),
            'body' => $message->body,
            'created_at' => $message->created_at->format('d M Y, h:i A'),
            'initial' => strtoupper(substr($message->user->name, 0, 1)),
        ];
    }

    protected function formatChatMessagePayload(TicketChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'ticket_id' => $message->ticket_id,
            'user_id' => $message->user_id,
            'user_name' => $message->user->name,
            'role_label' => $message->user->role->label(),
            'body' => $message->body,
            'created_at' => $message->created_at->format('d M Y, h:i A'),
            'time' => $message->created_at->format('h:i A'),
            'initial' => strtoupper(substr($message->user->name, 0, 1)),
        ];
    }
}
