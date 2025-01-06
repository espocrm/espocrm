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

namespace Espo\Core\Select\Text;

use Espo\Core\Utils\Metadata;

use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\FieldParam;

class MetadataProvider
{
    private Defs $ormDefs;

    public function __construct(private Metadata $metadata, Defs $ormDefs)
    {
        $this->ormDefs = $ormDefs;
    }

    public function getFullTextSearchOrderType(string $entityType): ?string
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'fullTextSearchOrderType'
        ]);
    }

    /**
     * @return string[]|null
     */
    public function getTextFilterAttributeList(string $entityType): ?array
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'textFilterFields'
        ]);
    }

    public function isFieldNotStorable(string $entityType, string $field): bool
    {
        return (bool) $this->metadata->get([
            'entityDefs', $entityType, 'fields', $field, Defs\Params\FieldParam::NOT_STORABLE
        ]);
    }

    public function isFullTextSearchSupportedForField(string $entityType, string $field): bool
    {
        $fieldType = $this->metadata->get([
            'entityDefs', $entityType, 'fields', $field, FieldParam::TYPE
        ]);

        return (bool) $this->metadata->get([
            'fields', $fieldType, 'fullTextSearch'
        ]);
    }

    public function hasFullTextSearch(string $entityType): bool
    {
        return (bool) $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'fullTextSearch'
        ]);
    }

    /**
     * @return string[]
     */
    public function getUseContainsAttributeList(string $entityType): array
    {
        return $this->metadata->get([
            'selectDefs', $entityType, 'textFilterUseContainsAttributeList'
        ]) ?? [];
    }

    /**
     * @return string[]|null
     */
    public function getFullTextSearchColumnList(string $entityType): ?array
    {
        return $this->ormDefs
            ->getEntity($entityType)
            ->getParam('fullTextSearchColumnList');
    }

    public function getRelationType(string $entityType, string $link): string
    {
        return $this->ormDefs
            ->getEntity($entityType)
            ->getRelation($link)
            ->getType();
    }

    public function getAttributeType(string $entityType, string $attribute): string
    {
        return $this->ormDefs
            ->getEntity($entityType)
            ->getAttribute($attribute)
            ->getType();
    }

    public function getFieldType(string $entityType, string $field): ?string
    {
        $entityDefs = $this->ormDefs->getEntity($entityType);

        if (!$entityDefs->hasField($field)) {
            return null;
        }

        return $entityDefs->getField($field)->getType();
    }

    public function getRelationEntityType(string $entityType, string $link): ?string
    {
        $relationDefs = $this->ormDefs
            ->getEntity($entityType)
            ->getRelation($link);

        if (!$relationDefs->hasForeignEntityType()) {
            return null;
        }

        return $relationDefs->getForeignEntityType();
    }

    public function getAttributeRelationParam(string $entityType, string $attribute): ?string
    {
        return $this->ormDefs
            ->getEntity($entityType)
            ->getAttribute($attribute)
            ->getParam(AttributeParam::RELATION);
    }
}
