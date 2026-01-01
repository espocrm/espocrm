import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { User } from 'lucide-react';

const StreamDashlet: React.FC<{ dashletId: string, options?: Record<string, any> }> = ({ options }) => {
    const [notes, setNotes] = useState<Record<string, any>[]>([]);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const fetchStream = async () => {
            setIsLoading(true);
            try {
                const response = await api.get('/api/v1/Stream', {
                    params: {
                        maxSize: options?.displayRecords || 5,
                        skipOwn: options?.skipOwn || false
                    }
                });
                setNotes(response.data.list);
            } catch (e) {
                console.error('Failed to fetch stream', e);
            } finally {
                setIsLoading(false);
            }
        };
        fetchStream();
    }, [options]);

    if (isLoading) return <div style={{ padding: '1rem' }}>Loading...</div>;

    return (
        <div className="stream-dashlet">
            {notes.length === 0 ? (
                <div style={{ padding: '1rem', color: 'var(--text-muted)' }}>No activities.</div>
            ) : (
                <div style={{ display: 'flex', flexDirection: 'column' }}>
                    {notes.map(note => (
                        <div key={note.id} style={{ padding: '1rem', borderBottom: '1px solid var(--border)', display: 'flex', gap: '1rem' }}>
                            <div style={{ width: '40px', height: '40px', borderRadius: '50%', background: 'var(--bg-card)', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                <User size={20} color="var(--primary)" />
                            </div>
                            <div style={{ flex: 1 }}>
                                <div style={{ fontSize: '0.875rem', fontWeight: 600 }}>{note.createdByName as string}</div>
                                <div style={{ fontSize: '0.875rem', color: 'var(--text-muted)', marginTop: '0.25rem' }} dangerouslySetInnerHTML={{ __html: (note.post as string) || '' }}></div>
                                <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '0.5rem' }}>{note.createdAt as string}</div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default StreamDashlet;
