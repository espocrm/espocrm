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

namespace Espo\Tools\Email;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Select\Text\MetadataProvider as TextMetadataProvider;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\EmailAddress;
use Espo\Entities\InboundEmail;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use RuntimeException;

class AddressService
{
    private const ERASED_PREFIX = 'ERASED:';

    private const ATTR_EMAIL_ADDRESS = 'emailAddress';

    public function __construct(
        private Config $config,
        private Acl $acl,
        private Metadata $metadata,
        private SelectBuilderFactory $selectBuilderFactory,
        private EntityManager $entityManager,
        private User $user,
        private TextMetadataProvider $textMetadataProvider
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     * @throws NotFound
     * @throws Forbidden
     */
    public function searchInEntityType(string $entityType, string $query, int $limit): array
    {
        if (!in_array($entityType, $this->getHavingEmailAddressEntityTypeList())) {
            throw new NotFound("No 'email' field.");
        }

        if (!$this->acl->checkScope($entityType, Acl\Table::ACTION_READ)) {
            throw new Forbidden("No access to $entityType.");
        }

        if (!$this->acl->checkField($entityType, 'email')) {
            throw new Forbidden("No access to field 'email' in $entityType.");
        }

        $result = [];

        $this->findInAddressBookByEntityType($query, $limit, $entityType, $result);

        return $result;
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

        $this->findInInboundEmail($query, $result);

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
            if (!str_contains($query, '@')) {
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

        if (str_contains($filter, '@')) {
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
        } else {
            $textFilter = $filter;
        }

        $selectBuilder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withAccessControlFilter();

        if ($textFilter) {
            $selectBuilder->withTextFilter($textFilter);
        }

        try {
            $builder = $selectBuilder
                ->buildQueryBuilder()
                ->where($whereClause)
                ->order('name')
                ->limit(0, $limit);
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException($e->getMessage());
        }

        if ($entityType === User::ENTITY_TYPE) {
            $this->handleQueryBuilderUser($builder);
        }

        $select = [
            Field::ID,
            'emailAddress',
            Field::NAME,
        ];

        if (
            $this->metadata->get(['entityDefs', $entityType, 'fields', Field::NAME, 'type']) === FieldType::PERSON_NAME
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
            $emailAddress = $entity->get(self::ATTR_EMAIL_ADDRESS);

            $emailAddressData = $this->getEmailAddressRepository()->getEmailAddressData($entity);

            $skipPrimaryEmailAddress = false;

            if (!$emailAddress) {
                continue;
            }

            if (str_starts_with($emailAddress, self::ERASED_PREFIX)) {
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
                    'entityName' => $entity->get(Field::NAME),
                    'entityType' => $entityType,
                    'entityId' => $entity->getId(),
                ];
            }

            foreach ($emailAddressData as $item) {
                if ($emailAddress === $item->emailAddress) {
                    continue;
                }

                if (str_starts_with($item->emailAddress, self::ERASED_PREFIX)) {
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
                    'entityName' => $entity->get(Field::NAME),
                    'entityType' => $entityType,
                    'entityId' => $entity->getId(),
                ];
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $result
     */
    private function findInInboundEmail(string $query, array &$result): void
    {
        if ($this->user->isPortal()) {
            return;
        }

        $list = $this->entityManager
            ->getRDBRepository(InboundEmail::ENTITY_TYPE)
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
                'entityType' => InboundEmail::ENTITY_TYPE,
                'entityId' => $item->getId(),
            ];
        }
    }

    private function hasFullTextSearch(string $entityType): bool
    {
        return $this->textMetadataProvider->hasFullTextSearch($entityType);
    }

    private function handleQueryBuilderUser(SelectBuilder $queryBuilder): void
    {
        /*if ($this->acl->getPermissionLevel('portalPermission') === Table::LEVEL_NO) {
            $queryBuilder->where([
                'type!=' => User::TYPE_PORTAL,
            ]);
        }*/

        $queryBuilder->where([
            'isActive' => true,
            'type!=' => [
                User::TYPE_PORTAL,
                User::TYPE_API,
                User::TYPE_SYSTEM,
                User::TYPE_SUPER_ADMIN,
            ],
        ]);
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
