<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Custom\Services;

use DateTime;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\EntityManager;
use PDO;

/**
 * Service providing aggregated activity statistics per Account for W5D4 dashlet.
 */
class ActivitySummaryService
{
    public const int DEFAULT_DAYS = 30;

    public const string TYPE_MEETING = 'Meeting';
    public const string TYPE_CALL = 'Call';
    public const string TYPE_TASK = 'Task';
    public const string TYPE_EMAIL = 'Email';

    /** @var string[] */
    public const array ACTIVITY_TYPES = [
        self::TYPE_MEETING,
        self::TYPE_CALL,
        self::TYPE_TASK,
        self::TYPE_EMAIL,
    ];

    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private Acl $acl
    ) {}

    /**
     * Get activity counts grouped by Account and Activity Type for the given period.
     *
     * @param int $days Number of past days to query (default 30).
     * @param ?DateTime $from Optional explicit start DateTime.
     * @return array<string, mixed> Structured result containing 'list', 'byAccount', 'total', and 'meta'.
     */
    public function getActivitySummary(int $days = self::DEFAULT_DAYS, ?DateTime $from = null): array
    {
        $startDate = $from ?? (new DateTime())->modify("-{$days} days");
        $thresholdDate = $startDate->format('Y-m-d 00:00:00');

        $meetingCounts = $this->getMeetingCounts($thresholdDate);
        $callCounts = $this->getCallCounts($thresholdDate);
        $taskCounts = $this->getTaskCounts($thresholdDate);
        $emailCounts = $this->getEmailCounts($thresholdDate);

        $allAccountIds = array_unique(array_merge(
            array_keys($meetingCounts),
            array_keys($callCounts),
            array_keys($taskCounts),
            array_keys($emailCounts)
        ));

        $allAccountIds = array_values(array_filter($allAccountIds, fn($id) => !empty($id)));

        $accountNameMap = $this->getAccountNames($allAccountIds);

        return $this->formatResult(
            $meetingCounts,
            $callCounts,
            $taskCounts,
            $emailCounts,
            $accountNameMap,
            $days,
            $thresholdDate
        );
    }

    /**
     * Query Meeting activities per Account.
     * Meeting links to Account via parentType = 'Account' and parentId = account.id.
     *
     * @return array<string, int> Map of accountId => count
     */
    public function getMeetingCounts(string $thresholdDate): array
    {
        if (!$this->acl->checkScope(self::TYPE_MEETING, Table::ACTION_READ)) {
            return [];
        }

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(self::TYPE_MEETING)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $queryBuilder
            ->select([
                ['parentId', 'accountId'],
                ['COUNT:(id)', 'count'],
            ])
            ->where([
                'parentType' => 'Account',
                'parentId!=' => null,
                'dateStart>=' => $thresholdDate,
                'deleted' => false,
            ])
            ->group('parentId');

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $counts = [];

        foreach ($rows as $row) {
            $accountId = (string) $row['accountId'];
            if (!empty($accountId)) {
                $counts[$accountId] = (int) $row['count'];
            }
        }

        return $counts;
    }

    /**
     * Query Call activities per Account.
     * Call can link to Account via:
     * 1. parentType = 'Account' and parentId = account.id
     * 2. accountId = account.id (when parentType is not 'Account')
     *
     * @return array<string, int> Map of accountId => count
     */
    public function getCallCounts(string $thresholdDate): array
    {
        if (!$this->acl->checkScope(self::TYPE_CALL, Table::ACTION_READ)) {
            return [];
        }

        $counts = [];

        // 1. Calls where parentType = 'Account'
        $qbParent = $this->selectBuilderFactory
            ->create()
            ->from(self::TYPE_CALL)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $qbParent
            ->select([
                ['parentId', 'accountId'],
                ['COUNT:(id)', 'count'],
            ])
            ->where([
                'parentType' => 'Account',
                'parentId!=' => null,
                'dateStart>=' => $thresholdDate,
                'deleted' => false,
            ])
            ->group('parentId');

        $sthParent = $this->entityManager
            ->getQueryExecutor()
            ->execute($qbParent->build());

        $rowsParent = $sthParent->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rowsParent as $row) {
            $accountId = (string) $row['accountId'];
            if (!empty($accountId)) {
                $counts[$accountId] = ($counts[$accountId] ?? 0) + (int) $row['count'];
            }
        }

        // 2. Calls where parentType != 'Account' (or null), but accountId is set
        $qbAccount = $this->selectBuilderFactory
            ->create()
            ->from(self::TYPE_CALL)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $qbAccount
            ->select([
                ['accountId', 'accountId'],
                ['COUNT:(id)', 'count'],
            ])
            ->where([
                'OR' => [
                    ['parentType!=' => 'Account'],
                    ['parentType' => null],
                ],
                'accountId!=' => null,
                'dateStart>=' => $thresholdDate,
                'deleted' => false,
            ])
            ->group('accountId');

        $sthAccount = $this->entityManager
            ->getQueryExecutor()
            ->execute($qbAccount->build());

        $rowsAccount = $sthAccount->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rowsAccount as $row) {
            $accountId = (string) $row['accountId'];
            if (!empty($accountId)) {
                $counts[$accountId] = ($counts[$accountId] ?? 0) + (int) $row['count'];
            }
        }

        return $counts;
    }

    /**
     * Query Task activities per Account.
     * Task can link to Account via:
     * 1. parentType = 'Account' and parentId = account.id
     * 2. accountId = account.id (when parentType is not 'Account')
     * Evaluates task date using dateEnd, dateStart, or createdAt within the window.
     *
     * @return array<string, int> Map of accountId => count
     */
    public function getTaskCounts(string $thresholdDate): array
    {
        if (!$this->acl->checkScope(self::TYPE_TASK, Table::ACTION_READ)) {
            return [];
        }

        $dateWhere = [
            'OR' => [
                ['dateEnd>=' => $thresholdDate],
                ['dateStart>=' => $thresholdDate],
                ['createdAt>=' => $thresholdDate],
            ],
        ];

        $counts = [];

        // 1. Tasks where parentType = 'Account'
        $qbParent = $this->selectBuilderFactory
            ->create()
            ->from(self::TYPE_TASK)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $qbParent
            ->select([
                ['parentId', 'accountId'],
                ['COUNT:(id)', 'count'],
            ])
            ->where(array_merge([
                'parentType' => 'Account',
                'parentId!=' => null,
                'deleted' => false,
            ], $dateWhere))
            ->group('parentId');

        $sthParent = $this->entityManager
            ->getQueryExecutor()
            ->execute($qbParent->build());

        $rowsParent = $sthParent->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rowsParent as $row) {
            $accountId = (string) $row['accountId'];
            if (!empty($accountId)) {
                $counts[$accountId] = ($counts[$accountId] ?? 0) + (int) $row['count'];
            }
        }

        // 2. Tasks where parentType != 'Account' (or null), but accountId is set
        $qbAccount = $this->selectBuilderFactory
            ->create()
            ->from(self::TYPE_TASK)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $qbAccount
            ->select([
                ['accountId', 'accountId'],
                ['COUNT:(id)', 'count'],
            ])
            ->where(array_merge([
                'OR' => [
                    ['parentType!=' => 'Account'],
                    ['parentType' => null],
                ],
                'accountId!=' => null,
                'deleted' => false,
            ], $dateWhere))
            ->group('accountId');

        $sthAccount = $this->entityManager
            ->getQueryExecutor()
            ->execute($qbAccount->build());

        $rowsAccount = $sthAccount->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rowsAccount as $row) {
            $accountId = (string) $row['accountId'];
            if (!empty($accountId)) {
                $counts[$accountId] = ($counts[$accountId] ?? 0) + (int) $row['count'];
            }
        }

        return $counts;
    }

    /**
     * Query Email activities per Account.
     * Email links to Account via parentType = 'Account' and parentId = account.id.
     * Evaluates email date using dateSent (or createdAt if dateSent is null).
     *
     * @return array<string, int> Map of accountId => count
     */
    public function getEmailCounts(string $thresholdDate): array
    {
        if (!$this->acl->checkScope(self::TYPE_EMAIL, Table::ACTION_READ)) {
            return [];
        }

        $dateWhere = [
            'OR' => [
                ['dateSent>=' => $thresholdDate],
                [
                    'dateSent' => null,
                    'createdAt>=' => $thresholdDate,
                ],
            ],
        ];

        $qbEmail = $this->selectBuilderFactory
            ->create()
            ->from(self::TYPE_EMAIL)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $qbEmail
            ->select([
                ['parentId', 'accountId'],
                ['COUNT:(id)', 'count'],
            ])
            ->where(array_merge([
                'parentType' => 'Account',
                'parentId!=' => null,
                'deleted' => false,
            ], $dateWhere))
            ->group('parentId');

        $sthEmail = $this->entityManager
            ->getQueryExecutor()
            ->execute($qbEmail->build());

        $rowsEmail = $sthEmail->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $counts = [];

        foreach ($rowsEmail as $row) {
            $accountId = (string) $row['accountId'];
            if (!empty($accountId)) {
                $counts[$accountId] = (int) $row['count'];
            }
        }

        return $counts;
    }

    /**
     * Resolve account names for the given account IDs in a single batch query.
     * Applies strict access control to ensure users only see permitted accounts.
     *
     * @param string[] $accountIds
     * @return array<string, string> Map of accountId => accountName
     */
    public function getAccountNames(array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }

        if (!$this->acl->checkScope('Account', Table::ACTION_READ)) {
            return [];
        }

        $qb = $this->selectBuilderFactory
            ->create()
            ->from('Account')
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $qb
            ->select(['id', 'name'])
            ->where([
                'id' => $accountIds,
                'deleted' => false,
            ]);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($qb->build());

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];

        foreach ($rows as $row) {
            $map[(string) $row['id']] = (string) $row['name'];
        }

        return $map;
    }

    /**
     * Assemble final payload suitable for both flat and grouped bar chart visualizations.
     *
     * @param array<string, int> $meetingCounts
     * @param array<string, int> $callCounts
     * @param array<string, int> $taskCounts
     * @param array<string, int> $emailCounts
     * @param array<string, string> $accountNameMap
     * @param int $days
     * @param string $thresholdDate
     * @return array<string, mixed>
     */
    private function formatResult(
        array $meetingCounts,
        array $callCounts,
        array $taskCounts,
        array $emailCounts,
        array $accountNameMap,
        int $days,
        string $thresholdDate
    ): array {
        $typeCountsMap = [
            self::TYPE_MEETING => $meetingCounts,
            self::TYPE_CALL => $callCounts,
            self::TYPE_TASK => $taskCounts,
            self::TYPE_EMAIL => $emailCounts,
        ];

        $list = [];
        $byAccount = [];

        $totalByType = [
            self::TYPE_MEETING => 0,
            self::TYPE_CALL => 0,
            self::TYPE_TASK => 0,
            self::TYPE_EMAIL => 0,
            'all' => 0,
        ];

        foreach ($accountNameMap as $accountId => $accountName) {
            $accountTotal = 0;

            $byAccount[$accountId] = [
                'accountId' => $accountId,
                'accountName' => $accountName,
                self::TYPE_MEETING => 0,
                self::TYPE_CALL => 0,
                self::TYPE_TASK => 0,
                self::TYPE_EMAIL => 0,
                'total' => 0,
            ];

            foreach (self::ACTIVITY_TYPES as $type) {
                $count = $typeCountsMap[$type][$accountId] ?? 0;
                $byAccount[$accountId][$type] = $count;
                $accountTotal += $count;
                $totalByType[$type] += $count;

                if ($count > 0) {
                    $list[] = [
                        'accountId' => $accountId,
                        'accountName' => $accountName,
                        'activityType' => $type,
                        'count' => $count,
                    ];
                }
            }

            $byAccount[$accountId]['total'] = $accountTotal;
            $totalByType['all'] += $accountTotal;
        }

        // Sort list primarily by accountName, secondarily by activityType
        usort($list, function ($a, $b) {
            $cmp = strcmp($a['accountName'], $b['accountName']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($a['activityType'], $b['activityType']);
        });

        // Sort byAccount by total activity count descending
        uasort($byAccount, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'list' => $list,
            'byAccount' => array_values($byAccount),
            'total' => $totalByType,
            'meta' => [
                'days' => $days,
                'from' => $thresholdDate,
                'accountCount' => count($byAccount),
                'activityTypes' => self::ACTIVITY_TYPES,
            ],
        ];
    }
}
