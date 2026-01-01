import React, { useState } from 'react';
import api from '../../services/api';
import { Upload, Loader } from 'lucide-react';

interface ImageFieldProps {
    value: string | null;
    mode: 'detail' | 'edit' | 'list' | 'search';
    onChange?: (value: string | null) => void;
}

const ImageField: React.FC<ImageFieldProps> = ({ value, mode, onChange }) => {
    const [isUploading, setIsUploading] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(
        value ? `/api/v1/Attachment/${value}/download` : null
    );

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploading(true);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('role', 'Attachment');

        try {
            const response = await api.post('/api/v1/Attachment', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            const attachmentId = response.data.id;
            setPreviewUrl(`/api/v1/Attachment/${attachmentId}/download`);
            onChange?.(attachmentId);
        } catch (error) {
            console.error('Upload failed', error);
        } finally {
            setIsUploading(false);
        }
    };

    const handleClear = () => {
        setPreviewUrl(null);
        onChange?.(null);
    };

    if (mode === 'edit') {
        return (
            <div className="image-field-edit" style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
                <div
                    className="image-preview"
                    style={{
                        width: '80px',
                        height: '80px',
                        borderRadius: '8px',
                        background: 'rgba(255,255,255,0.05)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        overflow: 'hidden',
                        border: '1px solid var(--border)',
                        position: 'relative'
                    }}
                >
                    {previewUrl ? (
                        <img src={previewUrl} alt="Preview" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                    ) : (
                        <Upload size={24} style={{ color: 'var(--text-muted)' }} />
                    )}
                    {isUploading && (
                        <div style={{ position: 'absolute', inset: 0, background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyItems: 'center' }}>
                            <Loader className="animate-spin" size={20} style={{ margin: 'auto' }} />
                        </div>
                    )}
                </div>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                    <label className="btn" style={{ fontSize: '0.75rem', padding: '0.25rem 0.75rem', cursor: 'pointer', width: 'auto' }}>
                        {previewUrl ? 'Change' : 'Upload'}
                        <input type="file" accept="image/*" style={{ display: 'none' }} onChange={handleFileChange} />
                    </label>
                    {previewUrl && (
                        <button className="btn" style={{ fontSize: '0.75rem', padding: '0.25rem 0.75rem', background: 'transparent', color: 'var(--danger)', border: '1px solid var(--danger)', width: 'auto' }} onClick={handleClear}>
                            Remove
                        </button>
                    )}
                </div>
            </div>
        );
    }

    if (previewUrl) {
        return (
            <div style={{ width: mode === 'list' ? '40px' : '150px', height: mode === 'list' ? '40px' : '150px', borderRadius: '4px', overflow: 'hidden' }}>
                <img src={previewUrl} alt="Image" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            </div>
        );
    }

    return <span style={{ color: 'var(--text-muted)' }}>No image</span>;
};

export default ImageField;
