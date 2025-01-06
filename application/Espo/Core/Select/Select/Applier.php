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

namespace Espo\Core\Select\Select;

use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\FieldUtil;

use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

class Applier
{
    /** @var string[] */
    private $aclAttributeList = [
        'assignedUserId',
        'createdById',
    ];

    /** @var string[] */
    private $aclPortalAttributeList = [
        'assignedUserId',
        'createdById',
        'contactId',
        'accountId',
    ];

    public function __construct(
        private string $entityType,
        private User $user,
        private FieldUtil $fieldUtil,
        private MetadataProvider $metadataProvider
    ) {}

    public function apply(QueryBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $attributeList = $this->getSelectAttributeList($searchParams);

        if ($attributeList) {
            $queryBuilder->select(
                $this->prepareAttributeList($attributeList, $searchParams)
            );
        }
    }

    /**
     * @param string[] $attributeList
     * @return array<int, array{string, string}|string>
     */
    private function prepareAttributeList(array $attributeList, SearchParams $searchParams): array
    {
        $limit = $searchParams->getMaxTextAttributeLength();

        if ($limit === null) {
            return $attributeList;
        }

        $resultList = [];

        foreach ($attributeList as $item) {
            if (
                $this->metadataProvider->hasAttribute($this->entityType, $item) &&
                $this->metadataProvider->getAttributeType($this->entityType, $item) === Entity::TEXT &&
                !$this->metadataProvider->isAttributeNotStorable($this->entityType, $item)
            ) {
                $resultList[] = [
                    "LEFT:($item, $limit)",
                    $item
                ];

                continue;
            }

            $resultList[] = $item;
        }

        return $resultList;
    }

    /**
     * @return ?string[]
     */
    private function getSelectAttributeList(SearchParams $searchParams): ?array
    {
        $passedAttributeList = $searchParams->getSelect();

        if (!$passedAttributeList) {
            return null;
        }

        if ($passedAttributeList === ['*']) {
            return ['*'];
        }

        $attributeList = [];

        if (!in_array(Attribute::ID, $passedAttributeList)) {
            $attributeList[] = Attribute::ID;
        }

        foreach ($this->getAclAttributeList() as $attribute) {
            if (in_array($attribute, $passedAttributeList)) {
                continue;
            }

            if (!$this->metadataProvider->hasAttribute($this->entityType, $attribute)) {
                continue;
            }

            $attributeList[] = $attribute;
        }

        foreach ($passedAttributeList as $attribute) {
            if (in_array($attribute, $attributeList)) {
                continue;
            }

            if (!$this->metadataProvider->hasAttribute($this->entityType, $attribute)) {
                continue;
            }

            $attributeList[] = $attribute;
        }

        $orderByField = $searchParams->getOrderBy() ?? $this->metadataProvider->getDefaultOrderBy($this->entityType);

        if ($orderByField) {
            $sortByAttributeList = $this->fieldUtil->getAttributeList($this->entityType, $orderByField);

            foreach ($sortByAttributeList as $attribute) {
                if (in_array($attribute, $attributeList)) {
                    continue;
                }

                if (!$this->metadataProvider->hasAttribute($this->entityType, $attribute)) {
                    continue;
                }

                $attributeList[] = $attribute;
            }
        }

        $selectAttributesDependencyMap =
            $this->metadataProvider->getSelectAttributesDependencyMap($this->entityType) ?? [];

        foreach ($selectAttributesDependencyMap as $attribute => $dependantAttributeList) {
            if (!in_array($attribute, $attributeList)) {
                continue;
            }

            foreach ($dependantAttributeList as $dependantAttribute) {
                if (in_array($dependantAttribute, $attributeList)) {
                    continue;
                }

                $attributeList[] = $dependantAttribute;
            }
        }

        return $attributeList;
    }

    /**
     * @return string[]
     */
    private function getAclAttributeList(): array
    {
        if ($this->user->isPortal()) {
            return
                $this->metadataProvider->getAclPortalAttributeList($this->entityType) ??
                $this->aclPortalAttributeList;
        }

        return
            $this->metadataProvider->getAclAttributeList($this->entityType) ??
            $this->aclAttributeList;
    }
}
