import { api } from './api';
import toast from 'react-hot-toast';

export interface Notification {
  id: number;
  title: string;
  message: string;
  type: 'info' | 'warning' | 'error' | 'success' | 'urgent' | 'critical';
  urgency: 'low' | 'medium' | 'high' | 'urgent' | 'critical';
  user_id?: number;
  company_id: number;
  read_at?: string;
  created_at: string;
  data?: any;
}

export interface NotificationCount {
  total: number;
  urgent: number;
}

class NotificationService {
  private pollingInterval: number | null = null;
  private pollIntervalMs = 30000; // 30 seconds

  startPolling(callback: (notifications: Notification[]) => void, countCallback: (count: NotificationCount) => void) {
    if (this.pollingInterval) return;

    const poll = async () => {
      try {
        const [notificationsResponse, countResponse] = await Promise.all([
          api.get('/notifications?limit=50'),
          api.get('/notifications/unread-count')
        ]);

        callback(notificationsResponse.data.data);
        countCallback(countResponse.data.data);
      } catch (error) {
        console.error('Failed to fetch notifications:', error);
      }
    };

    // Initial fetch
    poll();

    // Set up interval
    this.pollingInterval = window.setInterval(poll, this.pollIntervalMs);
  }

  stopPolling() {
    if (this.pollingInterval) {
      clearInterval(this.pollingInterval);
      this.pollingInterval = null;
    }
  }

  async markAsRead(notificationId: number) {
    try {
      await api.post(`/notifications/${notificationId}/read`);
      return true;
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
      return false;
    }
  }

  async markAllAsRead() {
    try {
      await api.post('/notifications/mark-all-read');
      toast.success('All notifications marked as read');
      return true;
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error);
      return false;
    }
  }

  async deleteNotification(notificationId: number) {
    try {
      await api.delete(`/notifications/${notificationId}`);
      toast.success('Notification deleted');
      return true;
    } catch (error) {
      console.error('Failed to delete notification:', error);
      return false;
    }
  }

  async createNotification(notification: Partial<Notification>) {
    try {
      const response = await api.post('/notifications', notification);
      return response.data.data;
    } catch (error) {
      console.error('Failed to create notification:', error);
      throw error;
    }
  }
}

export const notificationService = new NotificationService();
