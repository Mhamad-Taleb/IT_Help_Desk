<?php

namespace App\Http\Controllers;

use App\Models\AiAssistantChat;
use App\Models\AiAssistantMessage;
use App\Services\AiSupportAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AiAssistantController extends Controller
{
    public function __construct(
        protected AiSupportAssistantService $assistantService
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $chatHistory = $user->aiAssistantChats()
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();
        $activeChat = $chatHistory->first();

        if ($activeChat) {
            $activeChat->load('messages');
        }

        return view('assistant.index', [
            'activeChat' => $activeChat,
            'chatHistory' => $chatHistory,
            'assistantReady' => $this->assistantService->isReady(),
            'user' => $user,
        ]);
    }

    public function show(Request $request, AiAssistantChat $chat): View
    {
        $this->ensureChatOwnership($request, $chat);

        $chatHistory = $request->user()->aiAssistantChats()
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('assistant.index', [
            'activeChat' => $chat->load('messages'),
            'chatHistory' => $chatHistory,
            'assistantReady' => $this->assistantService->isReady(),
            'user' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $chat = AiAssistantChat::query()->create([
            'user_id' => $request->user()->id,
            'title' => 'New Chat',
            'status' => 'active',
        ]);

        $this->seedWelcomeMessage($chat);

        $chat->load('messages')->loadCount('messages');

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'A new assistant chat is ready.',
                'chat' => $this->chatPayload($chat),
                'messages' => $chat->messages
                    ->map(fn (AiAssistantMessage $message): array => $this->messagePayload($message))
                    ->values()
                    ->all(),
            ]);
        }

        return redirect()
            ->route('assistant.show', $chat)
            ->with('status', 'A new assistant chat is ready.');
    }

    public function storeMessage(Request $request, AiAssistantChat $chat): RedirectResponse|JsonResponse
    {
        $this->ensureChatOwnership($request, $chat);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $message = trim($validated['message']);

        $userMessage = AiAssistantMessage::query()->create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $message,
        ]);

        $assistantMessage = AiAssistantMessage::query()->create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $this->assistantService->respond($chat->fresh()),
        ]);

        if ($chat->title === 'New Chat') {
            $chat->title = Str::limit($message, 48, '...');
        }

        $chat->last_message_at = now();
        $chat->save();

        $chat->refresh()->loadCount('messages');

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'Message sent to the AI assistant.',
                'chat' => $this->chatPayload($chat),
                'user_message' => $this->messagePayload($userMessage),
                'assistant_message' => $this->messagePayload($assistantMessage),
            ]);
        }

        return redirect()
            ->route('assistant.show', $chat)
            ->with('status', 'Message sent to the AI assistant.');
    }

    protected function ensureChatOwnership(Request $request, AiAssistantChat $chat): void
    {
        abort_unless($chat->user_id === $request->user()->id, 403);
    }

    protected function seedWelcomeMessage(AiAssistantChat $chat): void
    {
        AiAssistantMessage::query()->create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Welcome to the IDS AI Assistant. Ask me about laptops, printers, software, internet, email, passwords, VPN, or other internal IT help desk issues, and I will try to guide you step by step.',
        ]);

        $chat->last_message_at = now();
        $chat->save();
    }

    protected function chatPayload(AiAssistantChat $chat): array
    {
        return [
            'id' => $chat->id,
            'title' => $chat->title,
            'messages_count' => $chat->messages_count ?? $chat->messages()->count(),
            'last_message_at' => $chat->last_message_at?->format('d M, h:i A') ?? 'No messages yet',
            'show_url' => route('assistant.show', $chat),
        ];
    }

    protected function messagePayload(AiAssistantMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'time' => $message->created_at->format('h:i A'),
        ];
    }
}
