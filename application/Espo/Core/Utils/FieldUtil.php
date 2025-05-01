<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils;

use Espo\ORM\Defs\Params\FieldParam;

class FieldUtil
{
    /** @var array<string, array<string, string[]>> */
    private $fieldByTypeListCache = [];

    public function __construct(private Metadata $metadata)
    {}

    /**
     * Get a field type.
     * @since 8.1.0
     */
    public function getFieldType(string $entityType, string $field): ?string
    {
        return $this->metadata->get("entityDefs.$entityType.fields.$field.type");
    }

    /**
     * @return string[]
     */
    private function getAttributeListByType(string $entityType, string $name, string $type): array
    {
        $fieldType = $this->getFieldType($entityType, $name);

        if (!$fieldType) {
            return [];
        }

        $defs = $this->metadata->get('fields.' . $fieldType);

        if (!$defs) {
            return [];
        }

        if (is_object($defs)) {
            $defs = get_object_vars($defs);
        }

        $fieldList = [];

        if (isset($defs[$type . 'Fields'])) {
            $list = $defs[$type . 'Fields'];

            $naming = 'suffix';

            if (isset($defs['naming'])) {
                $naming = $defs['naming'];
            }

            if ($naming == 'prefix') {
                foreach ($list as $f) {
                    if ($f === '') {
                        $fieldList[] = $name;
                    } else {
                        $fieldList[] = $f . ucfirst($name);
                    }
                }
            } else {
                foreach ($list as $f) {
                    $fieldList[] = $name . ucfirst($f);
                }
            }
        } else {
            if ($type == 'actual') {
                $fieldList[] = $name;
            }
        }

        return $fieldList;
    }

    /**
     * @return string[]
     */
    public function getAdditionalActualAttributeList(string $entityType, string $name): array
    {
        $attributeList = [];

        $list = $this->metadata->get(['entityDefs', $entityType, 'fields', $name, 'additionalAttributeList']);

        if (empty($list)) {
            return [];
        }

        $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $name, FieldParam::TYPE]);

        if (!$type) {
            return [];
        }

        $naming = $this->metadata->get(['fields', $type, 'naming'], 'suffix');

        if ($naming == 'prefix') {
            foreach ($list as $f) {
                $attributeList[] = $f . ucfirst($name);
            }
        } else {
            foreach ($list as $f) {
                $attributeList[] = $name . ucfirst($f);
            }
        }

        return $attributeList;
    }

    /**
     * Get storable attributes of a specific field.
     *
     * @return string[]
     */
    public function getActualAttributeList(string $entityType, string $field): array
    {
        return array_merge(
            $this->getAttributeListByType($entityType, $field, 'actual'),
            $this->getAdditionalActualAttributeList($entityType, $field)
        );
    }

    /**
     * Get non-storable attributes of a specific field.
     *
     * @return string[]
     */
    public function getNotActualAttributeList(string $entityType, string $field): array
    {
        return $this->getAttributeListByType($entityType, $field, 'notActual');
    }

    /**
     * Get attributes of a specific field.
     *
     * @return string[]
     */
    public function getAttributeList(string $entityType, string $field): array
    {
        return array_merge(
            $this->getActualAttributeList($entityType, $field),
            $this->getNotActualAttributeList($entityType, $field)
        );
    }

    /**
     * Get a list of fields of a specific type in an entity type.
     *
     * @return string[]
     */
    public function getFieldByTypeList(string $entityType, string $type): array
    {
        if (!array_key_exists($entityType, $this->fieldByTypeListCache)) {
            $this->fieldByTypeListCache[$entityType] = [];
        }

        if (!array_key_exists($type, $this->fieldByTypeListCache[$entityType])) {
            /** @var array<string, array<string, mixed>> $fieldDefs */
            $fieldDefs = $this->metadata->get(['entityDefs', $entityType, 'fields'], []);

            $list = [];

            foreach ($fieldDefs as $field => $defs) {
                if (isset($defs['type']) && $defs['type'] === $type) {
                    $list[] = $field;
                }
            }

            $this->fieldByTypeListCache[$entityType][$type] = $list;
        }

        return $this->fieldByTypeListCache[$entityType][$type];
    }

    /**
     * @return string[]
     */
    private function getFieldTypeAttributeListByType(string $fieldType, string $name, string $type): array
    {
        $defs = $this->metadata->get(['fields', $fieldType]);

        if (!$defs) {
            return [];
        }

        $attributeList = [];

        if (isset($defs[$type . 'Fields'])) {
            $list = $defs[$type . 'Fields'];

            $naming = 'suffix';

            if (isset($defs['naming'])) {
                $naming = $defs['naming'];
            }

            if ($naming == 'prefix') {
                foreach ($list as $f) {
                    $attributeList[] = $f . ucfirst($name);
                }
            } else {
                foreach ($list as $f) {
                    $attributeList[] = $name . ucfirst($f);
                }
            }
        } else {
            if ($type == 'actual') {
                $attributeList[] = $name;
            }
        }

        return $attributeList;
    }

    /**
     * Get an attribute list for a given field type and field name.
     *
     * @return string[]
     */
    public function getFieldTypeAttributeList(string $fieldType, string $name): array
    {
        return array_merge(
            $this->getFieldTypeAttributeListByType($fieldType, $name, 'actual'),
            $this->getFieldTypeAttributeListByType($fieldType, $name, 'notActual')
        );
    }

    /**
     * Get a list of fields in an entity type.
     *
     * @return string[]
     */
    public function getEntityTypeFieldList(string $entityType): array
    {
        /** @var string[] */
        return array_keys($this->metadata->get(['entityDefs', $entityType, 'fields'], []));
    }

    /**
     * @return mixed
     */
    public function getEntityTypeFieldParam(string $entityType, string $field, string $param)
    {
        return $this->metadata->get(['entityDefs', $entityType, 'fields', $field, $param]);
    }

    /**
     * @return string[]
     */
    public function getEntityTypeAttributeList(string $entityType): array
    {
        $attributeList = [];

        foreach ($this->getEntityTypeFieldList($entityType) as $field) {
            $attributeList = array_merge(
                $attributeList,
                $this->getAttributeList($entityType, $field)
            );
        }

        return $attributeList;
    }

    /**
     * Get a field an attribute belongs to.
     *
     * @since 9.1.0
     */
    public function getFieldOfAttribute(string $entityType, string $attribute): ?string
    {
        foreach ($this->getEntityTypeFieldList($entityType) as $field) {
            if (in_array($attribute, $this->getAttributeList($entityType, $field))) {
                return $field;
            }
        }

        return null;
    }
}
