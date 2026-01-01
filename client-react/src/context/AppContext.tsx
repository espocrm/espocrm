import React, { createContext, useContext, useEffect, useState } from 'react';
import { authService } from '../services/auth';
import type { User } from '../services/auth';
import { metadataService } from '../services/metadata';
import type { Metadata } from '../services/metadata';
import { languageService } from '../services/language';
import type { LanguageData } from '../services/language';

interface AppContextType {
    user: User | null;
    metadata: Metadata | null;
    language: LanguageData | null;
    isLoading: boolean;
    login: (u: string, p: string) => Promise<void>;
    logout: () => Promise<void>;
}

const AppContext = createContext<AppContextType | undefined>(undefined);

export const AppProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [user, setUser] = useState<User | null>(null);
    const [metadata, setMetadata] = useState<Metadata | null>(null);
    const [language, setLanguage] = useState<LanguageData | null>(null);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const init = async () => {
            try {
                const u = await authService.checkAuth();
                if (u) {
                    setUser(u);
                    const [m, l] = await Promise.all([
                        metadataService.load(),
                        languageService.load()
                    ]);
                    setMetadata(m);
                    setLanguage(l);
                }
            } catch (e) {
                console.error('Initialization failed', e);
            } finally {
                setIsLoading(false);
            }
        };
        init();
    }, []);

    const login = async (u: string, p: string) => {
        const user = await authService.login(u, p);
        setUser(user);
        const [meta, lang] = await Promise.all([
            metadataService.load(),
            languageService.load()
        ]);
        setMetadata(meta);
        setLanguage(lang);
    };

    const logout = async () => {
        await authService.logout();
        setUser(null);
        setMetadata(null);
        setLanguage(null);
        localStorage.removeItem('language');
    };

    return (
        <AppContext.Provider value={{ user, metadata, language, isLoading, login, logout }}>
            {children}
        </AppContext.Provider>
    );
};

export const useAppContext = () => {
    const context = useContext(AppContext);
    if (context === undefined) {
        throw new Error('useAppContext must be used within an AppProvider');
    }
    return context;
};
