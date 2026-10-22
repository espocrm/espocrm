<?php

namespace Espo\Custom\Tools\ActivitySummary;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\EntityManager;
use stdClass;

class Report
{
    public function __construct(
        private Acl $acl,
        private SelectBuilderFactory $selectBuilderFactory,
        private EntityManager $entityManager
    ) {}

    /**
     * Return visible Account activity totals for the last 30 days.
     * Each activity query uses Espo's strict-access query builder.
     *
     * @throws Forbidden
     */
    public function run(): stdClass
    {
        foreach (['Account', 'Call', 'Meeting', 'Email'] as $scope) {
            if (!$this->acl->checkScope($scope, Table::ACTION_READ)) {
                throw new Forbidden();
            }
        }

        $from = (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s');
        $countMap = [];

        $this->collectCounts($countMap, 'Call', 'dateStart', $from);
        $this->collectCounts($countMap, 'Meeting', 'dateStart', $from);
        $this->collectCounts($countMap, 'Email', 'dateSent', $from);

        if ($countMap === []) {
            return (object) ['from' => $from, 'dataList' => []];
        }

        $accountQuery = $this->selectBuilderFactory
            ->create()
            ->from('Account')
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $accountQuery
            ->select(['id', 'name'])
            ->where(['id' => array_keys($countMap)]);

        $accountRows = $this->entityManager
            ->getQueryExecutor()
            ->execute($accountQuery->build())
            ->fetchAll() ?: [];

        $dataList = [];

        foreach ($accountRows as $account) {
            $id = $account['id'];
            $counts = $countMap[$id];
            $counts['accountId'] = $id;
            $counts['accountName'] = $account['name'];
            $counts['total'] = $counts['Call'] + $counts['Meeting'] + $counts['Email'];
            $dataList[] = $counts;
        }

        usort($dataList, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return (object) [
            'from' => $from,
            'dataList' => array_slice($dataList, 0, 10),
        ];
    }

    /** @param array<string, array{Call: int, Meeting: int, Email: int}> $countMap */
    private function collectCounts(array &$countMap, string $entityType, string $dateField, string $from): void
    {
        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $queryBuilder
            ->select(['parentId', ['COUNT:id', 'count']])
            ->where([
                'parentType' => 'Account',
                $dateField . '>=' => $from,
            ])
            ->group('parentId');

        $rows = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build())
            ->fetchAll() ?: [];

        foreach ($rows as $row) {
            if (empty($row['parentId'])) {
                continue;
            }

            $countMap[$row['parentId']] ??= ['Call' => 0, 'Meeting' => 0, 'Email' => 0];
            $countMap[$row['parentId']][$entityType] = (int) $row['count'];
        }
    }
}
