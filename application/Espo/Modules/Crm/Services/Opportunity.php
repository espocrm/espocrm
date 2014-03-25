<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
		
		$sql = "
			SELECT opportunity.stage AS `stage`, SUM(opportunity.amount * currency.rate) as `amount`
			FROM opportunity
			JOIN currency ON currency.id = opportunity.amount_currency
			WHERE 
				opportunity.deleted = 0 AND
				opportunity.close_date >= ".$pdo->quote($dateFrom)." AND
				opportunity.close_date < ".$pdo->quote($dateTo)." AND
				opportunity.stage <> 'Closed Lost'
			GROUP BY opportunity.lead_source
			ORDER BY FIELD(opportunity.stage, 'Prospecting', 'Qualification', 'Needs Analysis', 'Value Proposition', 'Id. Decision Makers', 'Perception Analysis', 'Proposal/Price Quote', 'Negotiation/Review', 'Closed Won')		
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

}

