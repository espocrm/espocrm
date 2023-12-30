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

namespace Espo\Modules\Crm\Tools\Calendar;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Call;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Task;
use Espo\Modules\Crm\Tools\Calendar\Items\BusyRange;
use Espo\Modules\Crm\Tools\Calendar\Items\Event;
use Espo\Modules\Crm\Tools\Calendar\Items\NonWorkingRange;
use Espo\Modules\Crm\Tools\Calendar\Items\WorkingRange;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select;
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
        private ServiceFactory $serviceFactory
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
        $from = $fetchParams->getFrom()->toString();
        $to = $fetchParams->getTo()->toString();
        $scopeList = $fetchParams->getScopeList();
        $skipAcl = $fetchParams->skipAcl();

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new NotFound();
        }

        $this->accessCheck($user);

        $calendarEntityList = $this->config->get('calendarEntityList', []);

        if (is_null($scopeList)) {
            $scopeList = $calendarEntityList;
        }

        $workingRangeItemList = [];

        if ($fetchParams->workingTimeRanges() || $fetchParams->workingTimeRangesInverted()) {
            $workingCalendar = $this->workingCalendarFactory->createForUser($user);

            $workingRangeItemList = $workingCalendar->isAvailable() ?
                $this->getWorkingRangeList($workingCalendar, $fetchParams) : [];
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
            if (!$this->acl->checkUserPermission($entity, 'user')) {
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
            'createdAt',
        ];

        $additionalAttributeList = $this->metadata->get(
            ['app', 'calendar', 'additionalAttributeList']
        ) ?? [];

        foreach ($additionalAttributeList as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ?
                [$attribute, $attribute] :
                ['null', $attribute];
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
            'createdAt',
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
            'createdAt',
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
            'createdAt',
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
            $this->metadata->get(['entityDefs', 'Task', 'fields', 'assignedUsers', 'type']) === 'linkMultiple' &&
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
                $eventList = $this->fetch($userId, $itemFetchParams);

                $busyRangeList = $this->fetchBusyRanges(
                    $userId,
                    $fetchParams->withScopeList($brScopeList),
                    array_filter($eventList, fn (Item $item) => $item instanceof Event)
                );
            }
            catch (Exception $e) {
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
        if ($this->acl->getPermissionLevel('userPermission') === Table::LEVEL_NO) {
            throw new Forbidden("User Permission not allowing to view calendars of other users.");
        }

        if ($this->acl->getPermissionLevel('userPermission') === Table::LEVEL_TEAM) {
            $userTeamIdList = $this->user->getLinkMultipleIdList('teams');

            foreach ($teamIdList as $teamId) {
                if (!in_array($teamId, $userTeamIdList)) {
                    throw new Forbidden("User Permission not allowing to view calendars of other teams.");
                }
            }
        }

        $userIdList = [];

        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['id', 'name'])
            ->leftJoin('teams')
            ->where([
                'isActive' => true,
                'teamsMiddle.teamId' => $teamIdList,
            ])
            ->distinct()
            ->find();

        $userNames = [];

        foreach ($userList as $user) {
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
                ->where(['id' => $teamIdList])
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
            }
            catch (Exception $e) {
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
     * @param Event[] $ignoreEventList
     * @return BusyRange[]
     * @throws NotFound
     * @throws Forbidden
     */
    public function fetchBusyRanges(string $userId, FetchParams $fetchParams, array $ignoreEventList = []): array
    {
        $rangeList = [];

        $eventList = $this->fetch($userId, $fetchParams->withSkipAcl(true));

        $ignoreHash = (object) [];

        foreach ($ignoreEventList as $event) {
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
            }
            catch (Exception) {}
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

        $extractor = new Extractor();

        $itemList = $fetchParams->workingTimeRangesInverted() ?
            (
            $fetchParams->isAgenda() ?
                $extractor->extractInversion($calendar, $from, $to) :
                $extractor->extractAllDayInversion($calendar, $from, $to)
            ) :
            (
            $fetchParams->isAgenda() ?
                $extractor->extract($calendar, $from, $to) :
                $extractor->extractAllDay($calendar, $from, $to)
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
        }
        catch (Exception) {
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
            $user = $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->getById($userId);

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

            try {
                $busyRangeList = $toReturn ?
                    $this->fetchBusyRanges($userId, $fetchParams, $ignoreList) :
                    [];
            }
            catch (Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }

                throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
            }

            $result[$userId] = array_merge($busyRangeList, $workingRangeItemList);
        }

        return $result;
    }
}
