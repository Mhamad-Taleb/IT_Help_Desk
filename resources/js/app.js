import './bootstrap';
import './echo';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.dashboardHeader = (config) => ({
    notificationsOpen: false,
    notifications: config.notifications ?? [],
    unreadCount: config.unreadCount ?? 0,
    markAllReadUrl: config.markAllReadUrl ?? '',
    clearAllUrl: config.clearAllUrl ?? '',
    async markAsRead(id, url) {
        const notificationIndex = this.notifications.findIndex((item) => item.id === id);
        const notification = notificationIndex >= 0 ? this.notifications[notificationIndex] : null;

        if (! notification || notification.is_read) {
            return;
        }

        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });

            if (! response.ok) {
                throw new Error('Unable to mark notification as read.');
            }

            this.notifications.splice(notificationIndex, 1);
            this.unreadCount = Math.max(0, this.unreadCount - 1);
        } catch (error) {
            console.error(error);
        }
    },
    async markAllAsRead() {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        try {
            const response = await fetch(this.markAllReadUrl, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });

            if (! response.ok) {
                throw new Error('Unable to mark all notifications as read.');
            }

            this.notifications = [];
            this.unreadCount = 0;
        } catch (error) {
            console.error(error);
        }
    },
    async clearAllNotifications() {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        try {
            const response = await fetch(this.clearAllUrl, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            });

            if (! response.ok) {
                throw new Error('Unable to clear notifications.');
            }

            this.notifications = [];
            this.unreadCount = 0;
        } catch (error) {
            console.error(error);
        }
    },
});

window.ticketDetailPage = (config) => ({
    attachments: config.attachments ?? [],
    comments: config.comments ?? [],
    uploadUrl: config.uploadUrl ?? '',
    commentUrl: config.commentUrl ?? '',
    csrfToken: config.csrfToken ?? '',
    uploadError: '',
    uploadSuccess: '',
    uploadSubmitting: false,
    prependAttachments(attachments) {
        if (! Array.isArray(attachments) || attachments.length === 0) {
            return;
        }

        this.attachments = [
            ...attachments,
            ...this.attachments,
        ];
    },
    prependComment(comment) {
        if (! comment) {
            return;
        }

        this.comments = [
            comment,
            ...this.comments,
        ];
    },
    resetUploadModal() {
        this.uploadError = '';
        this.uploadSuccess = '';
        this.uploadSubmitting = false;

        if (this.$refs.attachmentInput) {
            this.$refs.attachmentInput.value = '';
        }
    },
    async submitAttachmentUpload(event) {
        this.uploadError = '';
        this.uploadSuccess = '';
        this.uploadSubmitting = true;

        const formData = new FormData(event.target);

        try {
            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.uploadError = payload.message ?? 'Unable to upload the selected file.';
                return;
            }

            this.uploadSuccess = payload.message ?? 'File uploaded successfully.';
            this.prependAttachments(payload.attachments ?? []);

            if (this.$refs.attachmentInput) {
                this.$refs.attachmentInput.value = '';
            }
        } catch (error) {
            console.error(error);
            this.uploadError = 'Unable to upload the selected file.';
        } finally {
            this.uploadSubmitting = false;
        }
    },
});

window.ticketUploadModal = (config) => ({
    uploadUrl: config.uploadUrl ?? '',
    csrfToken: config.csrfToken ?? '',
    uploadError: '',
    uploadSuccess: '',
    uploadSubmitting: false,
    resetUploadModal() {
        this.uploadError = '';
        this.uploadSuccess = '';
        this.uploadSubmitting = false;

        if (this.$refs.attachmentInput) {
            this.$refs.attachmentInput.value = '';
        }
    },
    async submitAttachmentUpload(event) {
        this.uploadError = '';
        this.uploadSuccess = '';
        this.uploadSubmitting = true;

        const formData = new FormData(event.target);

        try {
            const response = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.uploadError = payload.message ?? 'Unable to upload the selected file.';
                return;
            }

            this.uploadSuccess = payload.message ?? 'File uploaded successfully.';
            window.dispatchEvent(new CustomEvent('ticket-attachments-added', {
                detail: {
                    attachments: payload.attachments ?? [],
                },
            }));

            if (this.$refs.attachmentInput) {
                this.$refs.attachmentInput.value = '';
            }
        } catch (error) {
            console.error(error);
            this.uploadError = 'Unable to upload the selected file.';
        } finally {
            this.uploadSubmitting = false;
        }
    },
});

window.ticketCommentModal = (config) => ({
    commentUrl: config.commentUrl ?? '',
    csrfToken: config.csrfToken ?? '',
    commentError: '',
    commentSuccess: '',
    commentSubmitting: false,
    resetCommentModal() {
        this.commentError = '';
        this.commentSuccess = '';
        this.commentSubmitting = false;

        if (this.$refs.commentInput) {
            this.$refs.commentInput.value = '';
        }
    },
    async submitComment(event) {
        this.commentError = '';
        this.commentSuccess = '';
        this.commentSubmitting = true;

        const formData = new FormData(event.target);

        try {
            const response = await fetch(this.commentUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (! response.ok) {
                this.commentError = payload.message ?? 'Unable to add the comment.';
                return;
            }

            this.commentSuccess = payload.message ?? 'Comment added successfully.';

            if (this.$refs.commentInput) {
                this.$refs.commentInput.value = '';
            }

            window.dispatchEvent(new CustomEvent('ticket-comment-added', {
                detail: {
                    comment: payload.comment ?? null,
                },
            }));

            this.resetCommentModal();
        } catch (error) {
            console.error(error);
            this.commentError = 'Unable to add the comment.';
        } finally {
            this.commentSubmitting = false;
        }
    },
});

window.ticketChatPage = (config) => ({
    ticketId: config.ticketId ?? null,
    currentUserId: config.currentUserId ?? null,
    chatUrl: config.chatUrl ?? '',
    csrfToken: config.csrfToken ?? '',
    messages: config.messages ?? [],
    channelName: config.channelName ?? '',
    isReadOnly: config.isReadOnly ?? false,
    body: '',
    chatError: '',
    chatSubmitting: false,
    channel: null,
    init() {
        this.scrollToBottom();
        this.subscribe();
    },
    subscribe() {
        if (! window.Echo || ! this.channelName) {
            return;
        }

        this.channel = window.Echo.private(this.channelName);

        this.channel.listen('.ticket.chat.message.sent', (payload) => {
            const message = payload?.message;

            if (! message) {
                return;
            }

            this.appendIncomingMessage(message);
        });
    },
    appendIncomingMessage(message) {
        if (! message || this.messages.some((entry) => String(entry.id) === String(message.id))) {
            return;
        }

        this.messages.push(message);
        this.scrollToBottom();
    },
    async sendMessage() {
        const content = this.body.trim();

        if (! content || this.chatSubmitting || this.isReadOnly) {
            return;
        }

        this.chatError = '';
        this.chatSubmitting = true;

        const tempId = `temp-${Date.now()}`;
        const optimisticMessage = {
            id: tempId,
            ticket_id: this.ticketId,
            user_id: this.currentUserId,
            user_name: 'You',
            role_label: '',
            body: content,
            created_at: 'Sending...',
            time: 'Now',
            initial: 'Y',
            pending: true,
        };

        this.messages.push(optimisticMessage);
        this.body = '';
        this.resizeComposer();
        this.scrollToBottom();

        try {
            const response = await fetch(this.chatUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Socket-ID': window.Echo?.socketId?.() ?? '',
                },
                body: JSON.stringify({
                    body: content,
                }),
            });

            const payload = await response.json().catch(() => ({}));

            if (! response.ok) {
                throw new Error(payload.message ?? 'Unable to send the chat message.');
            }

            const optimisticIndex = this.messages.findIndex((entry) => entry.id === tempId);

            if (optimisticIndex !== -1) {
                this.messages.splice(optimisticIndex, 1, payload.chat_message);
            } else {
                this.appendIncomingMessage(payload.chat_message);
            }

            this.scrollToBottom();
        } catch (error) {
            this.messages = this.messages.filter((entry) => entry.id !== tempId);
            this.body = content;
            this.resizeComposer();
            this.chatError = error.message ?? 'Unable to send the chat message.';
        } finally {
            this.chatSubmitting = false;
        }
    },
    submitOnEnter(event) {
        if (event.key === 'Enter' && ! event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        }
    },
    resizeComposer() {
        this.$nextTick(() => {
            const textarea = this.$refs.chatInput;

            if (! textarea) {
                return;
            }

            textarea.style.height = '0px';
            textarea.style.height = `${Math.min(textarea.scrollHeight, 180)}px`;
        });
    },
    scrollToBottom() {
        this.$nextTick(() => {
            const container = this.$refs.chatFeed;

            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    },
});

window.adminReportsPage = (config = {}) => ({
    init() {
        this.$store.adminReports.boot(config);
    },
});

Alpine.store('adminReports', {
    loading: false,
    range: '',
    rangeLabel: '',
    generatedAt: '',
    exportUrl: '',
    contentHtml: '',

    boot(config = {}) {
        this.range = config.range ?? this.range;
        this.rangeLabel = config.rangeLabel ?? this.rangeLabel;
        this.generatedAt = config.generatedAt ?? this.generatedAt;
        this.exportUrl = config.exportUrl ?? this.exportUrl;
        this.contentHtml = config.contentHtml ?? this.contentHtml;
    },

    async loadRange(url) {
        if (! url || this.loading) {
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (! response.ok) {
                throw new Error('Unable to load the selected report range.');
            }

            const payload = await response.json();

            this.range = payload.range ?? this.range;
            this.rangeLabel = payload.range_label ?? this.rangeLabel;
            this.generatedAt = payload.generated_at ?? this.generatedAt;
            this.exportUrl = payload.export_url ?? this.exportUrl;
            this.contentHtml = payload.html ?? this.contentHtml;

            if (payload.page_url) {
                window.history.replaceState({}, '', payload.page_url);
            }
        } catch (error) {
            console.error(error);
        } finally {
            this.loading = false;
        }
    },
});

Alpine.store('dashboardTheme', {
    darkMode: false,

    init() {
        this.darkMode = window.localStorage.getItem('ids-dashboard-theme') === 'dark';
        this.apply();
    },

    toggle() {
        this.darkMode = !this.darkMode;
        window.localStorage.setItem('ids-dashboard-theme', this.darkMode ? 'dark' : 'light');
        this.apply();
    },

    apply() {
        document.documentElement.classList.toggle('ids-dashboard-dark', this.darkMode);
    },
});

Alpine.start();

Alpine.store('dashboardTheme').init();
