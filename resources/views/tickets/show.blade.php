<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-white to-slate-50"></div>
            <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-blue-300/20 blur-3xl"></div>

            <div class="relative px-6 py-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-600">
                            Ticket Detail
                        </p>

                        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950">
                            {{ $ticket->title }}
                        </h2>

                        <p class="mt-2 text-sm font-medium text-slate-500">
                            {{ $ticket->ticket_number }} • Created by {{ $ticket->creator->name }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('tickets.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Back to Tickets
                        </a>

                        @if ($canDelete)
                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
                                onsubmit="return confirm('Delete this ticket permanently?');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700">
                                    Delete Ticket
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 shadow-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <span class="rounded-full bg-blue-50 px-3.5 py-1.5 text-[0.7rem] font-black uppercase tracking-[0.16em] text-blue-700 ring-1 ring-blue-100">
                                {{ $ticket->ticket_number }}
                            </span>

                            <span class="inline-flex rounded-full px-3.5 py-1.5 text-[0.7rem] font-black ring-1 ring-black/5 {{ $ticket->priority->badgeClasses() }}">
                                {{ $ticket->priority->label() }}
                            </span>

                            <span class="inline-flex rounded-full px-3.5 py-1.5 text-[0.7rem] font-black ring-1 ring-black/5 {{ $ticket->status->badgeClasses() }}">
                                {{ $ticket->status->label() }}
                            </span>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.18em] text-slate-400">Category</p>
                                <p class="mt-2 truncate text-base font-black text-slate-950">
                                    {{ $ticket->category->name }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.18em] text-slate-400">Assigned To</p>
                                <p class="mt-2 truncate text-base font-black text-slate-950">
                                    {{ $ticket->assignee?->name ?? 'Unassigned' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.18em] text-slate-400">Requester</p>
                                <p class="mt-2 truncate text-base font-black text-slate-950">
                                    {{ $ticket->creator->name }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="text-[0.65rem] font-black uppercase tracking-[0.18em] text-slate-400">Created</p>
                                <p class="mt-2 text-base font-black text-slate-950">
                                    {{ $ticket->created_at->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'add-comment-modal')"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-800">
                            Add Comment
                        </button>

                        @if ($canUpdate)
                            <button type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'update-ticket-modal')"
                                class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-blue-700 shadow-sm transition hover:bg-blue-50">
                                Update Ticket
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid xl:grid-cols-[minmax(0,1fr)_21rem]">
                <main class="p-5 sm:p-6">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-slate-400">
                            Issue Description
                        </p>

                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm leading-6 text-slate-700 shadow-sm">
                            {{ $ticket->description }}
                        </div>
                    </div>

                    <div class="mt-7">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h3 class="text-xl font-black text-slate-950">
                                    Comments
                                </h3>

                                <p class="mt-1 text-sm font-medium text-slate-500">
                                    Follow-up updates between the requester and support team.
                                </p>
                            </div>

                            <span class="w-fit rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.15em] text-blue-700">
                                {{ $messages->count() }} Comments
                            </span>
                        </div>

                        <div class="mt-4 space-y-2.5">
                            @forelse ($messages as $message)
                                <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:shadow-md">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-sky-500 text-xs font-black text-white">
                                            {{ strtoupper(substr($message->user->name, 0, 1)) }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="text-[13px] font-bold text-slate-900">
                                                    {{ $message->user->name }}
                                                </p>

                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.12em] text-slate-600">
                                                    {{ $message->user->role->label() }}
                                                </span>
                                            </div>

                                            <p class="mt-1 text-[10px] uppercase tracking-wider text-slate-400">
                                                {{ $message->created_at->format('d M Y, h:i A') }}
                                            </p>

                                            <div x-data="{ expanded: false }" class="mt-2">
                                                <div
                                                    :class="expanded ? '' : 'max-h-12 overflow-hidden'"
                                                    class="rounded-lg bg-slate-50 px-3 py-2 text-[13px] leading-5 text-slate-700 ring-1 ring-slate-100 transition-all duration-300"
                                                >
                                                    {{ $message->body }}
                                                </div>

                                                @if (strlen($message->body) > 90)
                                                    <button
                                                        type="button"
                                                        x-on:click="expanded = !expanded"
                                                        class="mt-1 text-[11px] font-bold text-blue-600 transition hover:text-blue-800"
                                                    >
                                                        <span x-show="!expanded">Show More</span>
                                                        <span x-show="expanded">Show Less</span>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                                    <p class="text-sm font-black text-slate-900">
                                        No comments yet
                                    </p>

                                    <p class="mt-2 text-sm text-slate-500">
                                        Use the Add Comment button to post the first update.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </main>

                <aside class="border-t border-slate-200 bg-slate-50 p-5 xl:border-l xl:border-t-0">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-[0.7rem] font-black uppercase tracking-[0.22em] text-blue-700">
                            Activity Timeline
                        </p>

                        <div class="mt-5 space-y-5">
                            @foreach ($timelineEvents as $event)
                                <div class="relative pl-6">
                                    <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full bg-blue-600 ring-4 ring-blue-100"></span>

                                    <div class="border-l border-slate-200 pl-4">
                                        <p class="text-sm font-black text-slate-950">
                                            {{ $event['title'] }}
                                        </p>

                                        <p class="mt-1 text-[0.7rem] font-bold uppercase tracking-[0.14em] text-slate-400">
                                            {{ $event['occurred_at']->format('d M Y, h:i A') }}
                                        </p>

                                        <p class="mt-2 text-sm leading-6 text-slate-600">
                                            {{ $event['description'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </section>
    </div>

    <x-modal name="add-comment-modal" maxWidth="2xl" focusable>
        <div class="rounded-[1.7rem] bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-600">
                        Ticket Comment
                    </p>

                    <h3 class="mt-2 text-2xl font-black text-slate-950">
                        Add Comment
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Post a follow-up comment for this ticket.
                    </p>
                </div>

                <button type="button"
                    x-data
                    x-on:click="$dispatch('close-modal', 'add-comment-modal')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                    Close
                </button>
            </div>

            <form method="POST" action="{{ route('tickets.messages.store', $ticket) }}" class="mt-5 space-y-5">
                @csrf

                <div>
                    <label for="comment_body" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                        Comment
                    </label>

                    <textarea id="comment_body" name="body" rows="5"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500"
                        required>{{ old('body') }}</textarea>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        This comment will be attached to the current ticket.
                    </p>

                    <button type="submit"
                        class="rounded-xl bg-blue-700 px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-blue-800">
                        Post Comment
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @if ($canUpdate)
        <x-modal name="update-ticket-modal" maxWidth="2xl" focusable>
            <div class="rounded-[1.7rem] bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-blue-600">
                            Ticket Update
                        </p>

                        <h3 class="mt-2 text-2xl font-black text-slate-950">
                            Update Ticket
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            @if ($canManageWorkflow)
                                Edit issue details, assignment, and workflow status.
                            @else
                                You can update your submitted issue while it remains active.
                            @endif
                        </p>
                    </div>

                    <button type="button"
                        x-data
                        x-on:click="$dispatch('close-modal', 'update-ticket-modal')"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                        Close
                    </button>
                </div>

                <form method="POST" action="{{ route('tickets.update', $ticket) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="title" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                            Issue Title
                        </label>

                        <input id="title" name="title" type="text"
                            value="{{ old('title', $ticket->title) }}"
                            class="mt-2 block h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500"
                            required />
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                            Description
                        </label>

                        <textarea id="description" name="description" rows="5"
                            class="mt-2 block w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500"
                            required>{{ old('description', $ticket->description) }}</textarea>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="category_id" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                                Category
                            </label>

                            <select id="category_id" name="category_id"
                                class="mt-2 block h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500"
                                required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $ticket->category_id) === (string) $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="priority" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                                Priority
                            </label>

                            <select id="priority" name="priority"
                                class="mt-2 block h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500"
                                required>
                                @foreach ($priorities as $priority)
                                    <option value="{{ $priority->value }}" @selected(old('priority', $ticket->priority->value) === $priority->value)>
                                        {{ $priority->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($canManageWorkflow)
                            <div>
                                <label for="status" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                                    Status
                                </label>

                                <select id="status" name="status"
                                    class="mt-2 block h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500"
                                    required>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', $ticket->status->value) === $status->value)>
                                            {{ $status->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="assigned_to" class="block text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                                    Assign To
                                </label>

                                <select id="assigned_to" name="assigned_to"
                                    class="mt-2 block h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:bg-white focus:ring-blue-500">
                                    <option value="">Unassigned</option>

                                    @foreach ($assignees as $assignee)
                                        <option value="{{ $assignee->id }}" @selected((string) old('assigned_to', $ticket->assigned_to) === (string) $assignee->id)>
                                            {{ $assignee->name }} - {{ $assignee->role->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="rounded-xl bg-blue-700 px-5 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:bg-blue-800">
                            Save Ticket Changes
                        </button>
                    </div>
                </form>
            </div>
        </x-modal>
    @endif
</x-app-layout>