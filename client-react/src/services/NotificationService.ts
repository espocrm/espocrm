import api from './api';

export interface Notification {
    id: string;
    message: string;
    type: string;
    createdAt: string;
    isRead: boolean;
}

type NotificationCallback = (notifications: Notification[]) => void;

class NotificationService {
    private callbacks: NotificationCallback[] = [];
    private interval: ReturnType<typeof setInterval> | null = null;
    private lastId: string | null = null;

    subscribe(callback: NotificationCallback) {
        this.callbacks.push(callback);
        if (!this.interval) {
            this.startPolling();
        }
    }

    unsubscribe(callback: NotificationCallback) {
        this.callbacks = this.callbacks.filter(cb => cb !== callback);
        if (this.callbacks.length === 0 && this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    private startPolling() {
        this.fetchNotifications();
        this.interval = setInterval(() => {
            this.fetchNotifications();
        }, 30000); // Poll every 30 seconds
    }

    private async fetchNotifications() {
        try {
            const response = await api.get('/api/v1/Notification', {
                params: {
                    where: this.lastId ? JSON.stringify([{ type: 'greaterThan', field: 'id', value: this.lastId }]) : undefined
                }
            });
            const newNotifications = response.data.list;
            if (newNotifications.length > 0) {
                this.lastId = newNotifications[0].id;
                this.callbacks.forEach(cb => cb(newNotifications));
            }
        } catch (error) {
            console.error('Failed to fetch notifications', error);
        }
    }

    async markRead(id: string) {
        try {
            await api.patch(`/api/v1/Notification/${id}`);
            this.fetchNotifications();
        } catch (error) {
            console.error('Failed to mark notification as read', error);
        }
    }

    async markAllRead() {
        try {
            await api.post('/api/v1/Notification/action/markAllAsRead');
            this.fetchNotifications();
        } catch (error) {
            console.error('Failed to mark all as read', error);
        }
    }
}

export default new NotificationService();
