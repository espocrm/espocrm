import React from 'react';
import Sidebar from './Sidebar';
import Navbar from './Navbar';

const Layout: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    return (
        <div style={{ display: 'flex', minHeight: '100vh' }}>
            <Sidebar />

            <main style={{ flex: 1, display: 'flex', flexDirection: 'column', minHeight: '100vh', marginLeft: '260px' }}>
                <Navbar />
                <div className="main-content" style={{ padding: '2rem', flex: 1, overflowY: 'auto', marginLeft: 0 }}>
                    {children}
                </div>
            </main>
        </div>
    );
};

export default Layout;
