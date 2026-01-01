import React from 'react';
import { NavLink } from 'react-router-dom';
import { useAppContext } from '../context/AppContext';
import { LayoutDashboard, Users, Settings, LogOut, Package, UserSquare2, ShieldCheck, Mail } from 'lucide-react';

const Sidebar: React.FC = () => {
    const { user, logout } = useAppContext();

    const navItems = [
        { name: 'Dashboard', icon: <LayoutDashboard size={20} />, path: '/' },
        { name: 'Accounts', icon: <UserSquare2 size={20} />, path: '/Account' },
        { name: 'Contacts', icon: <Users size={20} />, path: '/Contact' },
        { name: 'Users', icon: <ShieldCheck size={20} />, path: '/User' },
        { name: 'Emails', icon: <Mail size={20} />, path: '/Email' },
        { name: 'Modules', icon: <Package size={20} />, path: '/Extension' },
        { name: 'Settings', icon: <Settings size={20} />, path: '/Settings' },
    ];

    return (
        <aside className="sidebar">
            <div style={{ padding: '2rem', fontSize: '1.25rem', fontWeight: 'bold', color: 'var(--primary)' }}>
                XibalbaCRM
            </div>

            <nav style={{ flex: 1, overflowY: 'auto' }}>
                {navItems.map(item => (
                    <NavLink
                        key={item.name}
                        to={item.path}
                        className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`}
                    >
                        <span style={{ marginRight: '12px' }}>{item.icon}</span>
                        {item.name}
                    </NavLink>
                ))}
            </nav>

            <div style={{ padding: '1.5rem', borderTop: '1px solid var(--border)' }}>
                <div style={{ display: 'flex', alignItems: 'center', marginBottom: '1rem' }}>
                    <div style={{ width: '32px', height: '32px', borderRadius: '50%', background: 'var(--primary)', marginRight: '10px' }}></div>
                    <div style={{ overflow: 'hidden' }}>
                        <div style={{ fontSize: '0.875rem', fontWeight: 600, whiteSpace: 'nowrap', textOverflow: 'ellipsis' }}>{user?.userName}</div>
                        <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>{user?.type}</div>
                    </div>
                </div>
                <button onClick={logout} className="nav-item" style={{ width: '100%', background: 'none', border: 'none', cursor: 'pointer', padding: '0.5rem 0', justifyContent: 'flex-start' }}>
                    <LogOut size={18} style={{ marginRight: '12px' }} />
                    Logout
                </button>
            </div>
        </aside>
    );
};

export default Sidebar;
