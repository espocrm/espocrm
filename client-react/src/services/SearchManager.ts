export type FilterItem = {
    field: string;
    type: string;
    value: unknown;
};

export class SearchManager {
    static formatWhere(filters: FilterItem[], textFilter?: string, textFilterFields?: string[]): string {
        const where: unknown[] = [];

        if (textFilter && textFilterFields && textFilterFields.length > 0) {
            const orConditions = textFilterFields.map(field => ({
                type: 'contains',
                field: field,
                value: textFilter
            }));
            where.push({
                type: 'or',
                value: orConditions
            });
        }

        filters.forEach(filter => {
            if (filter.value !== undefined && filter.value !== null && filter.value !== '') {
                let value = filter.value;
                const type = filter.type;

                // Handle 'in' operator for comma-separated values
                if (type === 'in' && typeof value === 'string') {
                    value = value.split(',').map(item => item.trim());
                }

                where.push({
                    ...filter,
                    type,
                    value
                });
            }
        });

        return JSON.stringify(where);
    }

    static getDefaultSearchType(fieldType: string): string {
        switch (fieldType) {
            case 'varchar':
            case 'text':
                return 'startsWith';
            case 'enum':
            case 'link':
                return 'equals';
            case 'bool':
                return 'isTrue';
            case 'int':
            case 'float':
            case 'currency':
                return 'equals';
            default:
                return 'equals';
        }
    }

    static getOperators(fieldType: string): string[] {
        switch (fieldType) {
            case 'varchar':
            case 'text':
                return ['startsWith', 'contains', 'equals', 'notEquals'];
            case 'int':
            case 'float':
            case 'currency':
                return ['equals', 'notEquals', 'greaterThan', 'lessThan', 'greaterThanOrEquals', 'lessThanOrEquals'];
            case 'enum':
            case 'link':
                return ['equals', 'notEquals', 'in', 'notIn'];
            default:
                return ['equals', 'notEquals'];
        }
    }
}
