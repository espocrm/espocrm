import api from './api';

export interface User {
    id: string;
    userName: string;
    type: string;
    preferences?: Record<string, unknown>;
    [key: string]: unknown;
}

class AuthService {
    private user: User | null = null;
    private checkingAuth: Promise<User | null> | null = null;

    async login(userName: string, password: string): Promise<User> {
        const authString = btoa(`${userName}:${password}`);
        const response = await api.get('/api/v1/App/user', {
            headers: {
                'Authorization': `Basic ${authString}`
            }
        });

        this.user = response.data.user;
        localStorage.setItem('token', authString);
        localStorage.setItem('user', JSON.stringify(this.user));

        return this.user!;
    }

    async logout(): Promise<void> {
        this.user = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('metadata');
        localStorage.removeItem('language');
    }

    async checkAuth(): Promise<User | null> {
        if (this.checkingAuth) return this.checkingAuth;

        const token = localStorage.getItem('token');
        if (!token) return null;

        this.checkingAuth = (async () => {
            try {
                const response = await api.get('/api/v1/App/user');
                this.user = response.data.user;
                return this.user;
            } catch (e) {
                console.error('Auth check failed', e);
                await this.logout();
                return null;
            } finally {
                this.checkingAuth = null;
            }
        })();

        return this.checkingAuth;
    }

    getCurrentUser(): User | null {
        if (this.user) return this.user;
        const stored = localStorage.getItem('user');
        if (stored) {
            try {
                this.user = JSON.parse(stored);
                return this.user;
            } catch (e) {
                return null;
            }
        }
        return null;
    }
}

export const authService = new AuthService();
