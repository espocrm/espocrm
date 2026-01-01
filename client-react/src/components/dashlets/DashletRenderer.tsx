import React from 'react';
import StreamDashlet from './StreamDashlet';
import CalendarDashlet from './CalendarDashlet';

interface DashletRendererProps {
    name: string;
    id: string;
    options?: any;
}

const DashletRenderer: React.FC<DashletRendererProps> = ({ name, id, options }) => {
    switch (name) {
        case 'Stream':
            return <StreamDashlet dashletId={id} options={options} />;
        case 'Calendar':
            return <CalendarDashlet dashletId={id} options={options} />;
        default:
            return (
                <div style={{ padding: '1rem', color: 'var(--text-muted)' }}>
                    Dashlet "{name}" not implemented yet.
                </div>
            );
    }
};

export default DashletRenderer;
