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

namespace Espo\Modules\Crm\Tools\Calendar;

use DateTimeZone;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Select\Helpers\RelationQueryHelper;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Task;
use Espo\Modules\Crm\Tools\Calendar\FreeBusy\FetchParams as FetchBusyParams;
use Espo\Modules\Crm\Tools\Calendar\Items\BusyRange;
use Espo\Modules\Crm\Tools\Calendar\Items\Event;
use Espo\Modules\Crm\Tools\Calendar\Items\NonWorkingRange;
use Espo\Modules\Crm\Tools\Calendar\Items\WorkingRange;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Select;
use Espo\ORM\Type\RelationType;
use Espo\Tools\WorkingTime\Calendar as WorkingCalendar;
use Espo\Tools\WorkingTime\CalendarFactory as WorkingCalendarFactory;
use Espo\Core\ServiceFactory;
use Espo\Tools\WorkingTime\Extractor;

use PDO;
use Exception;
use RuntimeException;

class Service
{
    private const BUSY_RANGES_MAX_RANGE_DAYS = 20;
    /** @var array<string, string[]> */
    private array $entityTypeCanceledStatusListCacheMap = [];

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private WorkingCalendarFactory $workingCalendarFactory,
        private Acl $acl,
        private Metadata $metadata,
        private SelectBuilderFactory $selectBuilderFactory,
        private User $user,
        private ServiceFactory $serviceFactory,
        private RelationQueryHelper $relationQueryHelper,
        private Config\ApplicationConfig $applicationConfig,
    ) {}

    /**
     * Fetch events and ranges.
     *
     * @return (Event|NonWorkingRange|WorkingRange)[]
     * @throws NotFound
     * @throws Forbidden
     */
    public function fetch(string $userId, FetchParams $fetchParams): array
    {
        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            throw new NotFound();
        }

        return $this->fetchInternal($user, $fetchParams);

    }

    /**
     * Fetch events and ranges.
     *
     * @return (Event|NonWorkingRange|WorkingRange)[]
     * @throws Forbidden
     */
    private function fetchInternal(User $user, FetchParams $fetchParams, bool $skipAccessCheck = false): array
    {
        $from = $fetchParams->getFrom()->toString();
        $to = $fetchParams->getTo()->toString();
        $scopeList = $fetchParams->getScopeList();
        $skipAcl = $fetchParams->skipAcl();

        if (!$skipAccessCheck) {
            $this->accessCheck($user);
        }

        $calendarEntityList = $this->config->get('calendarEntityList', []);

        if (is_null($scopeList)) {
            $scopeList = $calendarEntityList;
        }

        $workingRangeItemList = [];

        if ($fetchParams->workingTimeRanges() || $fetchParams->workingTimeRangesInverted()) {
            $workingCalendar = $this->workingCalendarFactory->createForUser($user);

            $workingRangeItemList = $workingCalendar->isAvailable() ?
                $this->getWorkingRangeList($workingCalendar, $fetchParams) : [];

            if (
                $workingCalendar->isAvailable() &&
                !$fetchParams->workingTimeRangesInverted() &&
                $workingRangeItemList === []
            ) {
                // Empty range for fullcalendar. The whole range is non-working.
                $workingRangeItemList = [
                    new WorkingRange($fetchParams->getFrom(), $fetchParams->getFrom())
                ];
            }
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
                $this->getCalendarQuery($scope, $user->getId(), $from, $to, $skipAcl)
            ];

            $queryList = array_merge($queryList, $subItem);
        }

        if ($queryList === []) {
            return $workingRangeItemList;
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($unionQuery);

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $eventList = [];

        foreach ($rowList as $row) {
            $eventList[] = new Event(
                $row['dateStart'] ? DateTimeField::fromString($row['dateStart']) : null,
                $row['dateEnd'] ? DateTimeField::fromString($row['dateEnd']) : null,
                $row['scope'],
                $row
            );
        }

        return array_merge($eventList, $workingRangeItemList);
    }

    /**
     * @throws Forbidden
     */
    private function accessCheck(Entity $entity): void
    {
        if ($entity instanceof User) {
            if (!$this->acl->checkUserPermission($entity, Acl\Permission::USER_CALENDAR)) {
                throw new Forbidden();
            }

            return;
        }

        if (!$this->acl->check($entity, Table::ACTION_READ)) {
            throw new Forbidden();
        }
    }

    private function getCalendarQuery(
        string $scope,
        string $userId,
        string $from,
        string $to,
        bool $skipAcl = false
    ): Select {

        if ($this->serviceFactory->checkExists($scope)) {
            // For backward compatibility.
            $service = $this->serviceFactory->create($scope);

            if (method_exists($service, 'getCalenderQuery')) {
                return $service->getCalenderQuery($userId, $from, $to, $skipAcl);
            }
        }

        if ($scope === Meeting::ENTITY_TYPE) {
            return $this->getCalendarMeetingQuery($userId, $from, $to, $skipAcl);
        }

        if ($scope === Call::ENTITY_TYPE) {
            return $this->getCalendarCallQuery($userId, $from, $to, $skipAcl);
        }

        if ($scope === Task::ENTITY_TYPE) {
            return $this->getCalendarTaskQuery($userId, $from, $to, $skipAcl);
        }

        return $this->getCalenderBaseQuery($scope, $userId, $from, $to, $skipAcl);
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

        $seed = $this->entityManager->getNewEntity($scope);

        $select = [
            ['"' . $scope . '"', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            ($seed->hasAttribute('status') ? ['status', 'status'] : ['null', 'status']),
            ($seed->hasAttribute('dateStartDate') ? ['dateStartDate', 'dateStartDate'] : ['null', 'dateStartDate']),
            ($seed->hasAttribute('dateEndDate') ? ['dateEndDate', 'dateEndDate'] : ['null', 'dateEndDate']),
            ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['null', 'parentType']),
            ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['null', 'parentId']),
            Field::CREATED_AT,
        ];

        $additionalAttributeList = $this->metadata->get(['app', 'calendar', 'additionalAttributeList']) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['null', $attribute];
        }

        $hasAssignedUsers = $seed->hasRelation(Field::ASSIGNED_USERS);

        try {
            $queryBuilder = $builder->buildQueryBuilder();
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException($e->getMessage());
        }

        $orBuilder = OrGroup::createBuilder();

        if ($hasAssignedUsers) {
            $orBuilder->add(
                $this->relationQueryHelper->prepareAssignedUsersWhere($scope, $userId)
            );
        } else {
            $orBuilder->add(
                Cond::equal(Expr::column('assignedUserId'), $userId)
            );
        }

        // @todo Introduce a metadata scopes parameter 'usersLink'. Populate in an upgrade script.
        $usersLink = 'users';

        $usersRelation = $this->entityManager
            ->getDefs()
            ->getEntity($scope)
            ->tryGetRelation($usersLink);

        if (
            $usersRelation &&
            $usersRelation->getType() === RelationType::MANY_MANY &&
            $usersRelation->getForeignEntityType() === User::ENTITY_TYPE
        ) {
            $whereItem = $this->relationQueryHelper->prepareLinkWhereMany($scope, $usersLink, $userId);

            $orBuilder->add($whereItem);
        }

        $queryBuilder
            ->select($select)
            ->where($orBuilder->build())
            ->where([
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

        return $queryBuilder->build();
    }

    protected function getCalendarMeetingQuery(string $userId, string $from, string $to, bool $skipAcl): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Meeting::ENTITY_TYPE);

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
            Field::CREATED_AT,
        ];

        $seed = $this->entityManager->getNewEntity(Meeting::ENTITY_TYPE);

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['null', $attribute];
        }

        try {
            return $builder
                ->buildQueryBuilder()
                ->select($select)
                ->leftJoin('users')
                ->where([
                    'usersMiddle.userId' => $userId,
                    'usersMiddle.status!=' => Meeting::ATTENDEE_STATUS_DECLINED,
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
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    protected function getCalendarCallQuery(string $userId, string $from, string $to, bool $skipAcl): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Call::ENTITY_TYPE);

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
            ['null', 'dateStartDate'],
            ['null', 'dateEndDate'],
            'parentType',
            'parentId',
            Field::CREATED_AT,
        ];

        $seed = $this->entityManager->getNewEntity(Call::ENTITY_TYPE);

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['null', $attribute];
        }

        try {
            return $builder
                ->buildQueryBuilder()
                ->select($select)
                ->leftJoin('users')
                ->where([
                    'usersMiddle.userId' => $userId,
                    'usersMiddle.status!=' => Meeting::ATTENDEE_STATUS_DECLINED,
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
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    protected function getCalendarTaskQuery(string $userId, string $from, string $to, bool $skipAcl): Select
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from(Task::ENTITY_TYPE);

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
            Field::CREATED_AT,
        ];

        $seed = $this->entityManager->getNewEntity(Task::ENTITY_TYPE);

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['null', $attribute];
        }

        try {
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
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException($e->getMessage());
        }

        if (
            $this->metadata->get(['entityDefs', 'Task', 'fields', Field::ASSIGNED_USERS, 'type']) ===
                FieldType::LINK_MULTIPLE &&
            !$this->metadata->get(['entityDefs', 'Task', 'fields', Field::ASSIGNED_USERS, 'disabled'])
        ) {
            $queryBuilder->where(
                $this->relationQueryHelper->prepareAssignedUsersWhere(Task::ENTITY_TYPE, $userId)
            );
        } else {
            $queryBuilder->where([
                'assignedUserId' => $userId,
            ]);
        }

        return $queryBuilder->build();
    }

    /**
     * @param string[] $userIdList
     * @return array<string,Item[]>
     */
    public function fetchTimelineForUsers(array $userIdList, FetchParams $fetchParams): array
    {
        $scopeList = $fetchParams->getScopeList();

        $brScopeList = $this->config->get('busyRangesEntityList') ?? [Meeting::ENTITY_TYPE, Call::ENTITY_TYPE];

        if ($scopeList) {
            foreach ($scopeList as $s) {
                if (!in_array($s, $brScopeList)) {
                    $brScopeList[] = $s;
                }
            }
        }

        $itemFetchParams = $fetchParams
            ->withIsAgenda()
            ->withWorkingTimeRangesInverted();

        $resultData = [];

        foreach ($userIdList as $userId) {
            try {
                $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

                if (!$user) {
                    throw new NotFound("User $userId not found.");
                }

                $eventList = $this->fetchInternal($user, $itemFetchParams);

                $busyParams = new FetchBusyParams(
                    from: $fetchParams->getFrom(),
                    to: $fetchParams->getTo(),
                    accessCheck: true,
                    ignoreEventList: array_filter($eventList, fn (Item $item) => $item instanceof Event),
                );

                $busyRangeList = $this->fetchBusyRanges($user, $busyParams, $fetchParams->withScopeList($brScopeList));
            } catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
            }

            $resultData[$userId] = array_merge($eventList, $busyRangeList);
        }

        return $resultData;
    }

    /**
     * @param string[] $teamIdList
     * @return Item[]
     * @throws Forbidden
     * @throws NotFound
     */
    public function fetchForTeams(array $teamIdList, FetchParams $fetchParams): array
    {
        if ($this->acl->getPermissionLevel(Acl\Permission::USER_CALENDAR) === Table::LEVEL_NO) {
            throw new Forbidden("User Calendar Permission not allowing to view calendars of other users.");
        }

        if ($this->acl->getPermissionLevel(Acl\Permission::USER_CALENDAR) === Table::LEVEL_TEAM) {
            $userTeamIdList = $this->user->getLinkMultipleIdList(Field::TEAMS);

            foreach ($teamIdList as $teamId) {
                if (!in_array($teamId, $userTeamIdList)) {
                    throw new Forbidden("User Calendar Permission not allowing to view calendars of other teams.");
                }
            }
        }

        $userIdList = [];

        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select([Attribute::ID, 'name'])
            ->where(['isActive' => true])
            ->where(
                $this->relationQueryHelper->prepareLinkWhereMany(User::ENTITY_TYPE, 'teams', $teamIdList)
            )
            ->find();

        $userNames = [];

        foreach ($users as $user) {
            $userIdList[] = $user->getId();

            $userNames[$user->getId()] = $user->getName();
        }

        /** @var Event[] $eventList */
        $eventList = [];

        foreach ($userIdList as $userId) {
            $userEventList = $this->fetch($userId, $fetchParams);

            foreach ($userEventList as $event) {
                if (!$event instanceof Event) {
                    continue;
                }

                foreach ($eventList as $i => $e) {
                    if (
                        $e->getEntityType() === $event->getEntityType() &&
                        $e->getId() === $event->getId()
                    ) {
                        $eventList[$i] = $e->withUserIdAdded($userId);

                        continue 2;
                    }
                }

                $eventList[] = $event->withUserIdAdded($userId);
            }
        }

        foreach ($eventList as $i => $event) {
            $eventUserNames = [];

            foreach ($event->getUserIdList() as $userId) {
                $name = $userNames[$userId] ?? null;

                if ($name !== null) {
                    $eventUserNames[$userId] = $name;
                }
            }

            $eventList[$i] = $event->withUserNameMap($eventUserNames);
        }

        return array_merge(
            $eventList,
            $this->fetchWorkingRangeListForTeams($teamIdList, $fetchParams)
        );
    }

    /**
     * @param string[] $teamIdList
     * @param FetchParams $fetchParams
     * @return NonWorkingRange[]
     */
    private function fetchWorkingRangeListForTeams(array $teamIdList, FetchParams $fetchParams): array
    {
        $teamList = iterator_to_array(
            $this->entityManager
                ->getRDBRepositoryByClass(Team::class)
                ->where([Attribute::ID => $teamIdList])
                ->find()
        );

        if (!count($teamList)) {
            return [];
        }

        $workingTimeCalendarIdList = [];

        foreach ($teamList as $team) {
            $workingTimeCalendarLink = $team->getWorkingTimeCalendar();

            $workingTimeCalendarId = $workingTimeCalendarLink ? $workingTimeCalendarLink->getId() : null;

            if ($workingTimeCalendarId) {
                $workingTimeCalendarIdList[] = $workingTimeCalendarId;
            }
        }

        if (
            count($workingTimeCalendarIdList) !== count($teamList) ||
            count(array_unique($workingTimeCalendarIdList)) !== 1
        ) {
            return [];
        }

        $workingCalendar = $this->workingCalendarFactory->createForTeam($teamList[0]);

        if (!$workingCalendar->isAvailable()) {
            return [];
        }

        /** @var NonWorkingRange[] */
        return $this->getWorkingRangeList($workingCalendar, $fetchParams);
    }

    /**
     * Fetch for users.
     *
     * @param string[] $userIdList
     * @return Item[]
     */
    public function fetchForUsers(array $userIdList, FetchParams $fetchParams): array
    {
        $itemList = [];

        foreach ($userIdList as $userId) {
            try {
                $userItemList = $this->fetch($userId, $fetchParams);
            } catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
            }

            foreach ($userItemList as $event) {
                if (!$event instanceof Event) {
                    continue;
                }

                $itemList[] = $event->withAttribute('userId', $userId);
            }
        }

        return $itemList;
    }

    /**
     * @internal Use FreeBusy/Service instead.
     *
     * @return BusyRange[]
     * @throws Forbidden
     */
    public function fetchBusyRanges(User $user, FetchBusyParams $params, FetchParams $fetchParams): array
    {
        $rangeList = [];

        $fetchParams = $fetchParams
            ->withFrom($params->from)
            ->withTo($params->to);

        if ($fetchParams->getScopeList() === null) {
            $fetchParams = $fetchParams->withScopeList(
                $this->config->get('busyRangesEntityList') ??
                [Meeting::ENTITY_TYPE, Call::ENTITY_TYPE]
            );
        }

        $eventList = $this->fetchInternal($user, $fetchParams->withSkipAcl(), !$params->accessCheck);

        $ignoreHash = (object) [];

        foreach ($params->ignoreEventList as $event) {
            $id = $event->getId();

            if ($id) {
                $ignoreHash->$id = true;
            }
        }

        foreach ($eventList as $event) {
            if (!$event instanceof Event) {
                continue;
            }

            $start = $event->getStart();
            $end = $event->getEnd();
            $status = $event->getAttribute('status');
            $id = $event->getId();

            if (!$start || !$end) {
                continue;
            }

            if (in_array($status, $this->getEntityTypeCanceledStatusList($event->getEntityType()))) {
                continue;
            }

            if (isset($ignoreHash->$id)) {
                continue;
            }

            try {
                foreach ($rangeList as $range) {
                    if (
                        $start->toTimestamp() < $range->start->getTimestamp() &&
                        $end->toTimestamp() > $range->end->getTimestamp()
                    ) {
                        $range->dateStart = $start->toString();
                        $range->start = $start;
                        $range->dateEnd = $end->toString();
                        $range->end = $end;

                        continue 2;
                    }

                    if (
                        $start->toTimestamp() < $range->start->getTimestamp() &&
                        $end->toTimestamp() > $range->start->getTimestamp()
                    ) {
                        $range->dateStart = $start->toString();
                        $range->start = $start;

                        if ($end->toTimestamp() > $range->end->getTimestamp()) {
                            $range->dateEnd = $end->toString();
                            $range->end = $end;
                        }

                        continue 2;
                    }

                    if (
                        $start->toTimestamp() < $range->end->getTimestamp() &&
                        $end->toTimestamp() > $range->end->getTimestamp()
                    ) {
                        $range->dateEnd = $end->toString();
                        $range->end = $end;

                        if ($start->toTimestamp() < $range->start->getTimestamp()) {
                            $range->dateStart = $start->toString();
                            $range->start = $start;
                        }

                        continue 2;
                    }
                }

                $busyItem = (object) [
                    'dateStart' => $start->toString(),
                    'dateEnd' => $end->toString(),
                    'start' => $start,
                    'end' => $end,
                ];

                $rangeList[] = $busyItem;
            } catch (Exception) {}
        }

        return array_map(
            function ($item) {
                return new BusyRange(
                    DateTimeField::fromString($item->dateStart),
                    DateTimeField::fromString($item->dateEnd)
                );
            },
            $rangeList
        );
    }

    /**
     * @return string[]
     */
    private function getEntityTypeCanceledStatusList(string $entityType): array
    {
        $this->entityTypeCanceledStatusListCacheMap[$entityType] ??=
            $this->metadata->get(['scopes', $entityType, 'canceledStatusList']) ?? [];

        return $this->entityTypeCanceledStatusListCacheMap[$entityType];
    }

    /**
     * @return array<int, WorkingRange|NonWorkingRange>
     */
    private function getWorkingRangeList(WorkingCalendar $calendar, FetchParams $fetchParams): array
    {
        $from = $fetchParams->getFrom();
        $to = $fetchParams->getTo();

        $timezone = !$fetchParams->isAgenda() ?
            $this->getCurrentUserTimezone() : null;

        $extractor = new Extractor();

        $itemList = $fetchParams->workingTimeRangesInverted() ?
            (
            $fetchParams->isAgenda() ?
                $extractor->extractInversion($calendar, $from, $to) :
                $extractor->extractAllDayInversion($calendar, $from, $to, $timezone)
            ) :
            (
            $fetchParams->isAgenda() ?
                $extractor->extract($calendar, $from, $to) :
                $extractor->extractAllDay($calendar, $from, $to, $timezone)
            );

        $list = [];

        foreach ($itemList as $item) {
            if ($fetchParams->workingTimeRangesInverted()) {
                $list[] = new NonWorkingRange($item[0], $item[1]);

                continue;
            }

            $list[] = new WorkingRange($item[0], $item[1]);
        }

        return $list;
    }

    /**
     * @param string[] $userIdList
     * @return array<string, (BusyRange|NonWorkingRange)[]>
     * @throws Forbidden
     * @throws Error
     */
    public function fetchBusyRangesForUsers(
        array $userIdList,
        DateTimeField $from,
        DateTimeField $to,
        ?string $entityType = null,
        ?string $ignoreId = null
    ): array {

        $scopeList = $this->config->get('busyRangesEntityList') ?? [Meeting::ENTITY_TYPE, Call::ENTITY_TYPE];

        if ($entityType) {
            if (!$this->acl->check($entityType)) {
                throw new Forbidden();
            }

            if (!in_array($entityType, $scopeList)) {
                $scopeList[] = $entityType;
            }
        }

        $toReturn = true;

        try {
            $diff = $to->toDateTime()->diff($from->toDateTime(), true);

            if ($diff->days > $this->config->get('busyRangesMaxRange', self::BUSY_RANGES_MAX_RANGE_DAYS)) {
                $toReturn = false;
            }
        } catch (Exception) {
            throw new Error("BusyRanges: Bad date range.");
        }

        $ignoreList = [];

        if ($entityType && $ignoreId) {
            $ignoreList[] = new Event(
                DateTimeField::createNow(),
                DateTimeField::createNow(),
                $entityType,
                ['id' => $ignoreId]
            );
        }

        $result = [];

        $fetchParams = FetchParams
            ::create($from, $to)
            ->withScopeList($scopeList);

        foreach ($userIdList as $userId) {
            $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

            if (!$user) {
                continue;
            }

            $workingCalendar = $this->workingCalendarFactory->createForUser($user);

            /** @var NonWorkingRange[] $workingRangeItemList */
            $workingRangeItemList = $workingCalendar->isAvailable() ?
                $this->getWorkingRangeList(
                    $workingCalendar,
                    $fetchParams
                        ->withWorkingTimeRangesInverted()
                        ->withIsAgenda()
                ) :
                [];

            $busyParams = new FetchBusyParams(
                from: $fetchParams->getFrom(),
                to: $fetchParams->getTo(),
                accessCheck: true,
                ignoreEventList: $ignoreList,
            );

            try {
                $busyRangeList = $toReturn ?
                    $this->fetchBusyRanges($user, $busyParams, $fetchParams) : [];
            } catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
            }

            $result[$userId] = array_merge($busyRangeList, $workingRangeItemList);
        }

        return $result;
    }

    private function getCurrentUserTimezone(): DateTimeZone
    {
        try {
            return new DateTimeZone($this->getCurrentUserTimezoneString());
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @todo Move to a separate class.
     */
    private function getCurrentUserTimezoneString(): string
    {
        $preferences = $this->entityManager
            ->getRepositoryByClass(Preferences::class)
            ->getById($this->user->getId());

        if ($preferences && $preferences->getTimeZone()) {
            return $preferences->getTimeZone();
        }

        return $this->applicationConfig->getTimeZone();
    }
}
