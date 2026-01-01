import React, { useState } from 'react';
import { useAppContext } from '../context/AppContext';
import { LogIn } from 'lucide-react';

const LoginPage: React.FC = () => {
    const [userName, setUserName] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const { login } = useAppContext();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await login(userName, password);
        } catch (err: any) {
            setError('Invalid credentials');
        }
    };

    return (
        <div className="auth-container">
            <div className="card glass">
                <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
                    <h1 style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>XibalbaCRM</h1>
                    <p style={{ color: 'var(--text-muted)' }}>Welcome back</p>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="input-group">
                        <label>Username</label>
                        <input
                            type="text"
                            value={userName}
                            onChange={e => setUserName(e.target.value)}
                            placeholder="admin"
                            required
                        />
                    </div>
                    <div className="input-group">
                        <label>Password</label>
                        <input
                            type="password"
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                            placeholder="••••••••"
                            required
                        />
                    </div>

                    {error && <p style={{ color: '#ef4444', marginBottom: '1rem' }}>{error}</p>}

                    <button type="submit" className="btn">
                        <LogIn size={18} style={{ marginRight: '8px', verticalAlign: 'middle' }} />
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    );
};

export default LoginPage;
