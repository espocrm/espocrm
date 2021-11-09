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

use Espo\ORM\{
    Query\SelectBuilder,
};

use Espo\{
    Services\Record,
    Modules\Crm\Entities\Opportunity as OpportunityEntity,
};

use Espo\Core\{
    Exceptions\Forbidden,
};

use DateTime;
use DateInterval;
use PDO;
use stdClass;

class Opportunity extends Record
{
    protected $mandatorySelectAttributeList = [
        'accountId',
        'accountName',
    ];

    public function reportSalesPipeline(
        string $dateFilter,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        bool $useLastStage = false,
        ?string $teamId = null
    ): stdClass {

        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList(OpportunityEntity::ENTITY_TYPE))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $lostStageList = $this->getLostStageList();

        $options = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(OpportunityEntity::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $stageField = 'stage';

        if ($useLastStage) {
            $stageField = 'lastStage';
        }

        $whereClause = [
            [$stageField . '!=' => $lostStageList],
            [$stageField . '!=' => null],
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo,
            ];
        }

        if ($teamId) {
            $whereClause[] = [
                'teamsFilter.id' => $teamId,
            ];
        }

        $queryBuilder
            ->select([
                $stageField,
                ['SUM:amountConverted', 'amount'],
            ])
            ->order('LIST:'.$stageField.':' . implode(',', $options))
            ->group($stageField)
            ->where($whereClause);

        if ($teamId) {
            $queryBuilder->join('teams', 'teamsFilter');
        }

        $this->handleDistinctReportQueryBuilder($queryBuilder, $whereClause);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        $data = [];

        foreach ($rowList as $row) {
            $stage = $row[$stageField];

            $data[$stage] = floatval($row['amount']);
        }

        $dataList = [];

        $stageList = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        foreach ($stageList as $stage) {
            if (in_array($stage, $lostStageList)) {
                continue;
            }

            if (!in_array($stage, $lostStageList) && !isset($data[$stage])) {
                $data[$stage] = 0.0;
            }

            $dataList[] = [
                'stage' => $stage,
                'value' => $data[$stage],
            ];
        }

        return (object) [
            'dataList' => $dataList,
        ];
    }

    public function reportByLeadSource(
        string $dateFilter,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): stdClass {

        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList(OpportunityEntity::ENTITY_TYPE))) {
            throw new Forbidden();
        }

        if (in_array('leadSource', $this->getAcl()->getScopeForbiddenAttributeList(OpportunityEntity::ENTITY_TYPE))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $options = $this->getMetadata()->get('entityDefs.Lead.fields.source.options', []);

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(OpportunityEntity::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $whereClause = [
            ['stage!=' => $this->getLostStageList()],
            ['leadSource!=' => ''],
            ['leadSource!=' => null],
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo,
            ];
        }

        $queryBuilder
            ->select([
                'leadSource',
                ['SUM:amountWeightedConverted', 'amount'],
            ])
            ->order('LIST:leadSource:' . implode(',', $options))
            ->group('leadSource')
            ->where($whereClause);

        $this->handleDistinctReportQueryBuilder($queryBuilder, $whereClause);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rowList as $row) {
            $leadSource = $row['leadSource'];

            $result[$leadSource] = floatval($row['amount']);
        }

        return (object) $result;
    }

    public function reportByStage(
        string $dateFilter,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): stdClass {

        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList(OpportunityEntity::ENTITY_TYPE))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $options = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(OpportunityEntity::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $whereClause = [
            ['stage!=' => $this->getLostStageList()],
            ['stage!=' => $this->getWonStageList()],
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo,
            ];
        }

        $queryBuilder
            ->select([
                'stage',
                ['SUM:amountConverted', 'amount'],
            ])
            ->order('LIST:stage:' . implode(',', $options))
            ->group('stage')
            ->where($whereClause);

        $stageIgnoreList = array_merge($this->getLostStageList(), $this->getWonStageList());

        $this->handleDistinctReportQueryBuilder($queryBuilder, $whereClause);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rowList as $row) {
            $stage = $row['stage'];

            if (in_array($stage, $stageIgnoreList)) {
                continue;
            }

            $result[$stage] = floatval($row['amount']);
        }

        foreach ($options as $stage) {
            if (in_array($stage, $stageIgnoreList)) {
                continue;
            }

            if (array_key_exists($stage, $result)) {
                continue;
            }

            $result[$stage] = 0.0;
        }

        return (object) $result;
    }

    public function reportSalesByMonth(
        string $dateFilter,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): stdClass {

        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList(OpportunityEntity::ENTITY_TYPE))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(OpportunityEntity::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $whereClause = [
            'stage' => $this->getWonStageList(),
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo,
            ];
        }

        $queryBuilder
            ->select([
                ['MONTH:closeDate', 'month'],
                ['SUM:amountConverted', 'amount'],
            ])
            ->order('MONTH:closeDate')
            ->group('MONTH:closeDate')
            ->where($whereClause);

        $this->handleDistinctReportQueryBuilder($queryBuilder, $whereClause);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $result = [];

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rowList as $row) {
            $month = $row['month'];

            $result[$month] = floatval($row['amount']);
        }

        $dt = new DateTime($dateFrom);
        $dtTo = new DateTime($dateTo);

        if (intval($dtTo->format('d')) !== 1) {
            $dtTo->setDate((int) $dtTo->format('Y'), (int) $dtTo->format('m'), 1);
            $dtTo->modify('+ 1 month');
        }
        else {
            $dtTo->setDate((int) $dtTo->format('Y'), (int) $dtTo->format('m'), 1);
        }

        $interval = new DateInterval('P1M');

        while ($dt->getTimestamp() < $dtTo->getTimestamp()) {
            $month = $dt->format('Y-m');

            if (!array_key_exists($month, $result)) {
                $result[$month] = 0;
            }

            $dt->add($interval);
        }

        $keyList = array_keys($result);

        sort($keyList);

        $today = new DateTime();

        $endPosition = count($keyList);

        for ($i = count($keyList) - 1; $i >= 0; $i--) {
            $key = $keyList[$i];

            $dt = new DateTime($key . '-01');

            if ($dt->getTimestamp() < $today->getTimestamp()) {
                break;
            }

            if (empty($result[$key])) {
                $endPosition = $i;
            }
            else {
                break;
            }
        }

        $keyListSliced = array_slice($keyList, 0, $endPosition);

        return (object) [
            'keyList' => $keyListSliced,
            'dataMap' => $result,
        ];
    }

    /**
     * A grouping-by with distinct will give wrong results. Need to use sub-query.
     */
    protected function handleDistinctReportQueryBuilder(SelectBuilder $queryBuilder, array $whereClause)
    {
        if (!$queryBuilder->build()->isDistinct()) {
            return;
        }

        $subQuery = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(OpportunityEntity::ENTITY_TYPE)
            ->select('id')
            ->where($whereClause)
            ->build();

        $queryBuilder->where([
            'id=s' => $subQuery->getRaw(),
        ]);
    }

    protected function getDateRangeByFilter(string $dateFilter): array
    {
        switch ($dateFilter) {
            case 'currentYear':
                $dt = new DateTime();

                return [
                    $dt->modify('first day of January this year')->format('Y-m-d'),
                    $dt->add(new DateInterval('P1Y'))->format('Y-m-d')
                ];

            case 'currentQuarter':
                $dt = new DateTime();

                $quarter = ceil($dt->format('m') / 3);

                $dt->modify('first day of January this year');

                return [
                    $dt->add(new DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                    $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
                ];

            case 'currentMonth':
                $dt = new DateTime();

                return [
                    $dt->modify('first day of this month')->format('Y-m-d'),
                    $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
                ];

            case 'currentFiscalYear':
                $dtToday = new DateTime();

                $dt = new DateTime();

                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);

                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');

                if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
                    $dt->modify('-1 year');
                }

                return [
                    $dt->format('Y-m-d'),
                    $dt->add(new DateInterval('P1Y'))->format('Y-m-d')
                ];

            case 'currentFiscalQuarter':
                $dtToday = new DateTime();
                $dt = new DateTime();

                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);

                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');

                $month = intval($dtToday->format('m'));

                $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);

                if ($quarterShift) {
                    if ($quarterShift >= 0) {
                        $dt->add(new DateInterval('P'.($quarterShift * 3).'M'));
                    }
                    else {
                        $quarterShift *= -1;

                        $dt->sub(new DateInterval('P'.($quarterShift * 3).'M'));
                    }
                }

                return [
                    $dt->format('Y-m-d'),
                    $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
                ];
        }

        return [0, 0];
    }

    protected function getLostStageList(): array
    {
        $lostStageList = [];

        $probabilityMap =  $this->getMetadata()->get(
            ['entityDefs', OpportunityEntity::ENTITY_TYPE, 'fields', 'stage', 'probabilityMap'], []
        );

        $stageList = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        foreach ($stageList as $stage) {
            if (empty($probabilityMap[$stage])) {
                $lostStageList[] = $stage;
            }
        }

        return $lostStageList;
    }

    protected function getWonStageList(): array
    {
        $wonStageList = [];

        $probabilityMap =  $this->getMetadata()->get(
            ['entityDefs', OpportunityEntity::ENTITY_TYPE, 'fields', 'stage', 'probabilityMap'], []
        );

        $stageList = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        foreach ($stageList as $stage) {
            if (!empty($probabilityMap[$stage]) && $probabilityMap[$stage] == 100) {
                $wonStageList[] = $stage;
            }
        }

        return $wonStageList;
    }

    public function getEmailAddressList(string $id): array
    {
        /** @var OpportunityEntity */
        $entity = $this->getEntity($id);

        $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList($this->getEntityType());

        $list = [];

        if (
            !in_array('contacts', $forbiddenFieldList) &&
            $this->acl->checkScope('Contact')
        ) {
            foreach ($this->getContactEmailAddressList($entity) as $item) {
                $list[] = $item;
            }
        }

        if (
            empty($list) &&
            !in_array('account', $forbiddenFieldList) &&
            $this->acl->checkScope('Account') &&
            $entity->get('accountId')
        ) {
            $item = $this->getAccountEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        return $list;
    }

    protected function getAccountEmailAddress(OpportunityEntity $entity, array $dataList): ?stdClass
    {
        $account = $this->entityManager->getEntity('Account', $entity->get('accountId'));

        if (!$account || !$account->get('emailAddress')) {
            return null;
        }

        $emailAddress = $account->get('emailAddress');

        if (!$this->acl->checkEntity($account)) {
            return null;
        }

        foreach ($dataList as $item) {
            if ($item->emailAddrses === $emailAddress) {
                return null;
            }
        }

        return (object) [
            'emailAddress' => $emailAddress,
            'name' => $account->get('name'),
            'entityType' => 'Account',
        ];
    }

    protected function getContactEmailAddressList(OpportunityEntity $entity): array
    {
        $contactIdList = $entity->getLinkMultipleIdList('contacts');

        if (!count($contactIdList)) {
            return [];
        }

        $contactForbiddenFieldList = $this->acl->getScopeForbiddenFieldList('Contact');

        if (in_array('emailAddress', $contactForbiddenFieldList)) {
            return [];
        }

        $dataList = [];

        $emailAddressList = [];

        $query = $this->selectBuilderFactory
            ->create()
            ->from('Contact')
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'emailAddress',
                'name',
            ])
            ->where([
                'id' => $contactIdList,
            ])
            ->build();

        $contactCollection = $this->entityManager
            ->getRDBRepository('Contact')
            ->clone($query)
            ->find();

        foreach ($contactCollection as $contact) {
            $emailAddress = $contact->get('emailAddress');

            if (!$emailAddress) {
                continue;
            }

            if (in_array($emailAddress, $emailAddressList)) {
                continue;
            }

            $emailAddressList[] = $emailAddress;

            $dataList[] = (object) [
                'emailAddress' => $emailAddress,
                'name' => $contact->get('name'),
                'entityType' => 'Contact',
            ];
        }

        return $dataList;
    }
}
