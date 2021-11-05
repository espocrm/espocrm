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

namespace Espo\Modules\Crm\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\ORM\Query\UnionBuilder;
use Espo\ORM\Query\SelectBuilder;

use Espo\ORM\{
    Entity,
    Query\Select,
    Query\Part\Order,
};

use Espo\Core\{
    Record\Collection as RecordCollection,
    Select\SearchParams,
    Select\Where\Item as WhereItem,
    Select\Where\ConverterFactory as WhereConverterFactory,
    Select\SelectBuilderFactory,
    FieldProcessing\ListLoadProcessor,
    FieldProcessing\Loader\Params as FieldLoaderParams,
    Di,
    Record\ServiceContainer as RecordServiceContainer,
};

use Espo\{
    Entities\User as UserEntity,
};

use PDO;
use Exception;
use DateTime;
use DateInterval;

class Activities implements

    Di\ConfigAware,
    Di\MetadataAware,
    Di\AclAware,
    Di\ServiceFactoryAware,
    Di\EntityManagerAware,
    Di\UserAware
{

    use Di\ConfigSetter;
    use Di\MetadataSetter;
    use Di\AclSetter;
    use Di\ServiceFactorySetter;
    use Di\EntityManagerSetter;
    use Di\UserSetter;

    const UPCOMING_ACTIVITIES_FUTURE_DAYS = 1;

    const UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS = 7;

    const REMINDER_PAST_HOURS = 24;

    const BUSY_RANGES_MAX_RANGE_DAYS = 10;

    private $whereConverterFactory;

    private $listLoadProcessor;

    private $recordServiceContainer;

    private $selectBuilderFactory;

    public function __construct(
        WhereConverterFactory $whereConverterFactory,
        ListLoadProcessor $listLoadProcessor,
        RecordServiceContainer $recordServiceContainer,
        SelectBuilderFactory $selectBuilderFactory
    ) {
        $this->whereConverterFactory = $whereConverterFactory;
        $this->listLoadProcessor = $listLoadProcessor;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->selectBuilderFactory = $selectBuilderFactory;
    }

    protected function isPerson(string $scope): bool
    {
        return in_array($scope, ['Contact', 'Lead', 'User']) ||
            $this->metadata->get(['scopes', $scope, 'type']) === 'Person';
    }

    protected function isCompany(string $scope): bool
    {
        return in_array($scope, ['Account']) || $this->metadata->get(['scopes', $scope, 'type']) === 'Company';
    }

    protected function getActivitiesUserMeetingQuery(UserEntity $entity, array $statusList = [])
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from('Meeting')
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
                ['""', 'hasAttachment'],
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

    protected function getActivitiesUserCallQuery(UserEntity $entity, array $statusList = [])
    {
        $seed = $this->entityManager->getEntity('Call');

        $builder = $this->selectBuilderFactory
            ->create()
            ->from('Call')
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                (
                    $seed->hasAttribute('dateStartDate') ?
                        ['dateStartDate', 'dateStartDate'] : ['""', 'dateStartDate']
                ),
                (
                    $seed->hasAttribute('dateEndDate') ?
                        ['dateEndDate', 'dateEndDate'] : ['""', 'dateEndDate']
                ),
                ['"Call"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['""', 'hasAttachment'],
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

    protected function getActivitiesUserEmailQuery(UserEntity $entity, array $statusList = [])
    {
        if ($entity->isPortal() && $entity->get('contactId')) {
            $contact = $this->entityManager->getEntity('Contact', $entity->get('contactId'));

            if ($contact) {
                return $this->getActivitiesEmailQuery($contact, $statusList);
            }
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from('Email')
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['""', 'dateEnd'],
                ['""', 'dateStartDate'],
                ['""', 'dateEndDate'],
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

    protected function getActivitiesMeetingOrCallQuery(
        Entity $entity,
        array $statusList,
        string $targetEntityType
    ) {

        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $methodName = 'getActivities' . $entityType . $targetEntityType . 'Query';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList);
        }

        $seed = $this->entityManager->getEntity($targetEntityType);

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
                        ['dateStartDate', 'dateStartDate'] : ['""', 'dateStartDate']
                ),
                (
                    $seed->hasAttribute('dateEndDate') ?
                        ['dateEndDate', 'dateEndDate'] : ['""', 'dateEndDate']
                ),
                ['"' . $targetEntityType . '"', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['""', 'hasAttachment'],
            ]);

        if (!empty($statusList)) {
            $baseBuilder->where([
                'status' => $statusList,
            ]);
        }

        $builder = clone $baseBuilder;

        if ($entityType == 'Account') {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Account',
                    ],
                    [
                        'accountId' => $id,
                    ],
                ],
            ]);
        }
        else if ($entityType == 'Lead' && $entity->get('createdAccountId')) {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Lead',
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
            case 'Contact':
                $link = 'contacts';

                break;

            case 'Lead':
                $link = 'leads';

                break;

            case 'User':
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

    protected function getActivitiesMeetingQuery(Entity $entity, array $statusList = [])
    {
        return $this->getActivitiesMeetingOrCallQuery($entity, $statusList, 'Meeting');
    }

    protected function getActivitiesCallQuery(Entity $entity, array $statusList = [])
    {
        return $this->getActivitiesMeetingOrCallQuery($entity, $statusList, 'Call');
    }

    protected function getActivitiesEmailQuery(Entity $entity, array $statusList = [])
    {
        $entityType = $entity->getEntityType();
        $id = $entity->getId();

        $methodName = 'getActivities' . $entityType . 'EmailQuery';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList);
        }

        $baseBuilder = $this->selectBuilderFactory
            ->create()
            ->from('Email')
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['""', 'dateEnd'],
                ['""', 'dateStartDate'],
                ['""', 'dateEndDate'],
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

        if ($entityType == 'Account') {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Account',
                    ],
                    [
                        'accountId' => $id,
                    ],
                ],
            ]);
        }
        else if ($entityType == 'Lead' && $entity->get('createdAccountId')) {
            $builder->where([
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Lead',
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
                    'eea.deleted' => 0,
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

    protected function getResultFromQueryParts($parts, $scope, $id, $params)
    {
        if (empty($parts)) {
            return [
                'list' => [],
                'total' => 0,
            ];
        }

        $onlyScope = false;

        if (!empty($params['scope'])) {
            $onlyScope = $params['scope'];
        }

        $maxSize = $params['maxSize'] ?? null;

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

        $offset = $params['offset'] ?? 0;

        if (!$onlyScope && $scope === 'User') {
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

        $builder = $this->entityManager->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        if ($scope !== 'User') {
            $countQuery = $this->entityManager->getQueryBuilder()
                ->select()
                ->fromQuery($builder->build(), 'c')
                ->select('COUNT:(c.id)', 'count')
                ->build();

            $sth = $this->entityManager->getQueryExecutor()->execute($countQuery);

            $row = $sth->fetch(PDO::FETCH_ASSOC);

            $totalCount = $row['count'];
        }

        $builder->order('dateStart', 'DESC');

        if ($scope === 'User') {
            $maxSizeQ++;
        } else {
            $builder->order('createdAt', 'DESC');
        }

        if ($maxSize) {
            $builder->limit($offset, $maxSizeQ);
        }

        $unionQuery = $builder->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        $boolAttributeList = ['hasAttachment'];

        $list = [];

        foreach ($rowList as $row) {
            foreach ($boolAttributeList as $attribute) {
                if (!array_key_exists($attribute, $row)) {
                    continue;
                }

                $row[$attribute] = $row[$attribute] == '1' ? true : false;
            }

            $list[] = $row;
        }

        if ($scope === 'User') {
            if ($maxSize && count($list) > $maxSize) {
                $totalCount = -1;

                unset($list[count($list) - 1]);
            }
            else {
                $totalCount = -2;
            }
        }

        return [
            'list' => $list,
            'total' => $totalCount,
        ];
    }

    protected function accessCheck($entity)
    {
        if ($entity->getEntityType() == 'User') {
            if (!$this->acl->checkUserPermission($entity, 'user')) {
                throw new Forbidden();
            }
        }
        else {
            if (!$this->acl->check($entity, 'read')) {
                throw new Forbidden();
            }
        }
    }

    public function findActivitiyEntityType(
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
            $statusList = $this->metadata->get(['scopes', $entityType, 'activityStatusList'], ['Planned']);
        }
        else {
            $statusList = $this->metadata->get(['scopes', $entityType, 'historyStatusList'], ['Held', 'Not Held']);
        }

        if ($entityType === 'Email' && $searchParams->getOrderBy() === 'dateStart') {
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

    public function getActivities(string $scope, string $id, array $params = [])
    {
        $entity = $this->entityManager->getEntity($scope, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        if (!$fetchAll) {
            if (!$this->metadata->get(['scopes', $params['scope'], 'activity'])) {
                throw new Error('Entity \'' . $params['scope'] . '\' is not an activity');
            }
        }

        $parts = [];

        $entityTypeList = $this->config->get('activitiesEntityList', ['Meeting', 'Call']);

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $params['scope'] !== $entityType) {
                continue;
            }

            if (!$this->acl->checkScope($entityType)) {
                continue;
            }

            if (!$this->metadata->get('scopes.' . $entityType . '.activity')) {
                continue;
            }

            $statusList = $this->metadata->get(['scopes', $entityType, 'activityStatusList'], ['Planned']);

            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList);
        }

        return $this->getResultFromQueryParts($parts, $scope, $id, $params);
    }

    public function getHistory(string $scope, string $id, array $params = [])
    {
        $entity = $this->entityManager->getEntity($scope, $id);
        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        if (!$fetchAll) {
            if (!$this->metadata->get(['scopes', $params['scope'], 'activity'])) {
                throw new Error('Entity \'' . $params['scope'] . '\' is not an activity');
            }
        }

        $parts = [];
        $entityTypeList = $this->config->get('historyEntityList', ['Meeting', 'Call', 'Email']);

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $params['scope'] !== $entityType) {
                continue;
            }

            if (!$this->acl->checkScope($entityType)) {
                continue;
            }

            if (!$this->metadata->get('scopes.' . $entityType . '.activity')) {
                continue;
            }

            $statusList = $this->metadata->get(
                ['scopes', $entityType, 'historyStatusList'], ['Held', 'Not Held']
            );

            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList);
        }

        $result = $this->getResultFromQueryParts($parts, $scope, $id, $params);

        foreach ($result['list'] as &$item) {
            if ($item['_scope'] == 'Email') {
                $item['dateSent'] = $item['dateStart'];
            }
        }

        return $result;
    }

    protected function getCalendarMeetingQuery(string $userId, string $from, string $to, bool $skipAcl): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from('Meeting');

        if (!$skipAcl) {
            $builder->withStrictAccessControl();
        }

        $select = [
            ['"Meeting"', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            'status',
            ['dateStartDate', 'dateStartDate'],
            ['dateEndDate', 'dateEndDate'],
            'parentType',
            'parentId',
            'createdAt',
        ];

        $seed = $this->entityManager->getEntity('Meeting');

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['""', $attribute];
        }

        return $builder
            ->buildQueryBuilder()
            ->select($select)
            ->leftJoin('users')
            ->where([
                'usersMiddle.userId' => $userId,
                'usersMiddle.status!=' => 'Declined',
                'OR' => [
                    [
                        'dateStart>=' => $from,
                        'dateStart<' => $to,
                    ],
                    [
                        'dateEnd>=' => $from,
                        'dateEnd<' => $to,
                    ],
                    [
                        'dateStart<=' => $from,
                        'dateEnd>=' => $to,
                    ],
                ],
            ])
            ->build();
    }

    protected function getCalendarCallQuery(string $userId, string $from, string $to, bool $skipAcl): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from('Call');

        if (!$skipAcl) {
            $builder->withStrictAccessControl();
        }

        $select = [
            ['"Call"', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            'status',
            ['""', 'dateStartDate'],
            ['""', 'dateEndDate'],
            'parentType',
            'parentId',
            'createdAt',
        ];

        $seed = $this->entityManager->getEntity('Call');

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['""', $attribute];
        }

        return $builder
            ->buildQueryBuilder()
            ->select($select)
            ->leftJoin('users')
            ->where([
                'usersMiddle.userId' => $userId,
                'usersMiddle.status!=' => 'Declined',
                'OR' => [
                    [
                        'dateStart>=' => $from,
                        'dateStart<' => $to,
                    ],
                    [
                        'dateEnd>=' => $from,
                        'dateEnd<' => $to,
                    ],
                    [
                        'dateStart<=' => $from,
                        'dateEnd>=' => $to,
                    ],
                ],
            ])
            ->build();
    }

    protected function getCalendarTaskQuery(string $userId, string $from, string $to, bool $skipAcl): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from('Task');

        if (!$skipAcl) {
            $builder->withStrictAccessControl();
        }

        $select = [
            ['"Task"', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            'status',
            ['dateStartDate', 'dateStartDate'],
            ['dateEndDate', 'dateEndDate'],
            'parentType',
            'parentId',
            'createdAt',
        ];

        $seed = $this->entityManager->getEntity('Task');

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['""', $attribute];
        }

        $queryBuilder = $builder
            ->buildQueryBuilder()
            ->select($select)
            ->where([
                'OR' => [
                    [
                        'dateEnd' => null,
                        'dateStart>=' => $from,
                        'dateStart<' => $to,
                    ],
                    [
                        'dateEnd>=' => $from,
                        'dateEnd<' => $to,
                    ],
                    [
                        'dateEndDate!=' => null,
                        'dateEndDate>=' => $from,
                        'dateEndDate<' => $to,
                    ],
                ],
            ]);

        if (
            $this->metadata->get(['entityDefs', 'Task', 'fields', 'assignedUsers', 'type']) === 'linkMultiple'
            &&
            !$this->metadata->get(['entityDefs', 'Task', 'fields', 'assignedUsers', 'disabled'])
        ) {
            $queryBuilder
                ->distinct()
                ->leftJoin('assignedUsers', 'assignedUsers')
                ->where([
                    'assignedUsers.id' => $userId,
                ]);
        }
        else {
            $queryBuilder->where([
                'assignedUserId' => $userId,
            ]);
        }

        return $queryBuilder->build();
    }

    protected function getCalenderBaseQuery(
        string $scope,
        string $userId,
        string $from,
        string $to,
        bool $skipAcl = false
    ): Select {

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($scope);

        if (!$skipAcl) {
            $builder->withStrictAccessControl();
        }

        $seed = $this->entityManager->getEntity($scope);

        $select = [
            ['"' . $scope . '"', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            ($seed->hasAttribute('status') ? ['status', 'status'] : ['""', 'status']),
            ($seed->hasAttribute('dateStartDate') ? ['dateStartDate', 'dateStartDate'] : ['""', 'dateStartDate']),
            ($seed->hasAttribute('dateEndDate') ? ['dateEndDate', 'dateEndDate'] : ['""', 'dateEndDate']),
            ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['""', 'parentType']),
            ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['""', 'parentId']),
            'createdAt',
        ];

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['""', $attribute];
        }

        $orGroup = [
            'assignedUserId' => $userId,
        ];

        if ($seed->hasRelation('users')) {
            $orGroup['usersMiddle.userId'] = $userId;
        }

        if ($seed->hasRelation('assignedUsers')) {
            $orGroup['assignedUsersMiddle.userId'] = $userId;
        }

        $queryBuilder = $builder
            ->buildQueryBuilder()
            ->select($select)
            ->where([
                'OR' => $orGroup,
                [
                    'OR' => [
                        [
                            'dateEnd' => null,
                            'dateStart>=' => $from,
                            'dateStart<' => $to,
                        ],
                        [
                            'dateStart>=' => $from,
                            'dateStart<' => $to,
                        ],
                        [
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to,
                        ],
                        [
                            'dateStart<=' => $from,
                            'dateEnd>=' => $to,
                        ],
                        [
                            'dateEndDate!=' => null,
                            'dateEndDate>=' => $from,
                            'dateEndDate<' => $to,
                        ],
                    ],
                ],
            ]);

        if ($seed->hasRelation('users')) {
            $queryBuilder
                ->distinct()
                ->leftJoin('users');
        }

        if ($seed->hasRelation('assignedUsers')) {
            $queryBuilder
                ->distinct()
                ->leftJoin('assignedUsers');
        }

        return $queryBuilder->build();
    }

    protected function getCalendarQuery(
        string $scope,
        string $userId,
        string $from,
        string $to,
        bool $skipAcl = false
    ): Select {

        if ($this->serviceFactory->checkExists($scope)) {
            $service = $this->serviceFactory->create($scope);

            if (method_exists($service, 'getCalenderQuery')) {
                return $service->getCalenderQuery($userId, $from, $to, $skipAcl);
            }
        }

        $methodName = 'getCalendar' . $scope . 'Query';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($userId, $from, $to, $skipAcl);
        }

        return $this->getCalenderBaseQuery($scope, $userId, $from, $to, $skipAcl);
    }

    protected function getActivitiesQuery(Entity $entity, $scope, array $statusList = [])
    {
        $serviceName = 'Activities' . $entity->getEntityType();

        if ($this->serviceFactory->checkExists($serviceName)) {
            $service = $this->serviceFactory->create($serviceName);

            $methodName = 'getActivities' . $scope . 'Query';

            if (method_exists($service, $methodName)) {
                return $service->$methodName($entity, $statusList);
            }
        }

        $methodName = 'getActivities' . $scope . 'Query';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList);
        }

        return $this->getActivitiesBaseQuery($entity, $scope, $statusList);
    }

    protected function getActivitiesBaseQuery(Entity $entity, string $scope, array $statusList)
    {
        $seed = $this->entityManager->getEntity($scope);

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($scope)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                ($seed->hasAttribute('dateStart') ? ['dateStart', 'dateStart'] : ['""', 'dateStart']),
                ($seed->hasAttribute('dateEnd') ? ['dateEnd', 'dateEnd'] : ['""', 'dateEnd']),
                ($seed->hasAttribute('dateStartDate') ?
                    ['dateStartDate', 'dateStartDate'] : ['""', 'dateStartDate']),
                ($seed->hasAttribute('dateEndDate') ?
                    ['dateEndDate', 'dateEndDate'] : ['""', 'dateEndDate']),
                ['"' . $scope . '"', '_scope'],
                ($seed->hasAttribute('assignedUserId') ?
                    ['assignedUserId', 'assignedUserId'] : ['""', 'assignedUserId']),
                ($seed->hasAttribute('assignedUserName') ? ['assignedUserName', 'assignedUserName'] :
                    ['""', 'assignedUserName']),
                ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['""', 'parentType']),
                ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['""', 'parentId']),
                'status',
                'createdAt',
                ['""', 'hasAttachment'],
            ]);

        if ($entity->getEntityType() === 'User') {
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

    public function getUsersTimeline($userIdList, $from, $to, $scopeList = null)
    {
        $brScopeList = $this->config->get('busyRangesEntityList') ?? ['Meeting', 'Call'];

        if ($scopeList) {
            foreach ($scopeList as $s) {
                if (!in_array($s, $brScopeList)) {
                    $brScopeList[] = $s;
                }
            }
        }

        $resultData = (object) [];

        foreach ($userIdList as $userId) {
            $userData = (object) [
                'eventList' => [],
                'busyRangeList' => []
            ];

            try {
                $userData->eventList = $this->getEventList($userId, $from, $to, $scopeList);

                $userData->busyRangeList = $this->getBusyRangeList(
                    $userId, $from, $to, $brScopeList, $userData->eventList
                );
            }
            catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new Exception($e->getMessage(), $e->getCode(), $e);
            }

            $resultData->$userId = $userData;
        }

        return $resultData;
    }

    public function getBusyRanges(
        array $userIdList, string $from, string $to,
        ?string $entityType = null, ?string $ignoreId = null, ?array $scopeList = null)
    {
        $scopeList = $this->config->get('busyRangesEntityList') ?? ['Meeting', 'Call'];

        if ($entityType) {
            if (!$this->acl->check($entityType)) {
                throw new Forbidden();
            }

            if (!in_array($entityType, $scopeList)) {
                $scopeList[] = $entityType;
            }
        }

        try {
            $dtFrom = new \DateTime($from);
            $dtTo = new \DateTime($to);
            $diff = $dtTo->diff($dtFrom, true);

            if ($diff->days > $this->config->get('busyRangesMaxRange', self::BUSY_RANGES_MAX_RANGE_DAYS)) {
                return [];
            }
        }
        catch (Exception $e) {
            throw new Error("BusyRanges: Bad date range.");
        }

        $ignoreList = null;

        if ($entityType && $ignoreId) {
            $ignoreList = [
                [
                    'id' => $ignoreId,
                    'scope' => $entityType,
                ]
            ];
        }

        $resultData = (object) [];

        foreach ($userIdList as $userId) {
            try {
                $busyRangeList = $this->getBusyRangeList($userId, $from, $to, $scopeList, $ignoreList);
            }
            catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new Exception($e->getMessage(), $e->getCode(), $e);
            }

            $resultData->$userId = $busyRangeList;
        }

        return $resultData;
    }

    public function getEventsForUsers($userIdList, $from, $to, $scopeList = null)
    {
        return $this->getUsersEventList($userIdList, $from, $to, $scopeList);
    }

    public function getUsersEventList($userIdList, $from, $to, $scopeList = null)
    {
        $resultList = [];

        foreach ($userIdList as $userId) {
            try {
                $userResultList = $this->getEvents($userId, $from, $to, $scopeList);
            }
            catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new Exception($e->getMessage(), $e->getCode(), $e);
            }

            foreach ($userResultList as $item) {
                $item['userId'] = $userId;

                $resultList[] = $item;
            }
        }

        return $resultList;
    }

    public function getEventsForTeams($teamIdList, $from, $to, $scopeList = null)
    {
        return $this->getTeamsEventList($teamIdList, $from, $to, $scopeList);
    }

    public function getTeamsEventList($teamIdList, $from, $to, $scopeList = null)
    {
        if ($this->acl->get('userPermission') === 'no') {
            throw new Forbidden("User Permission not allowing to view calendars of other users.");
        }

        if ($this->acl->get('userPermission') === 'team') {
            $userTeamIdList = $this->user->getLinkMultipleIdList('teams');

            foreach ($teamIdList as $teamId) {
                if (!in_array($teamId, $userTeamIdList)) {
                    throw new Forbidden("User Permission not allowing to view calendars of other teams.");
                }
            }
        }

        $userIdList = [];

        $userList = $this->entityManager
            ->getRDBRepository('User')
            ->select(['id', 'name'])
            ->leftJoin('teams')
            ->where([
                'isActive' => true,
                'teamsMiddle.teamId' => $teamIdList
            ])
            ->distinct()
            ->find();

        $userNames = (object) [];

        foreach ($userList as $user) {
            $userIdList[] = $user->getId();
            $userNames->{$user->getId()} = $user->get('name');
        }

        $eventList = [];

        foreach ($userIdList as $userId) {
            $userEventList = $this->getEventList($userId, $from, $to, $scopeList);

            foreach ($userEventList as $event) {
                foreach ($eventList as &$e) {
                    if ($e['scope'] == $event['scope'] && $e['id'] == $event['id']) {
                        $e['userIdList'][] = $userId;

                        continue 2;
                    }
                }

                $event['userIdList'] = [$userId];

                $eventList[] = $event;
            }
        }

        foreach ($eventList as &$event) {
            $eventUserNames = (object) [];
            foreach ($event['userIdList'] as $userId) {
                $eventUserNames->$userId = $userNames->$userId;
            }
            $event['userNameMap'] = $eventUserNames;
        }


        return $eventList;
    }

    public function getBusyRangeList($userId, $from, $to, $scopeList = null, ?array $ignoreEventList = null)
    {
        $rangeList = [];

        $eventListInitial = $this->getEventList($userId, $from, $to, $scopeList, true);

        $ignoreHash = (object) [];

        if ($ignoreEventList) {
            foreach ($ignoreEventList as $item) {
                $ignoreHash->{$item['id']} = true;
            }
        }

        $canceledStatusList = $this->metadata->get('app.calendar.canceledStatusList') ?? [];

        /** @var \stdClass[] $eventList */
        $eventList = [];

        foreach ($eventListInitial as $i => $item) {
            $eventList[$i] = (object) $item;
        }

        foreach ($eventList as $event) {
            if (empty($event->dateStart) || empty($event->dateEnd)) {
                continue;
            }

            if (in_array($event->status ?? null, $canceledStatusList)) {
                continue;
            }

            if (isset($ignoreHash->{$event->id})) {
                continue;
            }

            try {
                $start = new DateTime($event->dateStart);
                $end = new DateTime($event->dateEnd);

                foreach ($rangeList as &$range) {
                    if (
                        $start->getTimestamp() < $range->start->getTimestamp()
                        &&
                        $end->getTimestamp() > $range->end->getTimestamp()
                    ) {
                        $range->dateStart = $event->dateStart;
                        $range->start = $start;
                        $range->dateEnd = $event->dateEnd;
                        $range->end = $end;

                        continue 2;
                    }

                    if (
                        $start->getTimestamp() < $range->start->getTimestamp()
                        &&
                        $end->getTimestamp() > $range->start->getTimestamp()
                    ) {
                        $range->dateStart = $event->dateStart;
                        $range->start = $start;

                        if ($end->getTimestamp() > $range->end->getTimestamp()) {
                            $range->dateEnd = $event->dateEnd;
                            $range->end = $end;
                        }

                        continue 2;
                    }

                    if (
                        $start->getTimestamp() < $range->end->getTimestamp() &&
                        $end->getTimestamp() > $range->end->getTimestamp()
                    ) {
                        $range->dateEnd = $event->dateEnd;
                        $range->end = $end;

                        if ($start->getTimestamp() < $range->start->getTimestamp()) {
                            $range->dateStart = $event->dateStart;
                            $range->start = $start;
                        }

                        continue 2;
                    }
                }

                $busyItem = (object) [
                    'dateStart' => $event->dateStart,
                    'dateEnd' => $event->dateEnd,
                    'start' => $start,
                    'end' => $end,
                ];

                $rangeList[] = $busyItem;
            }
            catch (Exception $e) {}
        }

        foreach ($rangeList as &$item) {
            unset($item->start);
            unset($item->end);
        }

        return $rangeList;
    }

    public function getEventList($userId, $from, $to, $scopeList = null, $skipAcl = false)
    {
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$user) {
            throw new NotFound();
        }

        $this->accessCheck($user);

        $calendarEntityList = $this->config->get('calendarEntityList', []);

        if (is_null($scopeList)) {
            $scopeList = $calendarEntityList;
        }

        $queryList = [];

        foreach ($scopeList as $scope) {
            if (!in_array($scope, $calendarEntityList)) {
                continue;
            }

            if (!$this->acl->checkScope($scope)) {
                continue;
            }

            if (!$this->metadata->get(['scopes', $scope, 'calendar'])) {
                continue;
            }

            $subItem = [
                $this->getCalendarQuery($scope, $userId, $from, $to, $skipAcl)
            ];

            $queryList = array_merge($queryList, $subItem);
        }

        if (empty($queryList)) {
            return [];
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $rowList;
    }

    public function getEvents($userId, $from, $to, $scopeList = null, $skipAcl = false)
    {
        return $this->getEventList($userId, $from, $to, $scopeList, $skipAcl);
    }

    public function removeReminder(string $id): void
    {
        $builder = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('Reminder')
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

    public function getPopupNotifications(string $userId): array
    {
        $dt = new DateTime();

        $pastHours = $this->config->get('reminderPastHours', self::REMINDER_PAST_HOURS);

        $now = $dt->format('Y-m-d H:i:s');
        $nowShifted = $dt->sub(new DateInterval('PT'.strval($pastHours).'H'))->format('Y-m-d H:i:s');

        /** @var iterable<\Espo\Modules\Crm\Entities\Reminder> $reminderCollection */
        $reminderCollection = $this->entityManager
            ->getRDBRepository('Reminder')
            ->select(['id', 'entityType', 'entityId'])
            ->where([
                'type' => 'Popup',
                'userId' => $userId,
                'remindAt<=' => $now,
                'startAt>' => $nowShifted,
            ])
            ->find();

        $resultList = [];

        foreach ($reminderCollection as $reminder) {
            $reminderId = $reminder->get('id');
            $entityType = $reminder->get('entityType');
            $entityId = $reminder->get('entityId');

            $entity = $this->entityManager->getEntity($entityType, $entityId);

            $data = null;

            if (!$entity) {
                continue;
            }

            if (
                $entity instanceof CoreEntity &&
                $entity->hasLinkMultipleField('users')
            ) {
                $entity->loadLinkMultipleField('users', ['status' => 'acceptanceStatus']);
                $status = $entity->getLinkMultipleColumn('users', 'status', $userId);

                if ($status === 'Declined') {
                    $this->removeReminder($reminderId);

                    continue;
                }
            }

            $dateAttribute = 'dateStart';

            if ($entityType === 'Task') {
                $dateAttribute = 'dateEnd';
            }

            $data = [
                'id' => $entity->getId(),
                'entityType' => $entityType,
                $dateAttribute => $entity->get($dateAttribute),
                'name' => $entity->get('name'),
            ];

            $resultList[] = [
                'id' => $reminderId,
                'data' => $data,
            ];
        }

        return $resultList;
    }

    public function getUpcomingActivities(
        string $userId,
        array $params = [],
        ?array $entityTypeList = null,
        ?int $futureDays = null
    ) {

        $user = $this->entityManager->getEntity('User', $userId);

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
                $entityType !== 'Task'
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

        if (empty($queryList)) {
            return [
                'total' => 0,
                'list' => [],
            ];
        }

        $builder = $this->entityManager->getQueryBuilder()
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

        $offset = intval($params['offset']);
        $maxSize = intval($params['maxSize']);

        $unionQuery = $builder
            ->order('dateStart')
            ->order('dateEnd')
            ->order('name')
            ->limit($offset, $maxSize)
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $entityDataList = [];

        foreach ($rows as $row) {
            $entity = $this->entityManager->getEntity($row['entityType'], $row['id']);

            $entityData = $entity->getValueMap();
            $entityData->_scope = $entity->getEntityType();

            $entityDataList[] = $entityData;
        }

        return [
            'total' => $totalCount,
            'list' => $entityDataList,
        ];
    }

    protected function getUpcomingActivitiesEntityTypeQuery(
        string $entityType,
        array $params,
        UserEntity $user,
        int $futureDays
    ): Select {

        $beforeString = (new DateTime())
            ->modify('+' . $futureDays . ' days')
            ->format('Y-m-d H:i:s');

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->forUser($user)
            ->withBoolFilter('onlyMy')
            ->withStrictAccessControl();

        $primaryFilter = 'planned';

        if ($entityType === 'Task') {
            $primaryFilter = 'actual';
        }

        $builder->withPrimaryFilter($primaryFilter);

        if (!empty($params['textFilter'])) {
            $builder->withTextFilter($params['textFilter']);
        }

        $queryBuilder = $builder->buildQueryBuilder();

        $converter = $this->whereConverterFactory->create($entityType, $user);

        $timeZone = $this->getUserTimeZone($user);

        if ($entityType === 'Task') {
            $upcomingTaskFutureDays = $this->config->get(
                'activitiesUpcomingTaskFutureDays',
                self::UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS
            );

            $taskBeforeString = (new DateTime())
                ->modify('+' . $upcomingTaskFutureDays . ' days')
                ->format('Y-m-d H:i:s');

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

    protected function getUserTimeZone(UserEntity $user): string
    {
        $preferences = $this->entityManager->getEntity('Preferences', $user->getId());

        if ($preferences) {
            $timeZone = $preferences->get('timeZone');

            if ($timeZone) {
                return $timeZone;
            }
        }

        return $this->config->get('timeZone') ?? 'UTC';
    }
}
