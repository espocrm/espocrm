<?php

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

