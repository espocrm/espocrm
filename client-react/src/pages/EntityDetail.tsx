import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useMetadata } from '../hooks/useMetadata';
import Field from '../components/fields/Field';
import { ArrowLeft, Edit2, MoreHorizontal, Save, X } from 'lucide-react';

const EntityDetail: React.FC = () => {
    const { entityType, id } = useParams<{ entityType: string, id: string }>();
    const navigate = useNavigate();
    const { getMetadata, translate } = useMetadata();
    const [data, setData] = useState<Record<string, any> | null>(null);
    const [editData, setEditData] = useState<Record<string, any> | null>(null);
    const [isLoading, setIsLoading] = useState(true);
    const [isEditing, setIsEditing] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const detailLayout = getMetadata(`entityDefs.${entityType}.layouts.detail`) || [];

    const fetchData = useCallback(async () => {
        setIsLoading(true);
        try {
            const response = await api.get(`/api/v1/${entityType}/${id}`);
            setData(response.data);
            setEditData(response.data);
        } catch (e) {
            console.error('Failed to fetch detail', e);
        } finally {
            setIsLoading(false);
        }
    }, [entityType, id]);

    useEffect(() => {
        if (entityType && id) {
            fetchData();
        }
    }, [entityType, id, fetchData]);

    const handleSave = async () => {
        if (!editData) return;
        setIsSaving(true);
        try {
            await api.put(`/api/v1/${entityType}/${id}`, editData);
            setData(editData);
            setIsEditing(false);
        } catch (e) {
            console.error('Failed to save record', e);
        } finally {
            setIsSaving(false);
        }
    };

    const handleFieldChange = (name: string, value: any) => {
        setEditData((prev: any) => ({ ...prev, [name]: value }));
    };

    if (isLoading) return <div style={{ padding: '2rem' }}>Loading...</div>;
    if (!data || !editData) return <div style={{ padding: '2rem' }}>Record not found</div>;

    return (
        <div className="entity-detail">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                    <button
                        onClick={() => navigate(-1)}
                        style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}
                    >
                        <ArrowLeft size={20} />
                    </button>
                    <h1 style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>{data.name || id}</h1>
                </div>
                <div style={{ display: 'flex', gap: '1rem' }}>
                    {isEditing ? (
                        <>
                            <button
                                className="btn"
                                onClick={handleSave}
                                disabled={isSaving}
                                style={{ width: 'auto', background: 'var(--primary)' }}
                            >
                                <Save size={16} style={{ marginRight: '8px' }} /> {isSaving ? 'Saving...' : 'Save'}
                            </button>
                            <button
                                className="btn"
                                onClick={() => { setIsEditing(false); setEditData(data); }}
                                style={{ width: 'auto', background: 'var(--bg-card)', border: '1px solid var(--border)' }}
                            >
                                <X size={16} style={{ marginRight: '8px' }} /> Cancel
                            </button>
                        </>
                    ) : (
                        <>
                            <button
                                className="btn"
                                onClick={() => setIsEditing(true)}
                                style={{ width: 'auto', background: 'var(--bg-card)', border: '1px solid var(--border)' }}
                            >
                                <Edit2 size={16} style={{ marginRight: '8px' }} /> Edit
                            </button>
                            <button className="btn" style={{ width: 'auto', background: 'var(--bg-card)', border: '1px solid var(--border)' }}>
                                <MoreHorizontal size={16} />
                            </button>
                        </>
                    )}
                </div>
            </div>

            <div className="layout">
                {detailLayout.map((panel: any, idx: number) => (
                    <div key={idx} className="card glass" style={{ marginBottom: '2rem' }}>
                        <div style={{ padding: '1rem', borderBottom: '1px solid var(--border)', fontWeight: 600, color: 'var(--primary)' }}>
                            {translate(panel.label || panel.name, 'labels', entityType)}
                        </div>
                        <div style={{ padding: '1.5rem' }}>
                            {panel.rows.map((row: any, rowIdx: number) => (
                                <div key={rowIdx} style={{ display: 'flex', gap: '2rem', marginBottom: '1.5rem' }}>
                                    {row.map((col: any, colIdx: number) => (
                                        <div key={colIdx} style={{ flex: 1 }}>
                                            {col !== false && (
                                                <>
                                                    <label style={{ display: 'block', fontSize: '0.75rem', color: 'var(--text-muted)', marginBottom: '0.5rem' }}>
                                                        {translate(col.name, 'fields', entityType)}
                                                    </label>
                                                    <Field
                                                        entityType={entityType!}
                                                        name={col.name}
                                                        value={isEditing ? editData[col.name] : data[col.name]}
                                                        mode={isEditing ? 'edit' : 'detail'}
                                                        onChange={(val: any) => handleFieldChange(col.name, val)}
                                                    />
                                                </>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default EntityDetail;
