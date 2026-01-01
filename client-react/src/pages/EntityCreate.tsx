import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useMetadata } from '../hooks/useMetadata';
import Field from '../components/fields/Field';
import { ArrowLeft, Save, X } from 'lucide-react';

const EntityCreate: React.FC = () => {
    const { entityType } = useParams<{ entityType: string }>();
    const navigate = useNavigate();
    const { getMetadata, translate } = useMetadata();
    const [data, setData] = useState<Record<string, any>>({});
    const [isSaving, setIsSaving] = useState(false);

    const editLayout = getMetadata(`entityDefs.${entityType}.layouts.edit`) ||
        getMetadata(`entityDefs.${entityType}.layouts.detail`) || [];

    const handleSave = async () => {
        setIsSaving(true);
        try {
            const response = await api.post(`/api/v1/${entityType}`, data);
            navigate(`/${entityType}/view/${response.data.id}`);
        } catch (e) {
            console.error('Failed to create record', e);
        } finally {
            setIsSaving(false);
        }
    };

    const handleFieldChange = (name: string, value: any) => {
        setData(prev => ({ ...prev, [name]: value }));
    };

    return (
        <div className="entity-create">
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                    <button
                        onClick={() => navigate(-1)}
                        style={{ background: 'none', border: 'none', color: 'var(--text-muted)', cursor: 'pointer' }}
                    >
                        <ArrowLeft size={20} />
                    </button>
                    <h1 style={{ fontSize: '1.5rem', fontWeight: 'bold' }}>Create {entityType}</h1>
                </div>
                <div style={{ display: 'flex', gap: '1rem' }}>
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
                        onClick={() => navigate(-1)}
                        style={{ width: 'auto', background: 'var(--bg-card)', border: '1px solid var(--border)' }}
                    >
                        <X size={16} style={{ marginRight: '8px' }} /> Cancel
                    </button>
                </div>
            </div>

            <div className="layout">
                {editLayout.map((panel: any, idx: number) => (
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
                                                        value={data[col.name]}
                                                        mode="edit"
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

export default EntityCreate;
