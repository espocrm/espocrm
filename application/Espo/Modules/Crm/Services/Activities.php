<?php

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \PDO;

class Activities extends \Espo\Core\Services\Base
{
	protected $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl'
	);
	
	protected function getPDO()
	{
		return $this->getEntityManager()->getPDO();
	}

	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}
	
	protected function getAcl()
	{
		return $this->injections['acl'];
	}
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	protected function isPerson($scope)
	{
		return in_array($scope, array('Contact', 'Lead', 'User'));
	}	
	
	protected function getMeetingQuery($scope, $id, $op = 'IN', $notIn = array())
	{
		$qu = "
			SELECT meeting.id AS 'id', meeting.name AS 'name', meeting.date_start AS 'dateStart', meeting.date_end AS 'dateEnd', 'Meeting' AS '_scope',
			       meeting.assigned_user_id AS assignedUserId, TRIM(CONCAT(user.first_name, ' ', user.last_name)) AS assignedUserName,
			       meeting.parent_type AS 'parentType', meeting.parent_id AS 'parentId'
			FROM `meeting`
			LEFT JOIN `user` ON user.id = meeting.assigned_user_id
		";
		
		if ($this->isPerson($scope)) {
			switch ($scope) {
				case 'Contact':
					$joinTable = 'contact_meeting';
					$key = 'contact_id';
					break;
				case 'Lead':
					$joinTable = 'lead_meeting';
					$key = 'lead_id';
					break;
				case 'User':
					$joinTable = 'meeting_user';
					$key = 'user_id';
					break;					
			}
			$qu .= "
				JOIN `{$joinTable}` ON meeting.id = {$joinTable}.meeting_id AND {$joinTable}.deleted = 0 AND {$joinTable}.{$key} = ".$this->getPDO()->quote($id)."
			";
		}
		$qu .= "
			WHERE meeting.deleted = 0
		";
		
		if (!$this->isPerson($scope)) {
			$qu .= "
				AND meeting.parent_type = ".$this->getPDO()->quote($scope)." AND meeting.parent_id = ".$this->getPDO()->quote($id)."
			";		
		}
		
		if (!empty($notIn)) {
			$qu .= "
				AND meeting.status {$op} ('". implode("', '", $notIn) . "')
			";
		}
		
		return $qu;
	}
	
	protected function getCallQuery($scope, $id, $op = 'IN', $notIn = array())
	{
		$qu = "
			SELECT call.id AS 'id', call.name AS 'name', call.date_start AS 'dateStart', call.date_end AS 'dateEnd', 'Call' AS '_scope',
			       call.assigned_user_id AS assignedUserId, TRIM(CONCAT(user.first_name, ' ', user.last_name)) AS assignedUserName,
			       call.parent_type AS 'parentType', call.parent_id AS 'parentId'
			FROM `call`
			LEFT JOIN `user` ON user.id = call.assigned_user_id
		";
		
		if ($this->isPerson($scope)) {
			switch ($scope) {
				case 'Contact':
					$joinTable = 'call_contact';
					$key = 'contact_id';
					break;
				case 'Lead':
					$joinTable = 'call_lead';
					$key = 'lead_id';
					break;
				case 'User':
					$joinTable = 'call_user';
					$key = 'user_id';
					break;					
			}
			$qu .= "
				JOIN `{$joinTable}` ON call.id = {$joinTable}.call_id AND {$joinTable}.deleted = 0 AND {$joinTable}.{$key} = ".$this->getPDO()->quote($id)."
			";
		}
		$qu .= "
			WHERE call.deleted = 0
		";
		
		if (!$this->isPerson($scope)) {
			$qu .= "
				AND call.parent_type = ".$this->getPDO()->quote($scope)." AND call.parent_id = ".$this->getPDO()->quote($id)."
			";		
		}
		
		if (!empty($notIn)) {
			$qu .= "
				AND call.status {$op} ('". implode("', '", $notIn) . "')
			";
		}
		
		return $qu;
	}
	
	protected function getEmailQuery($scope, $id, $op = 'IN', $notIn = array())
	{
		$qu = "
			SELECT email.id AS 'id', email.name AS 'name', email.date_sent AS 'dateStart', '' AS 'dateEnd', 'Email' AS '_scope',
			       email.assigned_user_id AS assignedUserId, TRIM(CONCAT(user.first_name, ' ', user.last_name)) AS assignedUserName,
			       email.parent_type AS 'parentType', email.parent_id AS 'parentId'
			FROM `email`
			LEFT JOIN `user` ON user.id = email.assigned_user_id
		";

		$qu .= "
			WHERE email.deleted = 0
		";		

		$qu .= "
			AND email.parent_type = ".$this->getPDO()->quote($scope)." AND email.parent_id = ".$this->getPDO()->quote($id)."
		";
		
		if (!empty($notIn)) {
			$qu .= "
				AND email.status {$op} ('". implode("', '", $notIn) . "')
			";
		}
		
		return $qu;
	}
	
	protected function getResult($parts, $scope, $id, $params)
	{
		$pdo = $this->getEntityManager()->getPDO();
		
		$onlyScope = false;			
		if (!empty($params['scope'])) {
			$onlyScope = $params['scope'];	
		}
		
		if (!$onlyScope) {
			$qu = implode(" UNION ", $parts);
		} else {
			$qu = $parts[$onlyScope];
		}
					
		$countQu = "SELECT COUNT(*) AS 'count' FROM ({$qu}) AS c";
		$sth = $pdo->prepare($countQu);		
		$sth->execute();
	
		$row = $sth->fetch(PDO::FETCH_ASSOC);		
		$totalCount = $row['count'];
		
		$qu .= "
			ORDER BY dateStart DESC
		";		
		
		if (!empty($params['maxSize'])) {
			$qu .= "
				LIMIT :offset, :maxSize
			";	
		}		

	

		$sth = $pdo->prepare($qu);
		
		if (!empty($params['maxSize'])) {
			$offset = 0;
			if (!empty($params['offset'])) {
				$offset = $params['offset'];
			}
			
			$sth->bindParam(':offset', $offset, PDO::PARAM_INT);
			$sth->bindParam(':maxSize', $params['maxSize'], PDO::PARAM_INT);
		}
		
		$sth->execute();
		
		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		$list = array();
		foreach ($rows as $row) {
			$list[] = $row;
		}
		
		return array(
			'list' => $rows,
			'total' => $totalCount
		);
	}
	
	public function getActivities($scope, $id, $params = array())
	{
		$parts = array(
			'Meeting' => $this->getMeetingQuery($scope, $id, 'NOT IN', array('Held', 'Not Held')),
			'Call' => $this->getCallQuery($scope, $id, 'NOT IN', array('Held', 'Not Held')),
		);		
		return $this->getResult($parts, $scope, $id, $params);
	}
	
	public function getHistory($scope, $id, $params)
	{
		$parts = array(
			'Meeting' => $this->getMeetingQuery($scope, $id, 'IN', array('Held')),
			'Call' => $this->getCallQuery($scope, $id, 'IN', array('Held')),
			'Email' => $this->getEmailQuery($scope, $id, 'IN', array('Archived', 'Sent')),
		);
		$result = $this->getResult($parts, $scope, $id, $params);
		
		foreach ($result['list'] as &$item) {
			if ($item['_scope'] == 'Email') {
				$item['dateSent'] = $item['dateStart'];
			}
		}
		
		return $result;	
	}
	
	public function getEvents($from, $to)
	{
		$pdo = $this->getPDO();
	
		$sql = "
			SELECT 'Meeting' AS scope, id AS id, name AS name, date_start AS dateStart, date_end AS dateEnd FROM `meeting`
			WHERE 
				deleted = 0 AND
				date_start >= ".$pdo->quote($from)." AND
				date_start < ".$pdo->quote($to)."
			UNION
			SELECT 'Call' AS scope, id AS id, name AS name, date_start AS dateStart, date_end AS dateEnd FROM `call`
			WHERE 
				deleted = 0 AND
				date_start >= ".$pdo->quote($from)." AND
				date_start < ".$pdo->quote($to)."
			UNION
			SELECT 'Task' AS scope, id AS id, name AS name, date_start AS dateStart, date_end AS dateEnd FROM `task`
			WHERE 
				deleted = 0 AND
				date_start >= ".$pdo->quote($from)." AND
				date_start < ".$pdo->quote($to)."
		";

		
		$sth = $pdo->prepare($sql);		
		$sth->execute();		
		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		return $rows;
	}
}

