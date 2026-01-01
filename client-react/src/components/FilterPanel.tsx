import React, { useState } from 'react';
import { useMetadata } from '../hooks/useMetadata';
import Field from './fields/Field';
import { Plus, X } from 'lucide-react';
import type { FilterItem } from '../services/SearchManager';
import { SearchManager } from '../services/SearchManager';

interface FilterPanelProps {
    entityType: string;
    onApply: (filters: FilterItem[]) => void;
    onReset: () => void;
}

const FilterPanel: React.FC<FilterPanelProps> = ({ entityType, onApply, onReset }) => {
    const { getMetadata, getFieldMetadata, translate } = useMetadata();
    const [activeFilters, setActiveFilters] = useState<FilterItem[]>([]);

    // Get filterable fields from metadata
    const filterLayout = getMetadata(`entityDefs.${entityType}.layouts.filters`) || [];
    const filterableFields = filterLayout.map((f: Record<string, unknown> | string) => {
        if (typeof f === 'string') return f;
        return (f.name as string) || '';
    });

    const availableFields = filterableFields.filter(
        (field: string) => !activeFilters.find(f => f.field === field)
    );

    const handleAddFilter = (field: string) => {
        const fieldMeta = getFieldMetadata(entityType, field);
        const type = SearchManager.getDefaultSearchType(fieldMeta?.type || 'varchar');
        setActiveFilters([...activeFilters, { field, type, value: '' }]);
    };

    const handleRemoveFilter = (index: number) => {
        const newFilters = [...activeFilters];
        newFilters.splice(index, 1);
        setActiveFilters(newFilters);
    };

    const handleValueChange = (index: number, value: unknown) => {
        const newFilters = [...activeFilters];
        newFilters[index].value = value;
        setActiveFilters(newFilters);
    };

    const handleOperatorChange = (index: number, type: string) => {
        const newFilters = [...activeFilters];
        newFilters[index].type = type;
        setActiveFilters(newFilters);
    };

    return (
        <div className="card glass" style={{ marginBottom: '1rem', padding: '1rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 600 }}>Advanced Filters</h3>
                <div style={{ display: 'flex', gap: '0.5rem' }}>
                    <button className="btn" style={{ padding: '0.5rem 1rem' }} onClick={() => onApply(activeFilters)}>Apply</button>
                    <button className="btn" style={{ padding: '0.5rem 1rem', background: 'transparent', border: '1px solid var(--border)' }} onClick={() => { setActiveFilters([]); onReset(); }}>Reset</button>
                </div>
            </div>

            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '1rem' }}>
                {activeFilters.map((filter, index) => {
                    const fieldMeta = getFieldMetadata(entityType, filter.field);
                    const operators = SearchManager.getOperators(fieldMeta?.type || 'varchar');

                    return (
                        <div key={filter.field} className="card" style={{ padding: '0.5rem', background: 'rgba(255,255,255,0.05)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <span style={{ fontSize: '0.875rem', color: 'var(--text-muted)', whiteSpace: 'nowrap' }}>
                                {translate(filter.field, 'fields', entityType)}:
                            </span>

                            <select
                                value={filter.type}
                                onChange={(e) => handleOperatorChange(index, e.target.value)}
                                style={{
                                    background: 'transparent',
                                    border: '1px solid var(--border)',
                                    color: 'white',
                                    fontSize: '0.75rem',
                                    padding: '0.2rem',
                                    borderRadius: '4px',
                                    outline: 'none'
                                }}
                            >
                                {operators.map(op => (
                                    <option key={op} value={op} style={{ background: 'var(--bg-card)' }}>
                                        {op}
                                    </option>
                                ))}
                            </select>

                            <div style={{ width: '150px' }}>
                                <Field
                                    entityType={entityType}
                                    name={filter.field}
                                    value={filter.value}
                                    mode="search"
                                    onChange={(val) => handleValueChange(index, val)}
                                />
                            </div>
                            <button
                                onClick={() => handleRemoveFilter(index)}
                                style={{ background: 'transparent', border: 'none', color: 'var(--text-muted)', cursor: 'pointer', display: 'flex', alignItems: 'center' }}
                            >
                                <X size={14} />
                            </button>
                        </div>
                    );
                })}

                {availableFields.length > 0 && (
                    <div className="dropdown" style={{ position: 'relative' }}>
                        <button className="btn" style={{ padding: '0.5rem 1rem', background: 'rgba(255,255,255,0.1)', display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                            <Plus size={16} /> Add Filter
                        </button>
                        <div className="card glass" style={{ position: 'absolute', top: '100%', left: 0, zIndex: 10, minWidth: '200px', padding: '0.5rem', marginTop: '0.5rem', display: 'none' }}>
                            {/* Simple dropdown simulation for now */}
                        </div>
                        {/* React implementation of a simple select for adding filters */}
                        <select
                            style={{ position: 'absolute', top: 0, left: 0, width: '100%', height: '100%', opacity: 0, cursor: 'pointer' }}
                            onChange={(e) => { if (e.target.value) handleAddFilter(e.target.value); e.target.value = ''; }}
                        >
                            <option value="">Add Filter...</option>
                            {availableFields.map((field: string) => (
                                <option key={field} value={field}>
                                    {translate(field, 'fields', entityType)}
                                </option>
                            ))}
                        </select>
                    </div>
                )}
            </div>
        </div>
    );
};

export default FilterPanel;
