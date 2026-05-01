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
  private subscribers: Set<(notifications: Notification[]) => void> = new Set();
  private countSubscribers: Set<(count: NotificationCount) => void> = new Set();
  private lastPoll = 0;
  private pollIntervalMs = 30000; // 30 seconds

  startPolling() {
    if (this.pollingInterval) return;

    this.pollingInterval = window.setInterval(() => {
      this.fetchNotifications();
      this.fetchUnreadCount();
    }, this.pollIntervalMs);

    // Initial fetch
    this.fetchNotifications();
    this.fetchUnreadCount();
  }

  stopPolling() {
    if (this.pollingInterval) {
      window.clearInterval(this.pollingInterval);
      this.pollingInterval = null;
    }
  }

  subscribe(callback: (notifications: Notification[]) => void) {
    this.subscribers.add(callback);
    return () => this.subscribers.delete(callback);
  }

  subscribeToCount(callback: (count: NotificationCount) => void) {
    this.countSubscribers.add(callback);
    return () => this.countSubscribers.delete(callback);
  }

  private async fetchNotifications() {
    try {
      const response = await api.get('/notifications');
      const notifications = response.data.data;
      this.subscribers.forEach(callback => callback(notifications));
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    }
  }

  private async fetchUnreadCount() {
    try {
      const response = await api.get('/notifications/count');
      const count = response.data.data;
      this.countSubscribers.forEach(callback => callback(count));
    } catch (error) {
      console.error('Failed to fetch notification count:', error);
    }
  }

  async markAsRead(id: number) {
    try {
      await api.post(`/notifications/${id}/read`);
      this.fetchNotifications();
      this.fetchUnreadCount();
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
      toast.error('Failed to mark notification as read');
    }
  }

  async markAllAsRead() {
    try {
      await api.post('/notifications/mark-all-read');
      this.fetchNotifications();
      this.fetchUnreadCount();
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error);
      toast.error('Failed to mark all notifications as read');
    }
  }

  async deleteNotification(id: number) {
    try {
      await api.delete(`/notifications/${id}`);
      this.fetchNotifications();
      this.fetchUnreadCount();
    } catch (error) {
      console.error('Failed to delete notification:', error);
      toast.error('Failed to delete notification');
    }
  }
}

export const notificationService = new NotificationService();
