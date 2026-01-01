import { useAppContext } from '../context/AppContext';
import { languageService } from '../services/language';

export const useMetadata = () => {
    const { metadata, language } = useAppContext();

    const getMetadata = (path: string, defaultValue: any = null) => {
        if (!metadata) return defaultValue;
        const parts = path.split('.');
        let current: any = metadata;
        for (const part of parts) {
            if (current === null || typeof current !== 'object' || !(part in current)) {
                return defaultValue;
            }
            current = current[part];
        }
        return current;
    };

    const getEntityMetadata = (entityType: string) => {
        return getMetadata(`entityDefs.${entityType}`);
    };

    const getFieldMetadata = (entityType: string, fieldName: string) => {
        return getMetadata(`entityDefs.${entityType}.fields.${fieldName}`);
    };

    const translate = (label: string, category: string = 'labels', scope: string = 'Global') => {
        if (!language) return label;
        return languageService.translate(label, category, scope);
    };

    const translateOption = (value: string, field: string, scope: string = 'Global') => {
        if (!language) return value;
        return languageService.translateOption(value, field, scope);
    };

    return { getMetadata, getEntityMetadata, getFieldMetadata, translate, translateOption };
};
