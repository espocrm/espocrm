import api from './api';

export interface Metadata {
    scopes: Record<string, any>;
    fields: Record<string, any>;
    dashlets: Record<string, any>;
    [key: string]: any;
}

class MetadataService {
    private data: Metadata | null = null;
    private loadingPromise: Promise<Metadata> | null = null;

    async load(): Promise<Metadata> {
        if (this.data) return this.data;
        if (this.loadingPromise) return this.loadingPromise;

        const cached = localStorage.getItem('metadata');
        if (cached) {
            this.data = JSON.parse(cached);
            return this.data!;
        }

        this.loadingPromise = (async () => {
            try {
                const response = await api.get('/api/v1/Metadata');
                this.data = response.data;
                localStorage.setItem('metadata', JSON.stringify(this.data));
                return this.data!;
            } finally {
                this.loadingPromise = null;
            }
        })();

        return this.loadingPromise;
    }

    get(path: string | string[], defaultValue: any = null): any {
        if (!this.data) return defaultValue;

        const parts = Array.isArray(path) ? path : path.split('.');
        let current: any = this.data;

        for (const part of parts) {
            if (current === null || typeof current !== 'object' || !(part in current)) {
                return defaultValue;
            }
            current = current[part];
        }

        return current ?? defaultValue;
    }

    getScopeList(): string[] {
        const scopes = this.get('scopes') || {};
        return Object.keys(scopes).filter(scope => !scopes[scope].disabled);
    }

    getScopeObjectList(): string[] {
        const scopes = this.get('scopes') || {};
        return Object.keys(scopes).filter(scope => !scopes[scope].disabled && scopes[scope].object);
    }

    getScopeEntityList(): string[] {
        const scopes = this.get('scopes') || {};
        return Object.keys(scopes).filter(scope => !scopes[scope].disabled && scopes[scope].entity);
    }

    clearCache() {
        localStorage.removeItem('metadata');
        this.data = null;
    }
}

export const metadataService = new MetadataService();
