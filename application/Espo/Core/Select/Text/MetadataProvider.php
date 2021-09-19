<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Select\Text;

use Espo\Core\Utils\Metadata;

use Espo\ORM\Defs;

class MetadataProvider
{
    private $metadata;

    private $ormDefs;

    public function __construct(Metadata $metadata, Defs $ormDefs)
    {
        $this->metadata = $metadata;
        $this->ormDefs = $ormDefs;
    }

    public function getFullTextSearchOrderType(string $entityType): ?string
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'fullTextSearchOrderType'
        ]);
    }

    public function getTextFilterAttributeList(string $entityType): ?array
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'textFilterFields'
        ]);
    }

    public function isFieldNotStorable(string $entityType, string $field): bool
    {
        return (bool) $this->metadata->get([
            'entityDefs', $entityType, 'fields', $field, 'notStorable'
        ]);
    }

    public function isFullTextSearchSupportedForField(string $entityType, string $field): bool
    {
        $fieldType = $this->metadata->get([
            'entityDefs', $entityType, 'fields', $field, 'type'
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

    public function getUseContainsAttributeList(string $entityType): array
    {
        return $this->metadata->get([
            'selectDefs', $entityType, 'textFilterUseContainsAttributeList'
        ]) ?? [];
    }

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
            ->getParam('relation');
    }
}
