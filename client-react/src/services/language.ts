import api from './api';

export type LanguageData = Record<string, Record<string, Record<string, unknown>>>;

class LanguageService {
    private data: LanguageData = {};
    private loadingPromise: Promise<LanguageData> | null = null;

    async load(): Promise<LanguageData> {
        if (Object.keys(this.data).length > 0) return this.data;
        if (this.loadingPromise) return this.loadingPromise;

        const cached = localStorage.getItem('language');
        if (cached) {
            this.data = JSON.parse(cached);
            return this.data;
        }

        this.loadingPromise = (async () => {
            try {
                const response = await api.get('/api/v1/I18n');
                this.data = response.data;
                localStorage.setItem('language', JSON.stringify(this.data));
                return this.data;
            } finally {
                this.loadingPromise = null;
            }
        })();

        return this.loadingPromise;
    }

    get(scope: string, category: string, name: string): any {
        if (this.data[scope]?.[category]?.[name] !== undefined) {
            return this.data[scope][category][name];
        }
        if (scope !== 'Global' && this.data['Global']?.[category]?.[name] !== undefined) {
            return this.data['Global'][category][name];
        }
        return scope === 'Global' ? name : false;
    }

    translate(name: string, category: string = 'labels', scope: string = 'Global'): string {
        const res = this.get(scope, category, name);
        return typeof res === 'string' ? res : name;
    }

    translateOption(value: string, field: string, scope: string = 'Global'): string {
        const translation = this.get(scope, 'options', field);
        if (typeof translation === 'object' && translation !== null && (translation as Record<string, string>)[value]) {
            return (translation as Record<string, string>)[value];
        }
        return value;
    }

    sortFieldList(scope: string, fieldList: string[]): string[] {
        return [...fieldList].sort((a, b) => {
            const la = this.translate(a, 'fields', scope);
            const lb = this.translate(b, 'fields', scope);
            return la.localeCompare(lb);
        });
    }

    sortEntityList(entityList: string[], plural: boolean = false): string[] {
        const category = plural ? 'scopeNamesPlural' : 'scopeNames';
        return [...entityList].sort((a, b) => {
            const la = this.translate(a, category);
            const lb = this.translate(b, category);
            return la.localeCompare(lb);
        });
    }
}

export const languageService = new LanguageService();
