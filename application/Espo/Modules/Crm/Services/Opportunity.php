<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
    public function reportSalesPipeline($dateFrom, $dateTo)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $options = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options');

        $sql = "
            SELECT opportunity.stage AS `stage`, SUM(opportunity.amount * currency.rate) as `amount`
            FROM opportunity
            JOIN currency ON currency.id = opportunity.amount_currency
            WHERE
                opportunity.deleted = 0 AND
                opportunity.close_date >= ".$pdo->quote($dateFrom)." AND
                opportunity.close_date < ".$pdo->quote($dateTo)." AND
                opportunity.stage <> 'Closed Lost'
            GROUP BY opportunity.stage
            ORDER BY FIELD(opportunity.stage, '".implode("','", $options)."')
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['stage']] = floatval($row['amount']);
        }

        return $result;
    }

    public function reportByLeadSource($dateFrom, $dateTo)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT opportunity.lead_source AS `leadSource`, SUM(opportunity.amount * currency.rate * opportunity.probability / 100) as `amount`
            FROM opportunity
            JOIN currency ON currency.id = opportunity.amount_currency
            WHERE
                opportunity.deleted = 0 AND
                opportunity.close_date >= ".$pdo->quote($dateFrom)." AND
                opportunity.close_date < ".$pdo->quote($dateTo)." AND
                opportunity.stage <> 'Closed Lost' AND
                opportunity.lead_source <> ''
            GROUP BY opportunity.lead_source
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['leadSource']] = floatval($row['amount']);
        }

        return $result;
    }

    public function reportByStage($dateFrom, $dateTo)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $options = $this->getMetadata()->get('entityDefs.Opportunity.fields.stage.options');

        $sql = "
            SELECT opportunity.stage AS `stage`, SUM(opportunity.amount * currency.rate) as `amount`
            FROM opportunity
            JOIN currency ON currency.id = opportunity.amount_currency
            WHERE
                opportunity.deleted = 0 AND
                opportunity.close_date >= ".$pdo->quote($dateFrom)." AND
                opportunity.close_date < ".$pdo->quote($dateTo)." AND
                opportunity.stage <> 'Closed Lost' AND
                opportunity.stage <> 'Closed Won'
            GROUP BY opportunity.stage
            ORDER BY FIELD(opportunity.stage, '".implode("','", $options)."')
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['stage']] = floatval($row['amount']);
        }

        return $result;
    }

    public function reportSalesByMonth($dateFrom, $dateTo)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            SELECT DATE_FORMAT(opportunity.close_date, '%Y-%m') AS `month`, SUM(opportunity.amount * currency.rate) as `amount`
            FROM opportunity
            JOIN currency ON currency.id = opportunity.amount_currency
            WHERE
                opportunity.deleted = 0 AND
                opportunity.close_date >= ".$pdo->quote($dateFrom)." AND
                opportunity.close_date < ".$pdo->quote($dateTo)." AND
                opportunity.stage = 'Closed Won'

            GROUP BY DATE_FORMAT(opportunity.close_date, '%Y-%m')
            ORDER BY `month`
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['month']] = floatval($row['amount']);
        }

        return $result;
    }

}

