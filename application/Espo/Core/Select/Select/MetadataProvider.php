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

namespace Espo\Core\Select\Select;

use Espo\Core\{
    Utils\Metadata,
};

use Espo\{
    ORM\EntityManager,
};

class MetadataProvider
{
    private $metadata;

    private $entityManager;

    public function __construct(Metadata $metadata, EntityManager $entityManager)
    {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
    }

    public function getDefaultOrderBy(string $entityType): ?string
    {
        return $this->metadata->get([
            'entityDefs', $entityType, 'collection', 'orderBy'
        ]) ?? null;
    }

    public function getSelectAttributesDependencyMap(string $entityType): ?array
    {
        return $this->metadata->get([
            'selectDefs', $entityType, 'selectAttributesDependencyMap'
        ]) ?? null;
    }

    public function getAclPortalAttributeList(string $entityType): ?array
    {
        return $this->metadata->get([
            'selectDefs', $entityType, 'aclPortalAttributeList'
        ]) ?? null;
    }

    public function getAclAttributeList(string $entityType): ?array
    {
        return $this->metadata->get([
            'selectDefs', $entityType, 'aclAttributeList'
        ]) ?? null;
    }

    public function hasAttribute(string $entityType, string $attribute): bool
    {
        return $this->entityManager
            ->getMetadata()
            ->getDefs()
            ->getEntity($entityType)
            ->hasAttribute($attribute);
    }

    public function isAttributeNotStorable(string $entityType, string $attribute): bool
    {
        return $this->entityManager
            ->getMetadata()
            ->getDefs()
            ->getEntity($entityType)
            ->getAttribute($attribute)
            ->isNotStorable();
    }

    public function getAttributeType(string $entityType, string $attribute): string
    {
        return $this->entityManager
            ->getMetadata()
            ->getDefs()
            ->getEntity($entityType)
            ->getAttribute($attribute)
            ->getType();
    }
}
