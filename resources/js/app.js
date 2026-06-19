import './bootstrap';

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
