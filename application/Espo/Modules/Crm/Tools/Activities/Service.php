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

namespace Espo\Modules\Crm\Tools\Activities;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\ServiceFactory;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Email;
use Espo\Entities\Preferences;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\UnionBuilder;
use Espo\ORM\Query\SelectBuilder;

use Espo\ORM\Entity;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\Part\Order;

use Espo\Core\Acl\Table;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Select\Where\ConverterFactory as WhereConverterFactory;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Core\FieldProcessing\Loader\Params as FieldLoaderParams;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;

use LogicException;
use PDO;
use DateTime;

class Service
{
    private const UPCOMING_ACTIVITIES_FUTURE_DAYS = 1;
    private const UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS = 7;

    /** @var array<string, array<string, string>> */
    private array $attributeMap = [
        Email::ENTITY_TYPE => [
            'dateSent' => 'dateStart',
        ],
    ];

    private WhereConverterFactory $whereConverterFactory;
    private ListLoadProcessor $listLoadProcessor;
    private RecordServiceContainer $recordServiceContainer;
    private SelectBuilderFactory $selectBuilderFactory;
    private Config $config;
    private Metadata $metadata;
    private Acl $acl;
    private ServiceFactory $serviceFactory;
    private EntityManager $entityManager;
    private User $user;

    public function __construct(
        WhereConverterFactory $whereConverterFactory,
        ListLoadProcessor $listLoadProcessor,
        RecordServiceContainer $recordServiceContainer,
        SelectBuilderFactory $selectBuilderFactory,
        Config $config,
        Metadata $metadata,
        Acl $acl,
        ServiceFactory $serviceFactory,
        EntityManager $entityManager,
        User $user
    ) {
        $this->whereConverterFactory = $whereConverterFactory;
        $this->listLoadProcessor = $listLoadProcessor;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->config = $config;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->serviceFactory = $serviceFactory;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    protected function isPerson(string $scope): bool
    {
        return
            in_array(
                $scope,
                [Contact::ENTITY_TYPE, Lead::ENTITY_TYPE, User::ENTITY_TYPE]
            ) ||
            $this->metadata->get(['scopes', $scope, 'type']) === Person::TEMPLATE_TYPE;
    }

    protected function isCompany(string $scope): bool
    {
        return
            $scope == Account::ENTITY_TYPE ||
            $this->metadata->get(['scopes', $scope, 'type']) === Company::TEMPLATE_TYPE;
    }

    /**
     * @param string[] $statusList
     */
    protected function getActivitiesUserMeetingQuery(User $entity, array $statusList = []): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Meeting::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['dateStartDate', 'dateStartDate'],
                ['dateEndDate', 'dateEndDate'],
                ['"Meeting"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['null', 'hasAttachment'],
            ])
            ->leftJoin(
                'MeetingUser',
                'usersLeftMiddle',
                [
                    'usersLeftMiddle.meetingId:' => 'meeting.id',
                ]
            );

        $where = [
            'usersLeftMiddle.userId' => $entity->getId(),
        ];

        if ($entity->isPortal() && $entity->get('contactId')) {
            $where['contactsLeftMiddle.contactId'] = $entity->get('contactId');

            $builder
                ->leftJoin('contacts', 'contactsLeft')
                ->distinct()
                ->where([
                    'OR' => $where,
                ]);
        }
        else {
            $builder->where($where);
        }

        if (!empty($statusList)) {
            $builder->where([
                'status' => $statusList,
            ]);
        }

        return $builder->build();
    }

    /**
     * @param string[] $statusList
     */
    protected function getActivitiesUserCallQuery(User $entity, array $statusList = []): Select
    {
        $seed = $this->entityManager->getNewEntity(Call::ENTITY_TYPE);

        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Call::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                (
                    $seed->hasAttribute('dateStartDate') ?
                        ['dateStartDate', 'dateStartDate'] : ['null', 'dateStartDate']
                ),
                (
                    $seed->hasAttribute('dateEndDate') ?
                        ['dateEndDate', 'dateEndDate'] : ['null', 'dateEndDate']
                ),
                ['"Call"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['null', 'hasAttachment'],
            ])
            ->leftJoin(
                'CallUser',
                'usersLeftMiddle',
                [
                    'usersLeftMiddle.callId:' => 'call.id',
                ]
            );

        $where = [
            'usersLeftMiddle.userId' => $entity->getId(),
        ];

        if ($entity->isPortal() && $entity->get('contactId')) {
            $where['contactsLeftMiddle.contactId'] = $entity->get('contactId');

            $builder
                ->leftJoin('contacts', 'contactsLeft')
                ->distinct()
                ->where([
                    'OR' => $where,
                ]);
        }
        else {
            $builder->where($where);
        }

        if (!empty($statusList)) {
            $builder->where([
                'status' => $statusList,
            ]);
        }

        return $builder->build();
    }

    /**
     * @param string[] $statusList
     * @return Select|Select[]
     */
    protected function getActivitiesUserEmailQuery(User $entity, array $statusList = [])
    {
        if ($entity->isPortal() && $entity->get('contactId')) {
            $contact = $this->entityManager->getEntity(Contact::ENTITY_TYPE, $entity->get('contactId'));

            if ($contact) {
                return $this->getActivitiesEmailQuery($contact, $statusList);
            }
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Email::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['null', 'dateEnd'],
                ['null', 'dateStartDate'],
                ['null', 'dateEndDate'],
                ['"Email"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                'hasAttachment',
            ])
            ->leftJoin(
                'EmailUser',
                'usersLeftMiddle',
                [
                    'usersLeftMiddle.emailId:' => 'email.id',
                ]
            )
            ->where([
                'usersLeftMiddle.userId' => $entity->getId(),
            ]);

        if (!empty($statusList)) {
            $builder->where([
                'status' => $statusList,
            ]);
        }

        return $builder->build();
    }

    /**
     * @param string[] $statusList
     * @return Select|Select[]
     */
    protected function getActivitiesMeetingOrCallQuery(
        Entity $entity,
        array $statusList,
        string $targetEntityType
    ) {
        if ($entity instanceof User && $targetEntityType === Meeting::ENTITY_TYPE) {
            return $this->getActivitiesUserMeetingQuery($entity, $statusList);
        }

        if ($entity instanceof User && $targetEntityType === Call::ENTITY_TYPE) {
            return $this->getActivitiesUserCallQuery($entity, $statusList);
        }

        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $seed = $this->entityManager->getNewEntity($targetEntityType);

        $baseBuilder = $this->selectBuilderFactory
            ->create()
            ->from($targetEntityType)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                (
                    $seed->hasAttribute('dateStartDate') ?
                        ['dateStartDate', 'dateStartDate'] : ['null', 'dateStartDate']
                ),
                (
                    $seed->hasAttribute('dateEndDate') ?
                        ['dateEndDate', 'dateEndDate'] : ['null', 'dateEndDate']
                ),
                ['"' . $targetEntityType . '"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['false', 'hasAttachment'],
            ]);

        if (!empty($statusList)) {
            $baseBuilder->where([
                'status' => $statusList,
            ]);
        }

        $builder = clone $baseBuilder;

        if ($entityType === Account::ENTITY_TYPE) {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => Account::ENTITY_TYPE,
                    ],
                    [
                        'accountId' => $id,
                    ],
                ],
            ]);
        }
        else if ($entityType === Lead::ENTITY_TYPE && $entity->get('createdAccountId')) {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => Lead::ENTITY_TYPE,
                    ],
                    [
                        'accountId' => $entity->get('createdAccountId'),
                    ],
                ],
            ]);
        }
        else {
            $builder->where([
                'parentId' => $id,
                'parentType' => $entityType,
            ]);
        }

        if (!$this->isPerson($entityType)) {
            return $builder->build();
        }

        $queryList = [$builder->build()];

        $link = null;

        switch ($entityType) {
            case Contact::ENTITY_TYPE:
                $link = 'contacts';

                break;

            case Lead::ENTITY_TYPE:
                $link = 'leads';

                break;

            case User::ENTITY_TYPE:
                $link = 'users';

                break;
        }

        if (!$link) {
            return $queryList;
        }

        $personBuilder = clone $baseBuilder;

        $personBuilder
            ->join($link)
            ->where([
                $link . '.id' => $id,
                'OR' => [
                    'parentType!=' => $entityType,
                    'parentId!=' => $id,
                    'parentType' => null,
                    'parentId' => null,
                ],
            ]);

        $queryList[] = $personBuilder->build();

        return $queryList;
    }

    /**
     * @param string[] $statusList
     * @return Select|Select[]
     */
    protected function getActivitiesMeetingQuery(Entity $entity, array $statusList = [])
    {
        return $this->getActivitiesMeetingOrCallQuery($entity, $statusList, Meeting::ENTITY_TYPE);
    }

    /**
     * @param string[] $statusList
     * @return Select|Select[]
     */
    protected function getActivitiesCallQuery(Entity $entity, array $statusList = [])
    {
        return $this->getActivitiesMeetingOrCallQuery($entity, $statusList, Call::ENTITY_TYPE);
    }

    /**
     * @param string[] $statusList
     * @return Select|Select[]
     */
    protected function getActivitiesEmailQuery(Entity $entity, array $statusList = [])
    {
        if ($entity instanceof User) {
            return $this->getActivitiesUserEmailQuery($entity, $statusList);
        }

        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $baseBuilder = $this->selectBuilderFactory
            ->create()
            ->from('Email')
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['null', 'dateEnd'],
                ['null', 'dateStartDate'],
                ['null', 'dateEndDate'],
                ['"Email"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                'hasAttachment',
            ]);

        if (!empty($statusList)) {
            $baseBuilder->where([
                'status' => $statusList,
            ]);
        }

        $builder = clone $baseBuilder;

        if ($entityType === Account::ENTITY_TYPE) {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => Account::ENTITY_TYPE,
                    ],
                    [
                        'accountId' => $id,
                    ],
                ],
            ]);
        }
        else if ($entityType == Lead::ENTITY_TYPE && $entity->get('createdAccountId')) {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => Lead::ENTITY_TYPE,
                    ],
                    [
                        'accountId' => $entity->get('createdAccountId'),
                    ],
                ],
            ]);
        }
        else {
            $builder->where([
               'parentId' => $id,
               'parentType' => $entityType,
            ]);
        }


        if (!$this->isPerson($entityType) && !$this->isCompany($entityType)) {
            return $builder->build();
        }

        $queryList = [$builder->build()];

        $personBuilder = clone $baseBuilder;

        $personBuilder
            ->leftJoin(
                'EntityEmailAddress',
                'eea',
                [
                    'eea.emailAddressId:' => 'fromEmailAddressId',
                    'eea.entityType' => $entityType,
                    'eea.deleted' => false,
                ]
            )
            ->where([
                'OR' => [
                    'parentType!=' => $entityType,
                    'parentId!=' => $id,
                    'parentType' => null,
                    'parentId' => null,
                ],
                'eea.entityId' => $id,
            ]);

        $queryList[] = $personBuilder->build();

        $addressBuilder = clone $baseBuilder;

        $addressBuilder
            ->leftJoin(
                'EmailEmailAddress',
                'em',
                [
                    'em.emailId:' => 'id',
                    'em.deleted' => false,
                ]
            )
            ->leftJoin(
                'EntityEmailAddress',
                'eea',
                [
                    'eea.emailAddressId:' => 'em.emailAddressId',
                    'eea.entityType' => $entityType,
                    'eea.deleted' => false,
                ]
            )
            ->where([
                'OR' => [
                    'parentType!=' => $entityType,
                    'parentId!=' => $id,
                    'parentType' => null,
                    'parentId' => null,
                ],
                'eea.entityId' => $id,
            ]);

        $queryList[] = $addressBuilder->build();

        return $queryList;
    }

    /**
     * @param array<string,Select|array<string, mixed>> $parts
     * @return RecordCollection<Entity>
     */
    protected function getResultFromQueryParts(array $parts, string $scope, FetchParams $params): RecordCollection
    {
        if ($parts === []) {
            /** @var RecordCollection<Entity> */
            return new RecordCollection(new EntityCollection(), 0);
        }

        $onlyScope = false;

        if ($params->getEntityType()) {
            $onlyScope = $params->getEntityType();
        }

        $maxSize = $params->getMaxSize();

        $queryList = [];

        if (!$onlyScope) {
            foreach ($parts as $part) {
                if (is_array($part)) {
                    $queryList = array_merge($queryList, $part);
                } else {
                    $queryList[] = $part;
                }
            }
        } else {
            $part = $parts[$onlyScope];

            if (is_array($part)) {
                $queryList = array_merge($queryList, $part);
            } else {
                $queryList[] = $part;
            }
        }

        $maxSizeQ = $maxSize;

        $offset = $params->getOffset() ?? 0;

        if (!$onlyScope && $scope === User::ENTITY_TYPE) {
            // optimizing sub-queries

            $newQueryList = [];

            foreach ($queryList as $query) {
                $subBuilder = $this->entityManager
                    ->getQueryBuilder()
                    ->select()
                    ->clone($query);

                if ($maxSize) {
                    $subBuilder->limit(0, $offset + $maxSize + 1);
                }

                // Order by `dateStart`.
                $subBuilder->order(
                    Order
                        ::create(
                            $query->getSelect()[2]->getExpression()
                        )
                        ->withDesc()
                );

                $newQueryList[] = $subBuilder->build();
            }

            $queryList = $newQueryList;
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $totalCount = -2;

        if ($scope !== User::ENTITY_TYPE) {
            $countQuery = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->fromQuery($builder->build(), 'c')
                ->select('COUNT:(c.id)', 'count')
                ->build();

            $sth = $this->entityManager->getQueryExecutor()->execute($countQuery);

            $row = $sth->fetch(PDO::FETCH_ASSOC);

            $totalCount = $row['count'];
        }

        $builder->order('dateStart', 'DESC');

        if ($scope === User::ENTITY_TYPE) {
            $maxSizeQ++;
        } else {
            $builder->order('createdAt', 'DESC');
        }

        if ($maxSize) {
            $builder->limit($offset, $maxSizeQ);
        }

        $unionQuery = $builder->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $collection = new EntityCollection();

        foreach ($rowList as $row) {
            $itemEntityType = $row['_scope'] ?? null;

            if (!is_string($itemEntityType)) {
                throw new LogicException();
            }

            foreach (($this->attributeMap[$itemEntityType] ?? []) as $attributeActual => $attribute) {
                $row[$attributeActual] = $row[$attribute];

                unset($row[$attribute]);
            }

            $itemEntity = $this->entityManager->getNewEntity($itemEntityType);
            $itemEntity->set($row);

            $collection->append($itemEntity);
        }

        if ($scope === User::ENTITY_TYPE) {
            return RecordCollection::createNoCount($collection, $maxSize);
        }

        return RecordCollection::create($collection, $totalCount);
    }

    /**
     * @throws Forbidden
     */
    protected function accessCheck(Entity $entity): void
    {
        if ($entity instanceof User) {
            if (!$this->acl->checkUserPermission($entity, 'user')) {
                throw new Forbidden();
            }

            return;
        }

        if (!$this->acl->check($entity, Table::ACTION_READ)) {
            throw new Forbidden();
        }
    }

    /**
     * @return RecordCollection<Entity>
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function findActivitiesEntityType(
        string $scope,
        string $id,
        string $entityType,
        bool $isHistory,
        SearchParams $searchParams
    ): RecordCollection {

        if (!$this->acl->checkScope($entityType)) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntity($scope, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        if (!$this->metadata->get(['scopes', $entityType, 'activity'])) {
            throw new Error("Entity '{$entityType}' is not an activity.");
        }

        if (!$isHistory) {
            $statusList = $this->metadata->get(['scopes', $entityType, 'activityStatusList']) ??
                [Meeting::STATUS_PLANNED];
        }
        else {
            $statusList = $this->metadata->get(['scopes', $entityType, 'historyStatusList']) ??
                [Meeting::STATUS_HELD, Meeting::STATUS_NOT_HELD];
        }

        if ($entityType === Email::ENTITY_TYPE && $searchParams->getOrderBy() === 'dateStart') {
            $searchParams = $searchParams->withOrderBy('dateSent');
        }

        $service = $this->recordServiceContainer->get($entityType);

        $preparedSearchParams = $service->prepareSearchParams($searchParams);

        $offset = $searchParams->getOffset();
        $limit = $searchParams->getMaxSize();

        $baseQueryList = $this->getActivitiesQuery($entity, $entityType, $statusList);

        if (!is_array($baseQueryList)) {
            $baseQueryList = [$baseQueryList];
        }

        $queryList = [];

        $order = null;

        foreach ($baseQueryList as $i => $itemQuery) {
            $itemBuilder = $this->selectBuilderFactory
                ->create()
                ->clone($itemQuery)
                ->withSearchParams($preparedSearchParams)
                ->withComplexExpressionsForbidden()
                ->withWherePermissionCheck()
                ->buildQueryBuilder();

            if ($i === 0) {
                $order = $itemBuilder->build()->getOrder();
            }

            $itemBuilder
                ->limit(null, null)
                ->order([]);

            $queryList[] = $itemBuilder->build();
        }

        $query = $queryList[0];

        if (count($queryList) > 1) {
            $unionBuilder = $this->entityManager
                ->getQueryBuilder()
                ->union();

            foreach ($queryList as $subQuery) {
                $unionBuilder->query($subQuery);
            }

            if ($order !== null && count($order)) {
                $unionBuilder->order(
                    $order[0]->getExpression()->getValue(),
                    $order[0]->getDirection()
                );
            }

            $query = $unionBuilder->build();
        }

        /** @var UnionBuilder|SelectBuilder $builder */
        $builder = $this->entityManager
            ->getQueryBuilder()
            ->clone($query);

        if ($order && count($queryList) === 1) {
            $builder->order($order);
        }

        $builder->limit($offset, $limit);

        $sql = $this->entityManager->getQueryComposer()->compose($builder->build());

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->createFromSthCollection(
                $this->entityManager
                    ->getRDBRepository($entityType)
                    ->findBySql($sql)
            );

        $loadProcessorParams = FieldLoaderParams
            ::create()
            ->withSelect($searchParams->getSelect());

        foreach ($collection as $e) {
            $this->listLoadProcessor->process($e, $loadProcessorParams);

            $service->prepareEntityForOutput($e);
        }

        $countQuery = $this->entityManager->getQueryBuilder()
            ->select()
            ->fromQuery($query, 'c')
            ->select('COUNT:(c.id)', 'count')
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($countQuery);

        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $total = $row['count'];

        return new RecordCollection($collection, $total);
    }

    /**
     * @return RecordCollection<Entity>
     * @throws NotFound
     * @throws Error
     * @throws Forbidden
     */
    public function getActivities(string $scope, string $id, FetchParams $params): RecordCollection
    {
        $entity = $this->entityManager->getEntityById($scope, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $targetScope = $params->getEntityType();

        $fetchAll = empty($targetScope);

        if ($targetScope) {
            if (!$this->metadata->get(['scopes', $targetScope, 'activity'])) {
                throw new Error('Entity \'' . $targetScope . '\' is not an activity');
            }
        }

        $parts = [];

        /** @var string[] $entityTypeList */
        $entityTypeList = $this->config->get('activitiesEntityList') ??
            [Meeting::ENTITY_TYPE, Call::ENTITY_TYPE];

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $targetScope !== $entityType) {
                continue;
            }

            if (!$this->acl->checkScope($entityType)) {
                continue;
            }

            if (!$this->metadata->get('scopes.' . $entityType . '.activity')) {
                continue;
            }

            $statusList = $this->metadata->get(['scopes', $entityType, 'activityStatusList']) ??
                [Meeting::STATUS_PLANNED];

            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList);
        }

        return $this->getResultFromQueryParts($parts, $scope, $params);
    }

    /**
     * @return RecordCollection<Entity>
     * @throws NotFound
     * @throws Error
     * @throws Forbidden
     */
    public function getHistory(string $scope, string $id, FetchParams $params): RecordCollection
    {
        $entity = $this->entityManager->getEntityById($scope, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $targetScope = $params->getEntityType();

        $fetchAll = empty($targetScope);

        if ($targetScope) {
            if (!$this->metadata->get(['scopes', $targetScope, 'activity'])) {
                throw new Error('Entity \'' . $targetScope . '\' is not an activity');
            }
        }

        $parts = [];

        /** @var string[] $entityTypeList */
        $entityTypeList = $this->config->get('historyEntityList') ??
            [
                Meeting::ENTITY_TYPE,
                Call::ENTITY_TYPE,
                Email::ENTITY_TYPE
            ];

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $targetScope !== $entityType) {
                continue;
            }

            if (!$this->acl->checkScope($entityType)) {
                continue;
            }

            if (!$this->metadata->get('scopes.' . $entityType . '.activity')) {
                continue;
            }

            $statusList = $this->metadata->get(['scopes', $entityType, 'historyStatusList']) ??
                [Meeting::STATUS_HELD, Meeting::STATUS_NOT_HELD];

            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList);
        }

        return $this->getResultFromQueryParts($parts, $scope, $params);
    }

    /**
     * @param string[] $statusList
     * @return Select|Select[]
     */
    protected function getActivitiesQuery(Entity $entity, string $scope, array $statusList = [])
    {
        $serviceName = 'Activities' . $entity->getEntityType();

        if ($this->serviceFactory->checkExists($serviceName)) {
            // For bc.
            $service = $this->serviceFactory->create($serviceName);

            $methodName = 'getActivities' . $scope . 'Query';

            if (method_exists($service, $methodName)) {
                return $service->$methodName($entity, $statusList);
            }
        }

        if ($scope === Meeting::ENTITY_TYPE) {
            return $this->getActivitiesMeetingQuery($entity, $statusList);
        }

        if ($scope === Call::ENTITY_TYPE) {
            return $this->getActivitiesCallQuery($entity, $statusList);
        }

        if ($scope === Email::ENTITY_TYPE) {
            return $this->getActivitiesEmailQuery($entity, $statusList);
        }

        return $this->getActivitiesBaseQuery($entity, $scope, $statusList);
    }

    /**
     * @param string[] $statusList
     */
    protected function getActivitiesBaseQuery(Entity $entity, string $scope, array $statusList = []): Select
    {
        $seed = $this->entityManager->getNewEntity($scope);

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($scope)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ($seed->hasAttribute('dateStart') ? ['dateStart', 'dateStart'] : ['null', 'dateStart']),
                ($seed->hasAttribute('dateEnd') ? ['dateEnd', 'dateEnd'] : ['null', 'dateEnd']),
                ($seed->hasAttribute('dateStartDate') ?
                    ['dateStartDate', 'dateStartDate'] : ['null', 'dateStartDate']),
                ($seed->hasAttribute('dateEndDate') ?
                    ['dateEndDate', 'dateEndDate'] : ['null', 'dateEndDate']),
                ['"' . $scope . '"', '_scope'],
                ($seed->hasAttribute('assignedUserId') ?
                    ['assignedUserId', 'assignedUserId'] : ['null', 'assignedUserId']),
                ($seed->hasAttribute('assignedUserName') ? ['assignedUserName', 'assignedUserName'] :
                    ['null', 'assignedUserName']),
                ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['null', 'parentType']),
                ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['null', 'parentId']),
                'status',
                'createdAt',
                ['false', 'hasAttachment'],
            ]);

        if ($entity->getEntityType() === User::ENTITY_TYPE) {
            $builder->where([
                'assignedUserId' => $entity->getId(),
            ]);
        }
        else {
            $builder->where([
                'parentId' => $entity->getId(),
                'parentType' => $entity->getEntityType(),
            ]);
        }

        $builder->where([
            'status' => $statusList,
        ]);

        return $builder->build();
    }

    public function removeReminder(string $id): void
    {
        $builder = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Reminder::ENTITY_TYPE)
            ->where([
                'id' => $id,
            ]);

        if (!$this->user->isAdmin()) {
            $builder->where([
                'userId' => $this->user->getId(),
            ]);
        }

        $deleteQuery = $builder->build();

        $this->entityManager->getQueryExecutor()->execute($deleteQuery);
    }

    /**
     * @param array{
     *   offset?: ?int,
     *   maxSize?: ?int,
     * } $params
     * @param ?string[] $entityTypeList
     * @return RecordCollection<Entity>
     * @throws Forbidden
     * @throws NotFound
     */
    public function getUpcomingActivities(
        string $userId,
        array $params = [],
        ?array $entityTypeList = null,
        ?int $futureDays = null
    ): RecordCollection {

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new NotFound();
        }

        $this->accessCheck($user);

        if (!$entityTypeList) {
            $entityTypeList = $this->config->get('activitiesEntityList', []);
        }

        if (is_null($futureDays)) {
            $futureDays = $this->config->get(
                'activitiesUpcomingFutureDays',
                self::UPCOMING_ACTIVITIES_FUTURE_DAYS
            );
        }

        $queryList = [];

        foreach ($entityTypeList as $entityType) {
            if (
                !$this->metadata->get(['scopes', $entityType, 'activity']) &&
                $entityType !== Task::ENTITY_TYPE
            ) {
                continue;
            }

            if (!$this->acl->checkScope($entityType, 'read')) {
                continue;
            }

            if (!$this->metadata->get(['entityDefs', $entityType, 'fields', 'dateStart'])) {
                continue;
            }

            if (!$this->metadata->get(['entityDefs', $entityType, 'fields', 'dateEnd'])) {
                continue;
            }

            $queryList[] = $this->getUpcomingActivitiesEntityTypeQuery($entityType, $params, $user, $futureDays);
        }

        if ($queryList === []) {
            return RecordCollection::create(new EntityCollection(), 0);
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionCountQuery = $builder->build();

        $countQuery = $this->entityManager->getQueryBuilder()
            ->select()
            ->fromQuery($unionCountQuery, 'c')
            ->select('COUNT:(c.id)', 'count')
            ->build();

        $countSth = $this->entityManager->getQueryExecutor()->execute($countQuery);

        $row = $countSth->fetch(PDO::FETCH_ASSOC);

        $totalCount = $row['count'];

        $offset = intval($params['offset'] ?? 0);
        $maxSize = intval($params['maxSize'] ?? 0);

        $unionQuery = $builder
            ->order('dateStart')
            ->order('dateEnd')
            ->order('name')
            ->limit($offset, $maxSize)
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $collection = new EntityCollection();

        foreach ($rows as $row) {
            /** @var string $itemEntityType */
            $itemEntityType = $row['entityType'];
            /** @var string $itemId */
            $itemId = $row['id'];

            $entity = $this->entityManager->getEntityById($itemEntityType, $itemId);

            if (!$entity) {
                // @todo Revise.
                $entity = $this->entityManager->getNewEntity($itemEntityType);

                $entity->set('id', $itemId);
            }

            $collection->append($entity);;
        }

        /** @var RecordCollection<Entity> */
        return RecordCollection::create($collection, $totalCount);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function getUpcomingActivitiesEntityTypeQuery(
        string $entityType,
        array $params,
        User $user,
        int $futureDays
    ): Select {

        $beforeString = (new DateTime())
            ->modify('+' . $futureDays . ' days')
            ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->forUser($user)
            ->withBoolFilter('onlyMy')
            ->withStrictAccessControl();

        $primaryFilter = 'planned';

        if ($entityType === Task::ENTITY_TYPE) {
            $primaryFilter = 'actual';
        }

        $builder->withPrimaryFilter($primaryFilter);

        if (!empty($params['textFilter'])) {
            $builder->withTextFilter($params['textFilter']);
        }

        $queryBuilder = $builder->buildQueryBuilder();

        $converter = $this->whereConverterFactory->create($entityType, $user);

        $timeZone = $this->getUserTimeZone($user);

        if ($entityType === Task::ENTITY_TYPE) {
            $upcomingTaskFutureDays = $this->config->get(
                'activitiesUpcomingTaskFutureDays',
                self::UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS
            );

            $taskBeforeString = (new DateTime())
                ->modify('+' . $upcomingTaskFutureDays . ' days')
                ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

            $queryBuilder->where([
                'OR' => [
                    [
                        'dateStart' => null,
                        'OR' => [
                            'dateEnd' => null,
                            $converter->convert(
                                $queryBuilder,
                                WhereItem::fromRaw([
                                    'type' => 'before',
                                    'attribute' => 'dateEnd',
                                    'value' => $taskBeforeString,
                                    'timeZone' => $timeZone,
                                ])
                            )->getRaw()
                        ]
                    ],
                    [
                        'dateStart!=' => null,
                        'OR' => [
                            $converter->convert(
                                $queryBuilder,
                                WhereItem::fromRaw([
                                    'type' => 'past',
                                    'attribute' => 'dateStart',
                                    'timeZone' => $timeZone,
                                ])
                            )->getRaw(),
                            $converter->convert(
                                $queryBuilder,
                                WhereItem::fromRaw([
                                    'type' => 'today',
                                    'attribute' => 'dateStart',
                                    'timeZone' => $timeZone,
                                ])
                            )->getRaw(),
                            $converter->convert(
                                $queryBuilder,
                                WhereItem::fromRaw([
                                    'type' => 'before',
                                    'attribute' => 'dateStart',
                                    'value' => $beforeString,
                                    'timeZone' => $timeZone,
                                ])
                            )->getRaw(),
                        ]
                    ],
                ],
            ]);
        }
        else {
            $queryBuilder->where([
                'OR' => [
                    $converter->convert(
                        $queryBuilder,
                        WhereItem::fromRaw([
                            'type' => 'today',
                            'attribute' => 'dateStart',
                            'timeZone' => $timeZone,
                        ])
                    )->getRaw(),
                    [
                        $converter->convert(
                            $queryBuilder,
                            WhereItem::fromRaw([
                                'type' => 'future',
                                'attribute' => 'dateEnd',
                                'timeZone' => $timeZone,
                            ])
                        )->getRaw(),
                        $converter->convert(
                            $queryBuilder,
                            WhereItem::fromRaw([
                                'type' => 'before',
                                'attribute' => 'dateStart',
                                'value' => $beforeString,
                                'timeZone' => $timeZone,
                            ])
                        )->getRaw(),
                    ],
                ],
            ]);
        }

        $queryBuilder->select([
            'id',
            'name',
            'dateStart',
            'dateEnd',
            ['"' . $entityType . '"', 'entityType'],
        ]);

        return $queryBuilder->build();
    }

    protected function getUserTimeZone(User $user): string
    {
        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $user->getId());

        if ($preferences) {
            $timeZone = $preferences->get('timeZone');

            if ($timeZone) {
                return $timeZone;
            }
        }

        return $this->config->get('timeZone') ?? 'UTC';
    }
}
