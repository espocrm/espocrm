import React from 'react';
import { useMetadata } from '../../hooks/useMetadata';
import type { FieldMode } from './Fields';
import { FieldRenderer } from './Fields';

interface FieldProps {
    entityType: string;
    name: string;
    value: any;
    mode: FieldMode;
    onChange?: (value: any) => void;
}

const Field: React.FC<FieldProps> = (props) => {
    const { getFieldMetadata } = useMetadata();
    const fieldMeta = getFieldMetadata(props.entityType, props.name) || {};
    const type = fieldMeta.type || 'varchar';

    return (
        <div className={`field field-type-${type} field-name-${props.name}`}>
            <FieldRenderer {...props} type={type} params={fieldMeta} />
        </div>
    );
};

export default Field;
