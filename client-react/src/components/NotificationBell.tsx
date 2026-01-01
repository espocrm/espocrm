import React, { useState, useEffect, useRef } from 'react';
import { Bell } from 'lucide-react';
import type { Notification } from '../services/NotificationService';
import NotificationService from '../services/NotificationService';

const NotificationBell: React.FC = () => {
    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [showDropdown, setShowDropdown] = useState(false);
    const dropdownRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const handleNewNotifications = (newOnes: Notification[]) => {
            setNotifications(prev => {
                const combined = [...newOnes, ...prev].slice(0, 50); // Keep last 50
                // Simple de-duplication by ID
                const unique = Array.from(new Map(combined.map(n => [n.id, n])).values());
                return unique;
            });
        };

        NotificationService.subscribe(handleNewNotifications);

        return () => {
            NotificationService.unsubscribe(handleNewNotifications);
        };
    }, []);

    const unreadCount = notifications.filter(n => !n.isRead).length;

    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
                setShowDropdown(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleMarkRead = (id: string, e: React.MouseEvent) => {
        e.stopPropagation();
        NotificationService.markRead(id);
    };

    const handleMarkAllRead = () => {
        NotificationService.markAllRead();
    };

    return (
        <div className="notification-bell" ref={dropdownRef}>
            <div onClick={() => setShowDropdown(!showDropdown)}>
                <Bell size={20} />
                {unreadCount > 0 && (
                    <span className="notification-badge">{unreadCount}</span>
                )}
            </div>

            {showDropdown && (
                <div className="notification-dropdown card glass" style={{ display: 'flex', flexDirection: 'column' }}>
                    <div style={{ padding: '1rem', borderBottom: '1px solid var(--border)', fontWeight: 600, display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span>Notifications</span>
                        {unreadCount > 0 && (
                            <button
                                onClick={handleMarkAllRead}
                                style={{ background: 'transparent', border: 'none', color: 'var(--primary)', fontSize: '0.75rem', cursor: 'pointer' }}
                            >
                                Mark all as read
                            </button>
                        )}
                    </div>
                    {notifications.length === 0 ? (
                        <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                            No notifications
                        </div>
                    ) : (
                        notifications.map(notification => (
                            <div
                                key={notification.id}
                                className={`notification-item ${!notification.isRead ? 'unread' : ''}`}
                                onClick={(e) => !notification.isRead && handleMarkRead(notification.id, e)}
                            >
                                <div className="message">{notification.message}</div>
                                <div className="time">
                                    {new Date(notification.createdAt).toLocaleString()}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}
        </div>
    );
};

export default NotificationBell;
