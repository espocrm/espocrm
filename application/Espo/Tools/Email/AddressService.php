<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Email;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Select\Text\MetadataProvider as TextMetadataProvider;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\EmailAddress as EmailAddressEntity;
use Espo\Entities\InboundEmail as InboundEmailEntity;
use Espo\Entities\User;
use Espo\Entities\User as UserEntity;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\Repositories\EmailAddress as Repository;

class AddressService
{
    private const ERASED_PREFIX = 'ERASED:';

    private Config $config;
    private Acl $acl;
    private Metadata $metadata;
    private SelectBuilderFactory $selectBuilderFactory;
    private EntityManager $entityManager;
    private User $user;
    private TextMetadataProvider $textMetadataProvider;

    public function __construct(
        Config $config,
        Acl $acl,
        Metadata $metadata,
        SelectBuilderFactory $selectBuilderFactory,
        EntityManager $entityManager,
        User $user,
        TextMetadataProvider $textMetadataProvider
    ) {
        $this->config = $config;
        $this->acl = $acl;
        $this->metadata = $metadata;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->textMetadataProvider = $textMetadataProvider;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchInAddressBook(string $query, int $limit, bool $onlyActual = false): array
    {
        $result = [];

        $entityTypeList = $this->config->get('emailAddressLookupEntityTypeList') ?? [];

        $allEntityTypeList = $this->getHavingEmailAddressEntityTypeList();

        foreach ($entityTypeList as $entityType) {
            if (!in_array($entityType, $allEntityTypeList)) {
                continue;
            }

            if (!$this->acl->checkScope($entityType)) {
                continue;
            }

            $this->findInAddressBookByEntityType($query, $limit, $entityType, $result, $onlyActual);
        }

        $this->findInInboundEmail($query, $limit, $result);

        $finalResult = [];

        foreach ($result as $item) {
            foreach ($finalResult as $item1) {
                if ($item['emailAddress'] == $item1['emailAddress']) {
                    continue 2;
                }
            }

            $finalResult[] = $item;
        }

        usort($finalResult, function ($item1, $item2) use ($query) {
            if (strpos($query, '@') === false) {
                return 0;
            }

            $p1 = strpos($item1['emailAddress'], $query);
            $p2 = strpos($item2['emailAddress'], $query);

            if ($p1 === 0 && $p2 !== 0) {
                return -1;
            }

            if ($p1 !== 0 && $p2 !== 0) {
                return 0;
            }

            if ($p1 !== 0 && $p2 === 0) {
                return 1;
            }

            return 0;
        });

        return $finalResult;
    }

    /**
     * @return string[]
     */
    private function getHavingEmailAddressEntityTypeList(): array
    {
        $list = [
            Account::ENTITY_TYPE,
            Contact::ENTITY_TYPE,
            Lead::ENTITY_TYPE,
            User::ENTITY_TYPE,
        ];

        $scopeDefs = $this->metadata->get(['scopes']);

        foreach ($scopeDefs as $scope => $defs) {
            if (
                empty($defs['disabled']) &&
                !empty($defs['type']) &&
                (
                    $defs['type'] === Person::TEMPLATE_TYPE ||
                    $defs['type'] === Company::TEMPLATE_TYPE
                )
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    /**
     * @param array<int, array<string, mixed>> $result
     */
    private function findInAddressBookByEntityType(
        string $filter,
        int $limit,
        string $entityType,
        array &$result,
        bool $onlyActual = false
    ): void {

        $textFilter = null;
        $whereClause = [];

        $byEmailAddress = false;

        if (strpos($filter, '@') !== false) {
            $byEmailAddress = true;
        }

        if (
            !$byEmailAddress &&
            mb_strlen($filter) < (int) $this->config->get('fullTextSearchMinLength') &&
            $this->hasFullTextSearch($entityType)
        ) {
            $byEmailAddress = true;
        }

        if ($byEmailAddress) {
            $whereClause = [
                'emailAddress*' => $filter . '%',
            ];
        }
        else {
            $textFilter = $filter;
        }

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withAccessControlFilter();

        if ($textFilter) {
            $selectBuilder->withTextFilter($textFilter);
        }

        $builder = $selectBuilder
            ->buildQueryBuilder()
            ->where($whereClause)
            ->order('name')
            ->limit(0, $limit);

        if ($textFilter) {
            $builder
                ->join('emailAddresses', 'emailAddressesJoin')
                ->distinct();
        }

        if ($entityType === User::ENTITY_TYPE) {
            $this->handleQueryBuilderUser($filter, $builder);
        }

        $select = [
            'id',
            'emailAddress',
            'name',
        ];

        if (
            $this->metadata->get(
                ['entityDefs', $entityType, 'fields', 'name', 'type']
            ) === 'personName'
        ) {
            $select[] = 'firstName';
            $select[] = 'lastName';
        }

        $builder->select($select);

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($builder->build())
            ->find();

        foreach ($collection as $entity) {
            $emailAddress = $entity->get('emailAddress');

            $emailAddressData = $this->getEmailAddressRepository()->getEmailAddressData($entity);

            $skipPrimaryEmailAddress = false;

            if (!$emailAddress) {
                continue;
            }

            if (strpos($emailAddress, self::ERASED_PREFIX) === 0) {
                $skipPrimaryEmailAddress = true;
            }

            if ($onlyActual) {
                if ($entity->get('emailAddressIsOptedOut')) {
                    $skipPrimaryEmailAddress = true;
                }

                foreach ($emailAddressData as $item) {
                    if ($emailAddress !== $item->emailAddress) {
                        continue;
                    }

                    if (!empty($item->invalid)) {
                        $skipPrimaryEmailAddress = true;
                    }
                }
            }

            if (!$skipPrimaryEmailAddress) {
                $result[] = [
                    'emailAddress' => $emailAddress,
                    'entityName' => $entity->get('name'),
                    'entityType' => $entityType,
                    'entityId' => $entity->getId(),
                ];
            }

            foreach ($emailAddressData as $item) {
                if ($emailAddress === $item->emailAddress) {
                    continue;
                }

                if (strpos($item->emailAddress, self::ERASED_PREFIX) === 0) {
                    continue;
                }

                if ($onlyActual) {
                    if (!empty($item->invalid)) {
                        continue;
                    }

                    if (!empty($item->optOut)) {
                        continue;
                    }
                }

                $result[] = [
                    'emailAddress' => $item->emailAddress,
                    'entityName' => $entity->get('name'),
                    'entityType' => $entityType,
                    'entityId' => $entity->getId(),
                ];
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $result
     */
    protected function findInInboundEmail(string $query, int $limit, array &$result): void
    {
        if ($this->user->isPortal()) {
            return;
        }

        $list = $this->entityManager
            ->getRDBRepository(InboundEmailEntity::ENTITY_TYPE)
            ->select([
                'id',
                'name',
                'emailAddress',
            ])
            ->where([
                'emailAddress*' => $query . '%',
            ])
            ->order('name')
            ->find();

        foreach ($list as $item) {
            $result[] = [
                'emailAddress' => $item->getEmailAddress(),
                'entityName' => $item->getName(),
                'entityType' => InboundEmailEntity::ENTITY_TYPE,
                'entityId' => $item->getId(),
            ];
        }
    }

    private function hasFullTextSearch(string $entityType): bool
    {
        return $this->textMetadataProvider->hasFullTextSearch($entityType);
    }

    private function handleQueryBuilderUser(string $filter, QueryBuilder $queryBuilder): void
    {
        if ($this->acl->getPermissionLevel('portalPermission') === Table::LEVEL_NO) {
            $queryBuilder->where([
                'type!=' => UserEntity::TYPE_PORTAL,
            ]);
        }

        $queryBuilder->where([
            'type!=' => [
                UserEntity::TYPE_API,
                UserEntity::TYPE_SYSTEM,
                UserEntity::TYPE_SUPER_ADMIN,
            ],
        ]);
    }

    private function getEmailAddressRepository(): Repository
    {
        /** @var Repository */
        return $this->entityManager->getRepository(EmailAddressEntity::ENTITY_TYPE);
    }
}
