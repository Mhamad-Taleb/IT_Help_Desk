<x-app-layout>
    <x-slot name="header">
    <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 shadow-sm">
        <div class="flex items-center justify-between gap-4">

            <div class="min-w-0">
                <p class="text-[0.55rem] font-black uppercase tracking-[0.2em] text-emerald-600">
                    Ticket Chat
                </p>

                <h2 class="truncate text-base font-black text-slate-950">
                    {{ $chatPartnerLabel }}
                </h2>

                <p class="truncate text-[11px] font-medium text-slate-500">
                    {{ $ticket->ticket_number }}
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black ring-1 ring-black/5 {{ $ticket->priority->badgeClasses() }}">
                    {{ $ticket->priority->label() }}
                </span>

                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black ring-1 ring-black/5 {{ $ticket->status->badgeClasses() }}">
                    {{ $ticket->status->label() }}
                </span>

                <a
                    href="{{ route('tickets.show', $ticket) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-black text-slate-700 hover:bg-slate-50"
                >
                    ← Back
                </a>
            </div>

        </div>
    </div>
</x-slot>

    @php
        $chatMessagesPayload = $messages->map(fn ($message) => [
            'id' => $message->id,
            'ticket_id' => $message->ticket_id,
            'user_id' => $message->user_id,
            'user_name' => $message->user->name,
            'role_label' => $message->user->role->label(),
            'body' => $message->body,
            'created_at' => $message->created_at->format('d M Y, h:i A'),
            'time' => $message->created_at->format('h:i A'),
            'initial' => strtoupper(substr($message->user->name, 0, 1)),
        ])->values();
    @endphp

    <div
        class="overflow-hidden"
        x-data="ticketChatPage({
            ticketId: {{ $ticket->id }},
            currentUserId: {{ $user->id }},
            chatUrl: '{{ route('tickets.chat.messages.store', $ticket) }}',
            csrfToken: '{{ csrf_token() }}',
            messages: @js($chatMessagesPayload),
            channelName: 'ticket-chat.{{ $ticket->id }}',
            isReadOnly: @js($ticket->status->isFinal()),
        })"
        x-init="init(); resizeComposer()"
    >
        @if (session('status'))
            <x-auto-dismiss-alert :message="session('status')" />
        @endif

        @if ($errors->any())
            <x-auto-dismiss-alert type="error" :message="$errors->first()" />
        @endif

        <section class="flex h-[calc(100dvh-9rem)] min-h-[28rem] flex-col overflow-hidden rounded-[1.5rem] border border-slate-200 bg-white shadow-sm">

            <div class="shrink-0 border-b border-slate-200 bg-white px-5 py-3 sm:px-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-600">
                            Conversation
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-500">
                            Chat about:
                            <span class="font-black text-slate-900">{{ $ticket->title }}</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600">
                            Requester: {{ $ticket->creator->name }}
                        </span>

                        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700">
                            <span x-text="messages.length"></span> messages
                        </span>
                    </div>
                </div>
            </div>

            <div
                x-ref="chatFeed"
                class="flex-1 space-y-4 overflow-y-auto bg-gradient-to-b from-slate-50 via-white to-white p-5 sm:p-6"
            >
                <template x-if="messages.length === 0">
                    <div class="flex h-full items-center justify-center">
                        <div class="w-full max-w-md rounded-[1.5rem] border border-dashed border-slate-300 bg-white px-6 py-10 text-center shadow-sm">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl">
                                💬
                            </div>

                            <p class="mt-4 text-lg font-black text-slate-950">
                                No messages yet
                            </p>

                            <p class="mt-2 text-sm text-slate-500">
                                Write your first message below.
                            </p>
                        </div>
                    </div>
                </template>

                <template x-for="message in messages" :key="message.id">
                    <div
                        class="flex"
                        :class="message.user_id === currentUserId ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="flex max-w-[48rem] gap-3"
                            :class="message.user_id === currentUserId ? 'flex-row-reverse' : ''"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-sm font-black text-white shadow-sm"
                                :class="message.user_id === currentUserId
                                    ? 'bg-gradient-to-br from-cyan-600 to-blue-600'
                                    : 'bg-gradient-to-br from-emerald-600 to-teal-500'"
                            >
                                <span x-text="message.initial"></span>
                            </div>

                            <div
                                class="min-w-0"
                                :class="message.user_id === currentUserId ? 'text-right' : 'text-left'"
                            >
                                <div
                                    class="mb-1 flex flex-wrap items-center gap-2"
                                    :class="message.user_id === currentUserId ? 'justify-end' : 'justify-start'"
                                >
                                    <span
                                        class="text-xs font-black uppercase tracking-[0.14em]"
                                        :class="message.user_id === currentUserId ? 'text-cyan-600' : 'text-emerald-600'"
                                        x-text="message.user_id === currentUserId ? 'You' : message.user_name"
                                    ></span>

                                    <span
                                        x-show="message.role_label"
                                        x-cloak
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.58rem] font-black uppercase tracking-[0.12em] text-slate-500"
                                        x-text="message.role_label"
                                    ></span>

                                    <span
                                        class="text-[0.65rem] font-bold uppercase tracking-[0.12em] text-slate-400"
                                        x-text="message.time"
                                    ></span>
                                </div>

                                <div
                                    class="rounded-[1.35rem] px-4 py-3 text-sm leading-7 shadow-sm"
                                    :class="message.user_id === currentUserId
                                        ? 'rounded-tr-md bg-gradient-to-br from-cyan-600 to-blue-600 text-white'
                                        : 'rounded-tl-md border border-slate-200 bg-white text-slate-700'"
                                >
                                    <span x-text="message.body"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div class="shrink-0 border-t border-slate-200 bg-white p-3 sm:p-4">
                @if ($ticket->status->isFinal())
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-bold text-amber-700">
                        This ticket is {{ strtolower($ticket->status->label()) }}, so the chat is now read-only.
                    </div>
                @else
                    <form @submit.prevent="sendMessage()">
                        <div
                            x-show="chatError"
                            x-cloak
                            class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700"
                            x-text="chatError"
                        ></div>

                        <div class="flex items-center gap-3 rounded-[1.4rem] border border-slate-300 bg-slate-50 px-4 py-3 transition focus-within:border-emerald-500 focus-within:bg-white focus-within:ring-4 focus-within:ring-emerald-500/10">
                            <textarea
                                id="chat_message"
                                x-ref="chatInput"
                                x-model="body"
                                @keydown="submitOnEnter($event)"
                                @input="resizeComposer()"
                                rows="1"
                                class="block max-h-20 min-h-[2.5rem] flex-1 resize-none border-0 bg-transparent px-0 py-2 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-0 focus:ring-0"
                                placeholder="Write your message..."
                                required
                            ></textarea>

                            <button
                                type="submit"
                                :disabled="chatSubmitting || !body.trim()"
                                class="inline-flex h-12 shrink-0 items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 text-xs font-black uppercase tracking-[0.16em] text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span x-show="!chatSubmitting">Send</span>
                                <span x-show="chatSubmitting" x-cloak>Sending...</span>
                                <span>➜</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

    <style>
        html,
        body {
            overflow: hidden;
        }
    </style>
</x-app-layout>