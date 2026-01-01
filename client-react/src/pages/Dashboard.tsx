import React, { useState, useEffect } from 'react';
import { useAppContext } from '../context/AppContext';
import DashletRenderer from '../components/dashlets/DashletRenderer';

interface DashletLayout {
    id: string;
    name: string;
    x: number;
    y: number;
    width: number;
    height: number;
    options?: Record<string, unknown>;
}

interface DashboardTab {
    name: string;
    layout: DashletLayout[];
}

const Dashboard: React.FC = () => {
    const { user } = useAppContext();
    const [activeTab, setActiveTab] = useState(0);
    const [dashboardLayout, setDashboardLayout] = useState<DashboardTab[]>([]);

    useEffect(() => {
        // In a real app, this would come from user preferences
        const layout: DashboardTab[] = (user?.preferences?.dashboardLayout as DashboardTab[]) || [
            {
                name: 'My Dashboard',
                layout: [
                    { id: 'd1', name: 'Stream', x: 0, y: 0, width: 4, height: 4 },
                    { id: 'd2', name: 'Calendar', x: 4, y: 0, width: 2, height: 4 },
                ]
            }
        ];
        setDashboardLayout(layout);
    }, [user]);

    if (dashboardLayout.length === 0) return null;

    return (
        <div className="dashboard">
            <div className="tabs" style={{ display: 'flex', gap: '1rem', marginBottom: '2rem', borderBottom: '1px solid var(--border)' }}>
                {dashboardLayout.map((tab, index) => (
                    <button
                        key={tab.name}
                        onClick={() => setActiveTab(index)}
                        className={`tab-btn ${activeTab === index ? 'active' : ''}`}
                        style={{
                            padding: '0.75rem 1.5rem',
                            background: 'none',
                            border: 'none',
                            color: activeTab === index ? 'var(--primary)' : 'var(--text-muted)',
                            borderBottom: activeTab === index ? '2px solid var(--primary)' : 'none',
                            cursor: 'pointer',
                            fontWeight: activeTab === index ? 600 : 400
                        }}
                    >
                        {tab.name}
                    </button>
                ))}
            </div>

            <div className="dashlets-grid" style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(12, 1fr)',
                gap: '1.5rem'
            }}>
                {dashboardLayout[activeTab].layout.map(dashlet => (
                    <div
                        key={dashlet.id}
                        className="card glass"
                        style={{
                            gridColumn: `span ${dashlet.width * 2}`,
                            minHeight: `${dashlet.height * 100}px`,
                            padding: 0,
                            overflow: 'hidden'
                        }}
                    >
                        <div style={{ padding: '1rem', borderBottom: '1px solid var(--border)', fontWeight: 600 }}>
                            {dashlet.name}
                        </div>
                        <div style={{ color: 'var(--text-muted)' }}>
                            <DashletRenderer name={dashlet.name} id={dashlet.id} options={dashlet.options} />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default Dashboard;
