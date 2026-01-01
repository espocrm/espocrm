import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useMetadata } from '../hooks/useMetadata';
import Field from '../components/fields/Field';
import { ChevronRight, Search as SearchIcon, Filter } from 'lucide-react';
import FilterPanel from '../components/FilterPanel';
import type { FilterItem } from '../services/SearchManager';
import { SearchManager } from '../services/SearchManager';

const EntityList: React.FC = () => {
    const { entityType } = useParams<{ entityType: string }>();
    const navigate = useNavigate();
    const { getMetadata, translate } = useMetadata();
    const [list, setList] = useState<any[]>([]); // eslint-disable-line @typescript-eslint/no-explicit-any

    const [isLoading, setIsLoading] = useState(true);
    const [total, setTotal] = useState(0);
    const [showFilters, setShowFilters] = useState(false);
    const [searchText, setSearchText] = useState('');
    const [activeFilters, setActiveFilters] = useState<FilterItem[]>([]);

    const listLayout = getMetadata(`clientDefs.${entityType}.recordViews.list.layout`) ||
        getMetadata(`entityDefs.${entityType}.layouts.list`) || [];

    const fetchData = useCallback(async () => {
        setIsLoading(true);
        try {
            const textFilterFields = getMetadata(`entityDefs.${entityType}.collection.textFilterFields`) || ['name'];
            const where = SearchManager.formatWhere(activeFilters, searchText, textFilterFields as string[]);
            const response = await api.get(`/api/v1/${entityType}`, {
                params: {
                    where: where,
                    maxSize: 20
                }
            });
            setList(response.data.list);
            setTotal(response.data.total);
        } catch (e) {
            console.error('Failed to fetch list', e);
        } finally {
            setIsLoading(false);
        }
    }, [entityType, activeFilters, searchText, getMetadata]);

    useEffect(() => {
        if (entityType) {
            fetchData();
        }
    }, [entityType, fetchData]);

    const handleSearchKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            fetchData();
        }
    };

    return (
        <div className="entity-list">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
                <h1 style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>{translate(entityType || '', 'scopeNames')}</h1>
                <div style={{ display: 'flex', gap: '1rem' }}>
                    <div className="input-group" style={{ marginBottom: 0 }}>
                        <div style={{ position: 'relative' }}>
                            <SearchIcon size={16} style={{ position: 'absolute', left: '12px', top: '50%', transform: 'translateY(-50%)', color: 'var(--text-muted)' }} />
                            <input
                                type="text"
                                placeholder="Search..."
                                style={{ paddingLeft: '2.5rem', width: '250px' }}
                                value={searchText}
                                onChange={(e) => setSearchText(e.target.value)}
                                onKeyDown={handleSearchKeyDown}
                            />
                        </div>
                    </div>
                    <button
                        className="btn"
                        style={{ width: 'auto', padding: '0.75rem', background: showFilters ? 'var(--primary)' : 'rgba(255,255,255,0.1)' }}
                        onClick={() => setShowFilters(!showFilters)}
                        title="Filters"
                    >
                        <Filter size={18} />
                    </button>
                    <button
                        className="btn"
                        style={{ width: 'auto', padding: '0.75rem 1.5rem' }}
                        onClick={() => navigate(`/${entityType}/create`)}
                    >
                        Create {translate(entityType || '', 'scopeNamesSingle')}
                    </button>
                </div>
            </div>

            {showFilters && (
                <FilterPanel
                    entityType={entityType!}
                    onApply={(filters) => { setActiveFilters(filters); }}
                    onReset={() => { setActiveFilters([]); setSearchText(''); }}
                />
            )}

            <div className="card glass" style={{ overflow: 'hidden', padding: 0 }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                        <tr style={{ background: 'rgba(255,255,255,0.05)', textAlign: 'left' }}>
                            {listLayout.map((col: any) => ( // eslint-disable-line @typescript-eslint/no-explicit-any
                                <th key={String(col.name)} style={{ padding: '1rem', color: 'var(--text-muted)', fontSize: '0.875rem', fontWeight: 600 }}>
                                    {translate(String(col.name), 'fields', entityType)}
                                </th>
                            ))}
                            <th style={{ width: '50px' }}></th>
                        </tr>
                    </thead>
                    <tbody>
                        {isLoading ? (
                            <tr><td colSpan={listLayout.length + 1} style={{ padding: '2rem', textAlign: 'center' }}>Loading...</td></tr>
                        ) : list.length === 0 ? (
                            <tr><td colSpan={listLayout.length + 1} style={{ padding: '2rem', textAlign: 'center' }}>No records found</td></tr>
                        ) : (
                            list.map(item => (
                                <tr key={String(item.id)} style={{ borderBottom: '1px solid var(--border)' }}>
                                    {listLayout.map((col: any) => ( // eslint-disable-line @typescript-eslint/no-explicit-any
                                        <td key={String(col.name)} style={{ padding: '1rem' }}>
                                            <Field
                                                entityType={entityType!}
                                                name={String(col.name)}
                                                value={item[col.name]}
                                                mode="list"
                                            />
                                        </td>
                                    ))}
                                    <td style={{ padding: '1rem' }}>
                                        <Link to={`/${entityType}/view/${item.id}`} style={{ color: 'var(--text-muted)' }}>
                                            <ChevronRight size={18} />
                                        </Link>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            <div style={{ marginTop: '1rem', color: 'var(--text-muted)', fontSize: '0.875rem' }}>
                Total: {total} records
            </div>
        </div>
    );
};

export default EntityList;

