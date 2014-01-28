<?php

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;

class EmailTemplate extends Record
{
	
	public function parse($id, array $params = array())
	{
		$emailTemplate = $this->getEntity($id);
		if (empty($emailTemplate)) {
			throw new NotFound();
		}
		
		$entityList = array();
		

		if (!empty($params['emailAddress'])) {
			$emailAddress = $this->getEntityManager()->getRepository('EmailAddress')->where(array(
				'lower' => $params['emailAddress']
			))->findOne();
			

			if (!empty($emailAddress)) {
				$pdo = $this->getEntityManager()->getPDO();
				$sql = "
					SELECT * FROM `entity_email_address`
					WHERE 
						`primary` = 1 AND `deleted` = 0 AND `email_address_id` = " . $pdo->quote($emailAddress->id). "
				";
				$sth = $pdo->prepare($sql);
				$sth->execute();				
				if ($row = $sth->fetch()) {
					if (!empty($row['entity_id'])) {
						$entity = $this->getEntityManager()->getEntity($row['entity_type'], $row['entity_id']);
						if (!empty($entity::$person)) {
							$entityList['Person'] = $entity;
						}
					}
				}
			}
		}
		
		if (!empty($params['parentId']) && !empty($params['parentType'])) {
			$parent = $this->getEntityManager()->getEntity($params['parentType'], $params['parentId']);
			if (!empty($parent)) {
				$entityList[$params['parentType']] = $parent;
				$entityList['Parent'] = $parent;
				
				if (empty($entityList['Person']) && !empty($entity::$person)) {
					$entityList['Person'] = $parent;
				}
			}
		}
		
		$subject = $emailTemplate->get('subject');
		$body = $emailTemplate->get('body');
		
		foreach ($entityList as $type => $entity) {
			$subject = $this->parseText($type, $entity, $subject);
		}
		foreach ($entityList as $type => $entity) {
			$body = $this->parseText($type, $entity, $body);
		}
		
		return array(
			'subject' => $subject,
			'body' => $body,
		);
		
	}
	
	protected function parseText($type, Entity $entity, $text)
	{
		
		$fields = array_keys($entity->getFields());
		foreach ($fields as $field) {
			$text = str_replace('{' . $type . '.' . $field . '}', $entity->get($field), $text);
		}
		return $text;
	}
}

