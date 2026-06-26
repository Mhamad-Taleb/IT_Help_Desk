<x-app-layout>
    <x-slot name="header">
        <div class="ids-assistant-header">
            <div class="ids-assistant-header__brand">
                <div class="ids-assistant-header__logo">AI</div>

                <div>
                    
                    <h3 class="ids-assistant-header__title">AI Support Assistant</h3>
                    <p class="ids-assistant-header__sub">Employee Self-Service Chat</p>
                </div>
            </div>

            <span @class([
                'ids-assistant-status',
                'ids-assistant-status--online' => $assistantReady,
                'ids-assistant-status--limited' => ! $assistantReady,
            ])>
                <span></span>
                {{ $assistantReady ? 'AI Online' : 'Limited Mode' }}
            </span>
        </div>
    </x-slot>

    @php
        $activeChatPayload = $activeChat
            ? [
                'id' => $activeChat->id,
                'title' => $activeChat->title,
                'messages_count' => $activeChat->messages->count(),
                'show_url' => route('assistant.show', $activeChat),
            ]
            : null;

        $chatHistoryPayload = $chatHistory->map(fn ($chat) => [
            'id' => $chat->id,
            'title' => $chat->title,
            'messages_count' => $chat->messages_count,
            'last_message_at' => $chat->last_message_at?->format('d M, h:i A') ?? 'No messages yet',
            'show_url' => route('assistant.show', $chat),
        ])->values();

        $messagesPayload = $activeChat
            ? $activeChat->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'time' => $message->created_at->format('h:i A'),
            ])->values()
            : collect();
    @endphp

    <style>
        [x-cloak] {
            display: none !important;
        }

        .ids-assistant-eyebrow {
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .26em;
            text-transform: uppercase;
            color: #0f7b92;
        }

        .ids-assistant-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .78rem 1rem;
            border: 1px solid #dbe5ef;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(240, 249, 255, .96)),
                radial-gradient(circle at top right, rgba(45, 212, 191, .14), transparent 38%);
            border-radius: 1.25rem;
            box-shadow: 0 14px 36px rgba(15, 23, 42, .07);
        }

        .ids-assistant-header__brand {
            display: flex;
            align-items: center;
            gap: .8rem;
            min-width: 0;
        }

        .ids-assistant-header__logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.55rem;
            height: 2.55rem;
            flex-shrink: 0;
            border-radius: .85rem;
            background: linear-gradient(135deg, #0f172a, #0f766e);
            color: #fff;
            font-size: .82rem;
            font-weight: 900;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .14);
        }

        .ids-assistant-header__title {
            margin: .1rem 0 0;
            color: #0f172a;
            font-size: clamp(1.05rem, 1.9vw, 1.55rem);
            font-weight: 900;
            letter-spacing: -.03em;
        }

        .ids-assistant-header__sub {
            margin-top: .12rem;
            color: #64748b;
            font-size: .78rem;
            font-weight: 600;
        }

        .ids-assistant-status {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .58rem .88rem;
            border-radius: 999px;
            font-size: .74rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ids-assistant-status span {
            width: .55rem;
            height: .55rem;
            border-radius: 50%;
            background: currentColor;
        }

        .ids-assistant-status--online {
            border: 1px solid #b9f2df;
            background: #ecfdf5;
            color: #047857;
        }

        .ids-assistant-status--limited {
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        .ids-assistant-shell {
            position: relative;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 10.2rem);
            min-height: 34rem;
            overflow: hidden;
            border: 1px solid #dbe5ef;
            border-radius: 1.75rem;
            background:
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .09);
        }

        .ids-assistant-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .8rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, .92);
            backdrop-filter: blur(18px);
        }

        .ids-assistant-toolbar__title {
            margin: 0;
            color: #0f172a;
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: -.03em;
        }

        .ids-assistant-toolbar__sub {
            margin-top: .15rem;
            color: #64748b;
            font-size: .74rem;
        }

        .ids-assistant-toolbar__actions {
            display: flex;
            align-items: center;
            gap: .65rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .ids-assistant-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .5rem .82rem;
            border-radius: 999px;
            border: 1px solid #dbe5ef;
            background: #fff;
            color: #475569;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .ids-assistant-pill--soft {
            background: linear-gradient(135deg, #ecfeff, #f0fdf4);
            color: #0f7b92;
        }

        .ids-assistant-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .66rem .9rem;
            border: 1px solid #dbe5ef;
            border-radius: 1rem;
            background: #fff;
            color: #0f172a;
            font-size: .78rem;
            font-weight: 800;
            cursor: pointer;
            transition: .16s ease;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .ids-assistant-btn:hover:not(:disabled) {
            transform: translateY(-1px);
            border-color: #99f6e4;
            box-shadow: 0 12px 28px rgba(15, 23, 42, .08);
        }

        .ids-assistant-btn:disabled {
            opacity: .55;
            cursor: not-allowed;
            box-shadow: none;
        }

        .ids-assistant-btn--primary {
            border-color: #0f7b92;
            background: linear-gradient(135deg, #0f7b92, #14b8a6);
            color: #fff;
        }

        .ids-assistant-btn--primary:hover:not(:disabled) {
            border-color: #0f7b92;
        }

        .ids-assistant-feed {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1rem .85rem;
            background:
                radial-gradient(circle at top right, rgba(34, 211, 238, .08), transparent 28%),
                linear-gradient(180deg, #f8fbff 0%, #f5f9ff 100%);
        }

        .ids-assistant-feed__inner {
            display: flex;
            flex-direction: column;
            min-height: 100%;
            max-width: 58rem;
            margin: 0 auto;
        }

        .ids-assistant-feed__messages {
            width: 100%;
        }

        .ids-assistant-message {
            display: flex;
            gap: .85rem;
            align-items: flex-start;
        }

        .ids-assistant-message + .ids-assistant-message {
            margin-top: 1.1rem;
        }

        .ids-assistant-message--user {
            justify-content: flex-end;
        }

        .ids-assistant-message--assistant {
            justify-content: flex-start;
        }

        .ids-assistant-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.55rem;
            height: 2.55rem;
            flex-shrink: 0;
            border-radius: .95rem;
            font-size: .82rem;
            font-weight: 900;
        }

        .ids-assistant-avatar--assistant {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            box-shadow: 0 14px 30px rgba(15, 23, 42, .16);
        }

        .ids-assistant-avatar--user {
            background: linear-gradient(135deg, #14b8a6, #0f7b92);
            color: #fff;
            box-shadow: 0 14px 30px rgba(20, 184, 166, .16);
        }

        .ids-assistant-avatar svg {
            width: 1.1rem;
            height: 1.1rem;
        }

        .ids-assistant-bubble-wrap {
            max-width: min(100%, 44rem);
        }

        .ids-assistant-bubble {
            padding: 1rem 1.1rem;
            border-radius: 1.35rem;
            font-size: .96rem;
            line-height: 1.8;
        }

        .ids-assistant-bubble--assistant {
            border: 1px solid #dbe5ef;
            border-bottom-left-radius: .45rem;
            background: #fff;
            color: #1e293b;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        }

        .ids-assistant-bubble--user {
            border-bottom-right-radius: .45rem;
            background: linear-gradient(135deg, #0f7b92, #14b8a6);
            color: #fff;
            box-shadow: 0 12px 28px rgba(20, 184, 166, .22);
        }

        .ids-assistant-bubble__meta {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: .35rem;
        }

        .ids-assistant-bubble__role {
            font-size: .64rem;
            font-weight: 900;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .ids-assistant-bubble--assistant .ids-assistant-bubble__role {
            color: #0f7b92;
        }

        .ids-assistant-bubble--user .ids-assistant-bubble__role {
            color: rgba(255, 255, 255, .76);
        }

        .ids-assistant-bubble__time {
            font-size: .7rem;
            font-weight: 700;
            opacity: .65;
        }

        .ids-assistant-bubble__text {
            margin: 0;
            white-space: pre-line;
        }

        .ids-assistant-bubble--pending .ids-assistant-bubble__text::after {
            content: "  ● ● ●";
            color: #14b8a6;
            animation: ids-assistant-pulse 1.35s infinite;
        }

        @keyframes ids-assistant-pulse {
            0%, 100% {
                opacity: .35;
            }

            50% {
                opacity: 1;
            }
        }

        .ids-assistant-actions {
            display: flex;
            gap: .45rem;
            margin-top: .55rem;
        }

        .ids-assistant-actions__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 .7rem;
            border: 1px solid #dbe5ef;
            border-radius: 999px;
            background: rgba(255, 255, 255, .9);
            color: #64748b;
            font-size: .74rem;
            font-weight: 700;
            cursor: pointer;
            transition: .16s ease;
        }

        .ids-assistant-actions__btn:hover {
            border-color: #99f6e4;
            color: #0f7b92;
        }

        .ids-assistant-empty {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 1rem 0;
        }

        .ids-assistant-empty__card {
            width: 100%;
            max-width: 52rem;
            padding: 2rem;
            border: 1px solid #dbe5ef;
            border-radius: 2rem;
            background: rgba(255, 255, 255, .92);
            box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
        }

        .ids-assistant-empty__badge {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .52rem .95rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #ecfeff, #f0fdf4);
            color: #0f7b92;
            font-size: .74rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .ids-assistant-empty__title {
            margin-top: 1rem;
            color: #0f172a;
            font-size: clamp(1.7rem, 3.4vw, 2.6rem);
            font-weight: 900;
            letter-spacing: -.05em;
        }

        .ids-assistant-empty__sub {
            margin-top: .7rem;
            max-width: 40rem;
            color: #64748b;
            font-size: .94rem;
            line-height: 1.8;
        }

        .ids-assistant-prompts {
            display: grid;
            gap: .85rem;
            margin-top: 1.4rem;
        }

        @media (min-width: 640px) {
            .ids-assistant-prompts {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .ids-assistant-prompt {
            padding: 1rem;
            border: 1px solid #dbe5ef;
            border-radius: 1.35rem;
            background: #fff;
            text-align: left;
            cursor: pointer;
            transition: .16s ease;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
        }

        .ids-assistant-prompt:hover {
            transform: translateY(-1px);
            border-color: #99f6e4;
            box-shadow: 0 16px 30px rgba(15, 23, 42, .06);
        }

        .ids-assistant-prompt__title {
            display: block;
            color: #0f172a;
            font-size: .9rem;
            font-weight: 900;
        }

        .ids-assistant-prompt__sub {
            display: block;
            margin-top: .35rem;
            color: #64748b;
            font-size: .78rem;
            line-height: 1.55;
        }

        .ids-assistant-compose {
            padding: .8rem 1rem .95rem;
            border-top: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, .94);
        }

        .ids-assistant-compose__inner {
            max-width: 58rem;
            margin: 0 auto;
        }

        .ids-assistant-compose__form {
            display: flex;
            align-items: flex-end;
            gap: .75rem;
            padding: .7rem .75rem .7rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 1.55rem;
            background: #fff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
            transition: .16s ease;
        }

        .ids-assistant-compose__form:focus-within {
            border-color: #5eead4;
            box-shadow: 0 0 0 4px rgba(20, 184, 166, .12), 0 16px 36px rgba(15, 23, 42, .08);
        }

        .ids-assistant-compose__textarea {
            width: 100%;
            min-height: 2.4rem;
            max-height: 10rem;
            padding: .35rem 0;
            border: none;
            outline: none;
            resize: none;
            background: transparent;
            color: #0f172a;
            font-size: .95rem;
            line-height: 1.55;
        }

        .ids-assistant-compose__textarea::placeholder {
            color: #94a3b8;
        }

        .ids-assistant-send {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            min-width: 3rem;
            border: none;
            border-radius: 1rem;
            background: linear-gradient(135deg, #0f7b92, #14b8a6);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(20, 184, 166, .22);
            transition: .16s ease;
        }

        .ids-assistant-send:hover:not(:disabled) {
            transform: translateY(-1px);
            filter: brightness(1.04);
        }

        .ids-assistant-send:disabled {
            opacity: .48;
            cursor: not-allowed;
            box-shadow: none;
        }

        .ids-assistant-send svg {
            width: 1.2rem;
            height: 1.2rem;
        }

        .ids-assistant-flash {
            position: absolute;
            top: 1rem;
            left: 50%;
            z-index: 50;
            width: min(30rem, calc(100% - 2rem));
            padding: .9rem 1rem;
            border-radius: 1rem;
            transform: translateX(-50%);
            box-shadow: 0 18px 38px rgba(15, 23, 42, .14);
        }

        .ids-assistant-flash--error {
            border: 1px solid #fecdd3;
            background: #fff1f2;
            color: #be123c;
        }

        .ids-assistant-modal {
            position: fixed;
            inset: 0;
            z-index: 120;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
            background: rgba(15, 23, 42, .44);
            backdrop-filter: blur(8px);
        }

        .ids-assistant-modal__card {
            width: min(100%, 34rem);
            max-height: min(40rem, 85vh);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .45);
            border-radius: 1.8rem;
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .98), rgba(247, 250, 255, .98));
            box-shadow: 0 34px 90px rgba(15, 23, 42, .28);
        }

        .ids-assistant-modal__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.05rem 1.15rem;
            border-bottom: 1px solid #e2e8f0;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .94), rgba(240, 249, 255, .9)),
                radial-gradient(circle at top right, rgba(45, 212, 191, .12), transparent 40%);
        }

        .ids-assistant-modal__title {
            margin: 0;
            color: #0f172a;
            font-size: 1.02rem;
            font-weight: 900;
        }

        .ids-assistant-modal__sub {
            margin-top: .26rem;
            color: #64748b;
            font-size: .8rem;
        }

        .ids-assistant-modal__close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.55rem;
            height: 2.55rem;
            border: 1px solid #dbe5ef;
            border-radius: 1rem;
            background: #fff;
            color: #475569;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
            transition: .16s ease;
        }

        .ids-assistant-modal__close:hover {
            border-color: #99f6e4;
            color: #0f7b92;
            transform: translateY(-1px);
        }

        .ids-assistant-modal__body {
            max-height: calc(min(40rem, 85vh) - 5rem);
            overflow-y: auto;
            padding: 1rem;
            background:
                linear-gradient(180deg, #f8fbff 0%, #f5f9ff 100%);
        }

        .ids-assistant-modal__stats {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin-bottom: .85rem;
            flex-wrap: wrap;
        }

        .ids-assistant-modal__pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2rem;
            padding: .42rem .78rem;
            border-radius: 999px;
            border: 1px solid #dbe5ef;
            background: #fff;
            color: #64748b;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .ids-assistant-modal__pill--accent {
            border-color: #b8f1e6;
            background: linear-gradient(135deg, #ecfeff, #f0fdf4);
            color: #0f7b92;
        }

        .ids-assistant-history-empty {
            padding: 2.2rem 1rem;
            border: 1px dashed #cbd5e1;
            border-radius: 1.45rem;
            text-align: center;
            background: #fff;
            color: #64748b;
        }

        .ids-assistant-history-item {
            width: 100%;
            display: block;
            padding: 1rem 1.05rem;
            border: 1px solid #dbe5ef;
            border-radius: 1.35rem;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            text-align: left;
            cursor: pointer;
            transition: .16s ease;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .04);
        }

        .ids-assistant-history-item + .ids-assistant-history-item {
            margin-top: .7rem;
        }

        .ids-assistant-history-item:hover {
            transform: translateY(-1px);
            border-color: #99f6e4;
            box-shadow: 0 16px 34px rgba(15, 23, 42, .07);
        }

        .ids-assistant-history-item--active {
            border-color: #5eead4;
            background:
                linear-gradient(135deg, #ecfeff, #f8fffe);
            box-shadow: 0 18px 38px rgba(20, 184, 166, .12);
        }

        .ids-assistant-history-item__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .ids-assistant-history-item__title {
            color: #0f172a;
            font-size: .9rem;
            font-weight: 800;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .ids-assistant-history-item__count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            padding: 0 .5rem;
            border-radius: 999px;
            background: linear-gradient(135deg, #eff6ff, #eefdfb);
            color: #1d4ed8;
            font-size: .72rem;
            font-weight: 900;
        }

        .ids-assistant-history-item__meta {
            margin-top: .45rem;
            color: #64748b;
            font-size: .76rem;
            font-weight: 600;
        }

        .ids-assistant-feed::-webkit-scrollbar,
        .ids-assistant-modal__body::-webkit-scrollbar {
            width: 7px;
        }

        .ids-assistant-feed::-webkit-scrollbar-thumb,
        .ids-assistant-modal__body::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: #cbd5e1;
        }

        @media (max-width: 900px) {
            .ids-assistant-shell {
                height: calc(100vh - 9.4rem);
                min-height: 31rem;
            }

            .ids-assistant-header,
            .ids-assistant-toolbar {
                padding-left: .9rem;
                padding-right: .9rem;
            }

            .ids-assistant-header {
                border-radius: 1.1rem;
            }
        }

        @media (max-width: 640px) {
            .ids-assistant-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .ids-assistant-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .ids-assistant-toolbar__actions {
                width: 100%;
                justify-content: flex-start;
            }

            .ids-assistant-empty__card {
                padding: 1.35rem;
                border-radius: 1.45rem;
            }

            .ids-assistant-feed {
                padding: 1rem .8rem;
            }

            .ids-assistant-compose {
                padding: .9rem .8rem 1rem;
            }

            .ids-assistant-bubble-wrap {
                max-width: calc(100% - 3rem);
            }
        }
    </style>

    <div
        class="ids-assistant-shell"
        x-data="{
            message: @js(old('message', '')),
            sending: false,
            creatingChat: false,
            historyOpen: false,
            activeChat: @js($activeChatPayload),
            chatHistory: @js($chatHistoryPayload),
            messages: @js($messagesPayload),
            flashMessage: @js($errors->any() ? $errors->first() : null),
            flashType: 'error',
            baseChatsUrl: @js(url('/assistant/chats')),
            createChatUrl: @js(route('assistant.store')),
            csrfToken: @js(csrf_token()),

            init() {
                this.messages = this.normalizeMessages(this.messages);
                this.autoGrowTextarea();
                this.scrollToBottom();
                this.dismissFlashLater();
            },

            normalizeMessages(payload) {
                if (Array.isArray(payload)) {
                    return payload;
                }

                if (payload && typeof payload === 'object') {
                    return Object.values(payload);
                }

                return [];
            },

            hasMessages() {
                return Array.isArray(this.messages) && this.messages.length > 0;
            },

            dismissFlashLater() {
                if (!this.flashMessage) return;

                setTimeout(() => {
                    this.flashMessage = null;
                }, 3000);
            },

            showFlash(message, type = 'error') {
                if (type !== 'error') return;

                this.flashMessage = message;
                this.flashType = type;
                this.dismissFlashLater();
            },

            activeChatTitle() {
                return this.activeChat?.title ?? 'New Chat';
            },

            activeMessageCount() {
                return this.activeChat?.messages_count ?? this.messages.length;
            },

            usePrompt(text) {
                this.message = text;
                this.autoGrowTextarea();
                this.$nextTick(() => this.$refs.messageBox?.focus());
            },

            async createChat(prefillMessage = null) {
                if (this.creatingChat) return;

                this.creatingChat = true;

                try {
                    const response = await fetch(this.createChatUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to start a new chat.');
                    }

                    this.activeChat = data.chat;
                    this.messages = this.normalizeMessages(data.messages);
                    this.historyOpen = false;
                    this.upsertChatHistory(data.chat, true);
                    this.scrollToBottom();

                    if (prefillMessage) {
                        this.message = prefillMessage;
                        this.autoGrowTextarea();
                        await this.submitMessage();
                    } else {
                        this.message = '';
                        this.autoGrowTextarea();
                        this.$nextTick(() => this.$refs.messageBox?.focus());
                    }
                } catch (error) {
                    this.showFlash(error.message || 'Unable to start a new chat.');
                } finally {
                    this.creatingChat = false;
                }
            },

            async submitMessage() {
                const content = this.message.trim();

                if (!content || this.sending) return;

                if (!this.activeChat) {
                    await this.createChat(content);
                    return;
                }

                const optimisticUser = {
                    id: 'user-' + Date.now(),
                    role: 'user',
                    content,
                    time: 'Now',
                };

                const pendingAI = {
                    id: 'ai-pending-' + Date.now(),
                    role: 'assistant',
                    content: 'Thinking',
                    time: 'Now',
                    pending: true,
                };

                this.sending = true;
                this.message = '';
                this.autoGrowTextarea();

                this.messages.push(optimisticUser, pendingAI);
                this.activeChat.messages_count = (this.activeChat.messages_count ?? 0) + 2;
                this.upsertChatHistory({ ...this.activeChat, last_message_at: 'Just now' }, true);
                this.scrollToBottom();

                try {
                    const response = await fetch(`${this.baseChatsUrl}/${this.activeChat.id}/messages`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ message: content }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const validationMsg = data?.errors?.message?.[0];
                        throw new Error(validationMsg || data.message || 'Unable to send your message.');
                    }

                    this.messages.splice(-2, 2, data.user_message, data.assistant_message);
                    this.activeChat = data.chat;
                    this.upsertChatHistory(data.chat, true);
                    this.scrollToBottom();
                } catch (error) {
                    this.messages = this.messages.filter(entry => entry.id !== optimisticUser.id && entry.id !== pendingAI.id);
                    this.activeChat.messages_count = Math.max((this.activeChat.messages_count ?? 2) - 2, 0);
                    this.message = content;
                    this.autoGrowTextarea();
                    this.showFlash(error.message || 'Unable to send your message.');
                    this.$nextTick(() => this.$refs.messageBox?.focus());
                } finally {
                    this.sending = false;
                }
            },

            submitOnEnter(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    this.submitMessage();
                }
            },

            autoGrowTextarea() {
                this.$nextTick(() => {
                    const textarea = this.$refs.messageBox;

                    if (!textarea) return;

                    textarea.style.height = '0px';
                    textarea.style.height = `${Math.min(textarea.scrollHeight, 160)}px`;
                });
            },

            upsertChatHistory(chat, pinToTop = false) {
                const index = this.chatHistory.findIndex(entry => entry.id === chat.id);

                if (index !== -1) {
                    this.chatHistory.splice(index, 1);
                }

                if (pinToTop) {
                    this.chatHistory.unshift(chat);
                } else {
                    this.chatHistory.push(chat);
                }
            },

            copyText(text) {
                navigator.clipboard.writeText(text);
            },

            openChat(url) {
                window.location.href = url;
            },

            scrollToBottom() {
                this.$nextTick(() => {
                    const feed = this.$refs.chatMessages;

                    if (feed) {
                        feed.scrollTop = feed.scrollHeight;
                    }
                });
            },
        }"
        x-init="init()"
        @keydown.escape.window="historyOpen = false"
    >
        <div
            x-show="flashMessage && flashType === 'error'"
            x-transition.opacity.duration.250ms
            class="ids-assistant-flash ids-assistant-flash--error"
            style="display: none;"
        >
            <p x-text="flashMessage"></p>
        </div>

        <div class="ids-assistant-toolbar">
            <div>
                <h2 class="ids-assistant-toolbar__title" x-text="activeChatTitle()"></h2>
                <p class="ids-assistant-toolbar__sub">Ask about laptops, software, accounts, network, VPN, printers, and other internal IT issues.</p>
            </div>

            <div class="ids-assistant-toolbar__actions">
                <span class="ids-assistant-pill ids-assistant-pill--soft">
                    <span x-text="activeMessageCount()"></span>
                    <span>messages</span>
                </span>

                <button
                    type="button"
                    class="ids-assistant-btn"
                    @click="historyOpen = true"
                >
                    <span>Chat History</span>
                </button>

                <button
                    type="button"
                    class="ids-assistant-btn ids-assistant-btn--primary"
                    @click="createChat()"
                    :disabled="creatingChat || sending"
                >
                    <span x-text="creatingChat ? 'Creating...' : 'New Chat'"></span>
                </button>
            </div>
        </div>

        <div class="ids-assistant-feed" x-ref="chatMessages">
            <div class="ids-assistant-feed__inner">
                <template x-if="hasMessages()">
                    <div class="ids-assistant-feed__messages">
                        <template x-for="entry in messages" :key="entry.id">
                            <article
                                class="ids-assistant-message"
                                :class="entry.role === 'user' ? 'ids-assistant-message--user' : 'ids-assistant-message--assistant'"
                            >
                                <template x-if="entry.role !== 'user'">
                                    <div class="ids-assistant-avatar ids-assistant-avatar--assistant">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.8"
                                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4v-4z"
                                            />
                                        </svg>
                                    </div>
                                </template>

                                <div class="ids-assistant-bubble-wrap">
                                    <div
                                        class="ids-assistant-bubble"
                                        :class="{
                                            'ids-assistant-bubble--user': entry.role === 'user',
                                            'ids-assistant-bubble--assistant': entry.role !== 'user',
                                            'ids-assistant-bubble--pending': entry.pending,
                                        }"
                                    >
                                        <div class="ids-assistant-bubble__meta">
                                            <span
                                                class="ids-assistant-bubble__role"
                                                x-text="entry.role === 'user' ? 'You' : 'AI Assistant'"
                                            ></span>
                                            <span class="ids-assistant-bubble__time" x-text="entry.time"></span>
                                        </div>

                                        <p class="ids-assistant-bubble__text" x-text="entry.content"></p>
                                    </div>

                                    <template x-if="entry.role !== 'user' && !entry.pending">
                                        <div class="ids-assistant-actions">
                                            <button
                                                type="button"
                                                class="ids-assistant-actions__btn"
                                                @click="copyText(entry.content)"
                                            >
                                                Copy
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <template x-if="entry.role === 'user'">
                                    <div class="ids-assistant-avatar ids-assistant-avatar--user">
                                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                                    </div>
                                </template>
                            </article>
                        </template>
                    </div>
                </template>

                <template x-if="!hasMessages()">
                    <div class="ids-assistant-empty">
                        <div class="ids-assistant-empty__card">
                            <span class="ids-assistant-empty__badge">Smart IT Guidance</span>

                            <h3 class="ids-assistant-empty__title">
                                Start a new support conversation
                            </h3>

                            <p class="ids-assistant-empty__sub">
                                Describe the technical issue you are facing and the assistant will help you troubleshoot it step by step before you open a ticket.
                            </p>

                            <div class="ids-assistant-prompts">
                                <button
                                    type="button"
                                    class="ids-assistant-prompt"
                                    @click="usePrompt('My printer is not printing. Give me the steps to troubleshoot it.')"
                                >
                                    <span class="ids-assistant-prompt__title">Printer issue</span>
                                    <span class="ids-assistant-prompt__sub">Check connectivity, queue status, paper, toner, and driver setup.</span>
                                </button>

                                <button
                                    type="button"
                                    class="ids-assistant-prompt"
                                    @click="usePrompt('I cannot connect to the office Wi-Fi. What should I check first?')"
                                >
                                    <span class="ids-assistant-prompt__title">Wi-Fi problem</span>
                                    <span class="ids-assistant-prompt__sub">Troubleshoot signal, adapter, saved networks, and internet access.</span>
                                </button>

                                <button
                                    type="button"
                                    class="ids-assistant-prompt"
                                    @click="usePrompt('I forgot my work password. What is the safe reset process?')"
                                >
                                    <span class="ids-assistant-prompt__title">Password reset</span>
                                    <span class="ids-assistant-prompt__sub">Follow secure access recovery steps for accounts and email.</span>
                                </button>

                                <button
                                    type="button"
                                    class="ids-assistant-prompt"
                                    @click="usePrompt('My laptop is very slow. Give me a checklist to improve it.')"
                                >
                                    <span class="ids-assistant-prompt__title">Slow laptop</span>
                                    <span class="ids-assistant-prompt__sub">Review updates, storage, startup apps, browser load, and background tasks.</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="ids-assistant-compose">
            <div class="ids-assistant-compose__inner">
                <form @submit.prevent="submitMessage()">
                    <div class="ids-assistant-compose__form">
                        <textarea
                            x-ref="messageBox"
                            x-model="message"
                            @keydown="submitOnEnter($event)"
                            @input="autoGrowTextarea()"
                            id="message"
                            name="message"
                            rows="1"
                            class="ids-assistant-compose__textarea"
                            placeholder="Ask anything about IT support..."
                            :disabled="sending || creatingChat"
                            required
                        ></textarea>

                        <button
                            type="submit"
                            class="ids-assistant-send"
                            :disabled="sending || creatingChat || !message.trim()"
                            title="Send message"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2.2"
                                    d="M5 12h14m-6-6l6 6-6 6"
                                />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <template x-teleport="body">
            <div
                x-show="historyOpen"
                x-cloak
                x-transition.opacity.duration.180ms
                class="ids-assistant-modal"
                style="display: none;"
                @click.self="historyOpen = false"
            >
                <div class="ids-assistant-modal__card">
                    <div class="ids-assistant-modal__head">
                        <div>
                            <h3 class="ids-assistant-modal__title">Previous Chats</h3>
                            <p class="ids-assistant-modal__sub">Open any earlier conversation from your support history.</p>
                        </div>

                        <button
                            type="button"
                            class="ids-assistant-modal__close"
                            @click="historyOpen = false"
                        >
                            ×
                        </button>
                    </div>

                    <div class="ids-assistant-modal__body">
                        <div class="ids-assistant-modal__stats">
                            <span class="ids-assistant-modal__pill ids-assistant-modal__pill--accent">
                                <span x-text="chatHistory.length"></span>&nbsp;Chats
                            </span>
                            <span class="ids-assistant-modal__pill">History Archive</span>
                        </div>

                        <template x-if="chatHistory.length === 0">
                            <div class="ids-assistant-history-empty">
                                No previous chats yet. Start a conversation to build your AI support history.
                            </div>
                        </template>

                        <template x-for="chat in chatHistory" :key="chat.id">
                            <button
                                type="button"
                                class="ids-assistant-history-item"
                                :class="activeChat && activeChat.id === chat.id ? 'ids-assistant-history-item--active' : ''"
                                @click="openChat(chat.show_url)"
                            >
                                <div class="ids-assistant-history-item__top">
                                    <span class="ids-assistant-history-item__title" x-text="chat.title"></span>
                                    <span class="ids-assistant-history-item__count" x-text="chat.messages_count"></span>
                                </div>

                                <p class="ids-assistant-history-item__meta" x-text="chat.last_message_at"></p>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
