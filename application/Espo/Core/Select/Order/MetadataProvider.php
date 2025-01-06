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

namespace Espo\Core\Select\Order;

use Espo\Core\Utils\Metadata;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\EntityManager;

class MetadataProvider
{
    public function __construct(private Metadata $metadata, private EntityManager $entityManager)
    {}

    public function getFieldType(string $entityType, string $field): ?string
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'fields', $field, FieldParam::TYPE
        ]) ?? null;
    }

    public function getDefaultOrderBy(string $entityType): ?string
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'orderBy'
        ]) ?? null;
    }

    public function getDefaultOrder(string $entityType): ?string
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'order'
        ]) ?? null;
    }

    public function isFieldOrderDisabled(string $entityType, string $field): bool
    {
        return $this->metadata->get("entityDefs.$entityType.fields.$field.orderDisabled") ?? false;
    }

    public function hasAttribute(string $entityType, string $attribute): bool
    {
        return $this->entityManager
            ->getMetadata()
            ->getDefs()
            ->getEntity($entityType)
            ->hasAttribute($attribute);
    }

    public function isAttributeParamUniqueTrue(string $entityType, string $attribute): bool
    {
        return (bool) $this->entityManager
            ->getMetadata()
            ->getDefs()
            ->getEntity($entityType)
            ->getAttribute($attribute)
            ->getParam('unique');
    }
}
