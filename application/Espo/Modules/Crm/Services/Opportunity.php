<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
    public function reportSalesPipeline($dateFilter, $dateFrom = null, $dateTo = null)
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

        $selectParams = [
            'select' => ['stage', ['SUM:amountConverted', 'amount']],
            'whereClause' => [
                'stage!=' => 'Closed Lost'
            ],
            'orderBy' => 'LIST:stage:' . implode(',', $options),
            'groupBy' => ['stage']
        ];

        if ($dateFilter !== 'ever') {
            $selectParams['whereClause'][] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectManager->applyAccess($selectParams);

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $selectParams);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['stage']] = floatval($row['amount']);
        }

        return $result;
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

        $selectParams = [
            'select' => ['leadSource', ['SUM:amountWeightedConverted', 'amount']],
            'whereClause' => [
                'stage!=' => 'Closed Lost',
                ['leadSource!=' => ''],
                ['leadSource!=' => null]
            ],
            'orderBy' => 'LIST:leadSource:' . implode(',', $options),
            'groupBy' => ['leadSource']
        ];

        if ($dateFilter !== 'ever') {
            $selectParams['whereClause'][] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectManager->applyAccess($selectParams);

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

        $selectParams = [
            'select' => ['stage', ['SUM:amountConverted', 'amount']],
            'whereClause' => [
                'stage!=' => 'Closed Lost',
                'stage!=' => 'Closed Won'
            ],
            'orderBy' => 'LIST:stage:' . implode(',', $options),
            'groupBy' => ['stage']
        ];

        if ($dateFilter !== 'ever') {
            $selectParams['whereClause'][] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectManager->applyAccess($selectParams);

        $this->getEntityManager()->getRepository('Opportunity')->handleSelectParams($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Opportunity', $selectParams);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['stage']] = floatval($row['amount']);
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

        $selectParams = [
            'select' => [['MONTH:closeDate', 'month'], ['SUM:amountConverted', 'amount']],
            'whereClause' => [
                'stage' => 'Closed Won'
            ],
            'orderBy' => 1,
            'groupBy' => ['MONTH:closeDate']
        ];

        if ($dateFilter !== 'ever') {
            $selectParams['whereClause'][] = [
                'closeDate>=' => $dateFrom,
                'closeDate<' => $dateTo
            ];
        }

        $selectManager->applyAccess($selectParams);

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
            while ($dt->getTimestamp() <= $dtTo->getTimestamp()) {
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

        $endPosition = count($keyList) - 1;
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
        }
        return [0, 0];
    }
}
