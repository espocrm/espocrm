import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { Calendar as CalendarIcon, Clock } from 'lucide-react';

const CalendarDashlet: React.FC<{ dashletId: string, options?: Record<string, any> }> = () => {
    const [events, setEvents] = useState<Record<string, any>[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const fetchEvents = async () => {
            setIsLoading(true);
            try {
                const response = await api.get('/api/v1/Meeting', {
                    params: {
                        maxSize: 5,
                        offset: 0,
                        sortBy: 'dateStart',
                        asc: true,
                        where: [
                            { type: 'greaterThanOrEquals', attribute: 'dateStart', value: new Date().toISOString() }
                        ]
                    }
                });
                setEvents(response.data.list);
            } catch (e) {
                console.error('Failed to fetch events', e);
            } finally {
                setIsLoading(false);
            }
        };
        fetchEvents();
    }, []);

    if (isLoading) return <div style={{ padding: '1rem' }}>Loading...</div>;

    return (
        <div className="calendar-dashlet">
            {events.length === 0 ? (
                <div style={{ padding: '1.5rem', textAlign: 'center', color: 'var(--text-muted)' }}>
                    <CalendarIcon size={40} style={{ marginBottom: '1rem', opacity: 0.2 }} />
                    <p>No upcoming events.</p>
                </div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column' }}>
                    {events.map(event => (
                        <div key={event.id} style={{ padding: '1rem', borderBottom: '1px solid var(--border)', display: 'flex', gap: '1rem', alignItems: 'center' }}>
                            <div style={{ textAlign: 'center', minWidth: '50px' }}>
                                <div style={{ fontSize: '0.75rem', fontWeight: 'bold', color: 'var(--primary)', textTransform: 'uppercase' }}>
                                    {new Date(event.dateStart as string).toLocaleDateString('en-US', { month: 'short' })}
                                </div>
                                <div style={{ fontSize: '1.25rem', fontWeight: 'bold' }}>
                                    {new Date(event.dateStart as string).getDate()}
                                </div>
                            </div>
                            <div style={{ flex: 1 }}>
                                <div style={{ fontWeight: 600 }}>{event.name as string}</div>
                                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', display: 'flex', alignItems: 'center', gap: '4px', marginTop: '4px' }}>
                                    <Clock size={12} /> {new Date(event.dateStart as string).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default CalendarDashlet;
