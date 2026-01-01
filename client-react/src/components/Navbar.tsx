import React from 'react';
import NotificationBell from './NotificationBell';

const Navbar: React.FC = () => {
    return (
        <header className="header">
            <div className="navbar-logo" style={{ display: 'none' }}>
                {/* Logo can go here if we want it in the navbar too */}
            </div>
            <div style={{ flex: 1 }}></div>
            <div className="navbar-actions" style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                <NotificationBell />
            </div>
        </header>
    );
};

export default Navbar;
