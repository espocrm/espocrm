<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Opportunity extends \Espo\Services\Record
{
    protected $mandatorySelectAttributeList = [
        'accountId',
        'accountName'
    ];

    public function reportSalesPipeline($dateFilter, $dateFrom = null, $dateTo = null, $useLastStage = false)
    {
        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList('Opportunity'))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $lostStageList = $this->getLostStageList();

        $pdo = $this->getEntityManager()->getPDO();

        $options = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        $selectManager = $this->getSelectManagerFactory()->create('Opportunity');

        $stageField = 'stage';
        if ($useLastStage) {
            $stageField = 'lastStage';
        }

        $whereClause = [
            [$stageField . '!=' => $lostStageList],
            [$stageField . '!=' => null]
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectParams = [
            'select' => [$stageField, ['SUM:amountConverted', 'amount']],
            'whereClause' => $whereClause,
            'orderBy' => 'LIST:'.$stageField.':' . implode(',', $options),
            'groupBy' => [$stageField]
        ];

        $selectManager->applyAccess($selectParams);

        $this->handleDistinctReportSelectParams($selectParams, $whereClause);

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $selectParams);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $row) {
            $data[$row[$stageField]] = floatval($row['amount']);
        }

        $dataList = [];

        $stageList = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);
        foreach ($stageList as $stage) {
            if (in_array($stage, $lostStageList)) continue;
            if (!in_array($stage, $lostStageList) && !isset($data[$stage])) {
                $data[$stage] = 0.0;
            }

            $dataList[] = [
                'stage' => $stage,
                'value' => $data[$stage]
            ];
        }

        return [
            'dataList' => $dataList
        ];
    }

    public function reportByLeadSource($dateFilter, $dateFrom = null, $dateTo = null)
    {
        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList('Opportunity'))) {
            throw new Forbidden();
        }
        if (in_array('leadSource', $this->getAcl()->getScopeForbiddenAttributeList('Opportunity'))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $pdo = $this->getEntityManager()->getPDO();

        $options = $this->getMetadata()->get('entityDefs.Lead.fields.source.options', []);

        $selectManager = $this->getSelectManagerFactory()->create('Opportunity');

        $whereClause = [
            ['stage!=' => $this->getLostStageList()],
            ['leadSource!=' => ''],
            ['leadSource!=' => null]
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectParams = [
            'select' => ['leadSource', ['SUM:amountWeightedConverted', 'amount']],
            'whereClause' => $whereClause,
            'orderBy' => 'LIST:leadSource:' . implode(',', $options),
            'groupBy' => ['leadSource']
        ];

        $selectManager->applyAccess($selectParams);

        $this->handleDistinctReportSelectParams($selectParams, $whereClause);

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $selectParams);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['leadSource']] = floatval($row['amount']);
        }

        return $result;
    }

    public function reportByStage($dateFilter, $dateFrom = null, $dateTo = null)
    {
        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList('Opportunity'))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $pdo = $this->getEntityManager()->getPDO();

        $options = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);

        $selectManager = $this->getSelectManagerFactory()->create('Opportunity');

        $whereClause = [
            ['stage!=' => $this->getLostStageList()],
            ['stage!=' => $this->getWonStageList()]
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectParams = [
            'select' => ['stage', ['SUM:amountConverted', 'amount']],
            'whereClause' => $whereClause,
            'orderBy' => 'LIST:stage:' . implode(',', $options),
            'groupBy' => ['stage']
        ];

        $stageIgnoreList = array_merge($this->getLostStageList(), $this->getWonStageList());

        $selectManager->applyAccess($selectParams);

        $this->handleDistinctReportSelectParams($selectParams, $whereClause);

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $selectParams);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            if (in_array($row['stage'], $stageIgnoreList)) continue;
            $result[$row['stage']] = floatval($row['amount']);
        }

        foreach ($options as $stage) {
            if (in_array($stage, $stageIgnoreList)) continue;
            if (array_key_exists($stage, $result)) continue;
            $result[$stage] = 0.0;
        }

        return $result;
    }

    public function reportSalesByMonth($dateFilter, $dateFrom = null, $dateTo = null)
    {
        if (in_array('amount', $this->getAcl()->getScopeForbiddenAttributeList('Opportunity'))) {
            throw new Forbidden();
        }

        if ($dateFilter !== 'between' && $dateFilter !== 'ever') {
            list($dateFrom, $dateTo) = $this->getDateRangeByFilter($dateFilter);
        }

        $pdo = $this->getEntityManager()->getPDO();

        $selectManager = $this->getSelectManagerFactory()->create('Opportunity');

        $whereClause = [
            ['stage' => $this->getWonStageList()]
        ];

        if ($dateFilter !== 'ever') {
            $whereClause[] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectParams = [
            'select' => [['MONTH:closeDate', 'month'], ['SUM:amountConverted', 'amount']],
            'whereClause' => $whereClause,
            'orderBy' => 1,
            'groupBy' => ['MONTH:closeDate']
        ];

        $selectManager->applyAccess($selectParams);

        $this->handleDistinctReportSelectParams($selectParams, $whereClause);

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $selectParams);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['month']] = floatval($row['amount']);
        }

        $dt = new \DateTime($dateFrom);
        $dtTo = new \DateTime($dateTo);

        if (intval($dtTo->format('d')) !== 1) {
            $dtTo->setDate($dtTo->format('Y'), $dtTo->format('m'), 1);
            $dtTo->modify('+ 1 month');
        } else {
            $dtTo->setDate($dtTo->format('Y'), $dtTo->format('m'), 1);
        }

        if ($dt && $dtTo) {
            $interval = new \DateInterval('P1M');
            while ($dt->getTimestamp() < $dtTo->getTimestamp()) {
                $month = $dt->format('Y-m');
                if (!array_key_exists($month, $result)) {
                    $result[$month] = 0;
                }
                $dt->add($interval);
            }
        }

        $keyList = array_keys($result);
        sort($keyList);

        $today = new \DateTime();

        $endPosition = count($keyList);
        for ($i = count($keyList) - 1; $i >= 0; $i--) {
            $key = $keyList[$i];
            $dt = new \DateTime($key . '-01');

            if ($dt->getTimestamp() < $today->getTimestamp()) {
                break;
            }
            if (empty($result[$key])) {
                $endPosition = $i;
            } else {
                break;
            }
        }

        $keyList = array_slice($keyList, 0, $endPosition);

        return (object) [
            'keyList' => $keyList,
            'dataMap' => $result
        ];
    }

    protected function handleDistinctReportSelectParams(&$selectParams, $whereClause)
    {
        if (!empty($selectParams['distinct'])) {
            $selectParamsSubQuery = $selectParams;

            unset($selectParams['distinct']);
            $selectParams['leftJoins'] = [];
            $selectParams['joins'] = [];
            $selectParams['whereClause'] = $whereClause;

            $selectParamsSubQuery['select'] = ['id'];
            unset($selectParamsSubQuery['groupBy']);
            unset($selectParamsSubQuery['orderBy']);
            unset($selectParamsSubQuery['order']);

            $selectParams['whereClause'][] = [
                'id=s' => [
                    'entityType' => 'Opportunity',
                    'selectParams' => $selectParamsSubQuery
                ]
            ];
        }
    }

    protected function getDateRangeByFilter($dateFilter)
    {
        switch ($dateFilter) {
            case 'currentYear':
                $dt = new \DateTime();
                return [
                    $dt->modify('first day of January this year')->format('Y-m-d'),
                    $dt->add(new \DateInterval('P1Y'))->format('Y-m-d')
                ];
            case 'currentQuarter':
                $dt = new \DateTime();
                $quarter = ceil($dt->format('m') / 3);
                $dt->modify('first day of January this year');
                return [
                    $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                    $dt->add(new \DateInterval('P3M'))->format('Y-m-d')
                ];
            case 'currentMonth':
                $dt = new \DateTime();
                return [
                    $dt->modify('first day of this month')->format('Y-m-d'),
                    $dt->add(new \DateInterval('P1M'))->format('Y-m-d')
                ];
            case 'currentFiscalYear':
                $dtToday = new \DateTime();
                $dt = new \DateTime();
                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');
                if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
                    $dt->modify('-1 year');
                }
                return [
                    $dt->format('Y-m-d'),
                    $dt->add(new \DateInterval('P1Y'))->format('Y-m-d')
                ];
            case 'currentFiscalQuarter':
                $dtToday = new \DateTime();
                $dt = new \DateTime();
                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');
                $month = intval($dtToday->format('m'));
                $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);
                if ($quarterShift) {
                    if ($quarterShift >= 0) {
                        $dt->add(new \DateInterval('P'.($quarterShift * 3).'M'));
                    } else {
                        $quarterShift *= -1;
                        $dt->sub(new \DateInterval('P'.($quarterShift * 3).'M'));
                    }
                }
                return [
                    $dt->format('Y-m-d'),
                    $dt->add(new \DateInterval('P3M'))->format('Y-m-d')
                ];
        }
        return [0, 0];
    }

    public function massConvertCurrency($field, $targetCurrency, $params, $baseCurrency, $rates)
    {
        $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->entityType, 'edit');
        if (in_array($field, $forbiddenFieldList)) {
            throw new Forbidden();
        }

        $count = 0;

        $idUpdatedList = [];
        $repository = $this->getRepository();

        if (array_key_exists('where', $params)) {
            $where = $params['where'];
            $p = [];
            $p['where'] = $where;
            if (!empty($params['selectData']) && is_array($params['selectData'])) {
                foreach ($params['selectData'] as $k => $v) {
                    $p[$k] = $v;
                }
            }
            $selectParams = $this->getSelectParams($p);
        } else if (array_key_exists('ids', $params)) {
            $selectParams = $this->getSelectParams([]);
            $selectParams['whereClause'][] = ['id' => $params['ids']];
        } else {
            throw new Error();
        }

        $collection = $repository->find($selectParams);

        $currencyAttribute = $field . 'Currency';

        foreach ($collection as $entity) {
            if ($entity->get($field) === null) continue;

            $currentCurrency = $entity->get($currencyAttribute);
            $value = $entity->get($field);

            if ($currentCurrency === $targetCurrency) continue;

            if ($currentCurrency !== $baseCurrency && !property_exists($rates, $currentCurrency)) {
                continue;
            }

            $rate1 = property_exists($rates, $currentCurrency) ? $rates->$currentCurrency : 1.0;
            $value = $value * $rate1;

            $rate2 = property_exists($rates, $targetCurrency) ? $rates->$targetCurrency : 1.0;
            $value = $value / $rate2;

            if (!$rate2) continue;

            $value = round($value, 2);

            $data = [];
            $data[$currencyAttribute] = $targetCurrency;

            $data[$field] = $value;

            if ($this->getAcl()->check($entity, 'edit')) {
                $entity->set($data);
                if ($repository->save($entity)) {
                    $idUpdatedList[] = $entity->id;
                    $count++;

                    $this->processActionHistoryRecord('update', $entity);
                }
            }
        }

        return array(
            'count' => $count
        );
    }

    protected function getLostStageList()
    {
        $lostStageList = [];
        $probabilityMap =  $this->getMetadata()->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap'], []);
        $stageList = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);
        foreach ($stageList as $stage) {
            if (empty($probabilityMap[$stage])) {
                $lostStageList[] = $stage;
            }
        }
        return $lostStageList;
    }

    protected function getWonStageList()
    {
        $wonStageList = [];
        $probabilityMap =  $this->getMetadata()->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap'], []);
        $stageList = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options', []);
        foreach ($stageList as $stage) {
            if (!empty($probabilityMap[$stage]) && $probabilityMap[$stage] == 100) {
                $wonStageList[] = $stage;
            }
        }
        return $wonStageList;
    }

    public function getEmailAddressList($id)
    {
        $entity = $this->getEntity($id);
        $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->getEntityType());

        $list = [];
        $emailAddressList = [];

        if (!in_array('contacts', $forbiddenFieldList) && $this->getAcl()->checkScope('Contact')) {
            $contactIdList = $entity->getLinkMultipleIdList('contacts');
            if (count($contactIdList)) {
                $contactForbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList('Contact');
                if (!in_array('emailAddress', $contactForbiddenFieldList)) {
                    $selectManager = $this->getSelectManagerFactory()->create('Contact');
                    $selectParams = $selectManager->getEmptySelectParams();
                    $selectManager->applyAccess($selectParams);
                    $contactList = $this->getEntityManager()->getRepository('Contact')->select(['id', 'emailAddress', 'name'])->where([
                        'id' => $contactIdList
                    ])->find($selectParams);

                    foreach ($contactList as $contact) {
                        $emailAddress = $contact->get('emailAddress');
                        if ($emailAddress && !in_array($emailAddress, $emailAddressList)) {
                            $list[] = (object) [
                                'emailAddress' => $emailAddress,
                                'name' => $contact->get('name'),
                                'entityType' => 'Contact'
                            ];
                            $emailAddressList[] = $emailAddress;
                        }
                    }
                }
            }
        }

        if (empty($list)) {
            if (!in_array('account', $forbiddenFieldList) && $this->getAcl()->checkScope('Account')) {
                if ($entity->get('accountId')) {
                    $account = $this->getEntityManager()->getEntity('Account', $entity->get('accountId'));
                    if ($account && $account->get('emailAddress')) {
                        $emailAddress = $account->get('emailAddress');
                        if ($this->getAcl()->checkEntity($account)) {
                            $list[] = (object) [
                                'emailAddress' => $emailAddress,
                                'name' => $account->get('name'),
                                'entityType' => 'Account'
                            ];
                            $emailAddressList[] = $emailAddress;
                        }
                    }
                }
            }
        }

        return $list;
    }
}
