import React from 'react';
import { Link } from 'react-router-dom';
import ImageField from './ImageField';

export type FieldMode = 'detail' | 'edit' | 'list' | 'search';

export interface FieldProps {
    entityType: string;
    name: string;
    value: any; // eslint-disable-line @typescript-eslint/no-explicit-any
    mode: FieldMode;
    onChange?: (value: any) => void; // eslint-disable-line @typescript-eslint/no-explicit-any
    params?: Record<string, any>; // eslint-disable-line @typescript-eslint/no-explicit-any
}

export const VarcharField: React.FC<FieldProps> = ({ value, mode, onChange }) => {
    if (mode === 'edit' || mode === 'search') {
        return (
            <input
                type="text"
                className="input-group input"
                style={{ marginBottom: 0 }}
                value={value || ''}
                onChange={e => onChange?.(e.target.value)}
            />
        );
    }
    return <span>{value || ''}</span>;
};

export const BoolField: React.FC<FieldProps> = ({ value, mode, onChange }) => {
    if (mode === 'edit' || mode === 'search') {
        return (
            <input
                type="checkbox"
                checked={!!value}
                onChange={e => onChange?.(e.target.checked)}
            />
        );
    }
    return <span>{value ? 'Yes' : 'No'}</span>;
};

export const EnumField: React.FC<FieldProps> = ({ value, mode, onChange, params }) => {
    const options = params?.options || [];

    if (mode === 'edit' || mode === 'search') {
        return (
            <select
                className="input-group input"
                style={{ marginBottom: 0 }}
                value={value || ''}
                onChange={e => onChange?.(e.target.value)}
            >
                <option value=""></option>
                {options.map((opt: string) => (
                    <option key={opt} value={opt}>{opt}</option>
                ))}
            </select>
        );
    }
    return <span>{value || ''}</span>;
};

export const LinkField: React.FC<FieldProps> = ({ value, mode, params, name, onChange }) => {
    const linkId = value;

    if (mode === 'edit' || mode === 'search') {
        return (
            <input
                type="text"
                className="input-group input"
                style={{ marginBottom: 0 }}
                placeholder="ID..."
                value={linkId || ''}
                onChange={e => onChange?.(e.target.value)}
            />
        );
    }

    if (mode === 'list' || mode === 'detail') {
        if (!linkId) return <span>-</span>;
        return (
            <Link to={`/${params?.entity || name}/view/${linkId}`} style={{ color: 'var(--primary)', textDecoration: 'none' }}>
                {String(linkId)}
            </Link>
        );
    }

    return <span>{linkId || ''}</span>;
};

export const FieldRenderer: React.FC<FieldProps & { type?: string }> = (props) => {
    const { type, mode } = props;

    let field;
    switch (type) {
        case 'bool':
            field = <BoolField {...props} />;
            break;
        case 'enum':
            field = <EnumField {...props} />;
            break;
        case 'link':
            field = <LinkField {...props} />;
            break;
        case 'image':
            field = <ImageField value={props.value as string} mode={props.mode} onChange={props.onChange} />;
            break;
        case 'varchar':
        default:
            field = <VarcharField {...props} />;
            break;
    }

    if (mode === 'search') {
        // In search mode, we might want to eventually add an operator selector.
        // For now, let's keep it simple.
        return field;
    }

    return field;
};
