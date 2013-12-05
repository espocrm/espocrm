<?php

namespace Espo\Core\Doctrine\Converter;

use Espo\Core\Utils\Util;

class Link
{
    private $metadata;

    private $association;


	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;

		$this->association = new \Espo\Core\Doctrine\Converter\Association();
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getAssociation()
	{
		return $this->association;
	}


	public function getLinkEntityName($entityName, $link)
	{
		if (isset($link['params'])) {
        	return isset($link['params']['entity']) ? $link['params']['entity'] : $entityName;
		}
		return isset($link['entity']) ? $link['entity'] : $entityName;
	}

	public function toUnderScore($name)
	{
    	return Util::fromCamelCase($name, '_');
	}

	protected function getJoinTable($tableName1, $tableName2)
	{
		$tables = array(
        	$this->toUnderScore($tableName1),
        	$this->toUnderScore($tableName2),
		);

		asort($tables);

		return implode('_', $tables);
	}


	public function process($method, $entityName, $link, $foreignLink = array())
	{
		$params = array();
		$params['entityName'] = $entityName;
        $params['usEntityName'] = $this->toUnderScore($entityName);
        $params['link'] = $link;
		$params['usLinkName'] = $this->toUnderScore($link['name']);

        $foreignParams = array();
		$foreignParams['entityName'] = $this->getLinkEntityName($entityName, $link);
		$foreignParams['usEntityName'] = $this->toUnderScore($foreignParams['entityName']);
		$foreignParams['link'] = $foreignLink;
		$foreignParams['usLinkName'] = $this->toUnderScore($foreignParams['link']['name']);

		$params['targetEntity'] = $this->getMetadata()->getEntityPath($foreignParams['entityName']);
		$foreignParams['targetEntity'] = $this->getMetadata()->getEntityPath($params['entityName']);

        $params['joinTable'] = $foreignParams['targetEntity'] = $this->getJoinTable($params['entityName'], $foreignParams['entityName']);


		if (method_exists($this, $method)) {
        	return $this->$method($params, $foreignParams);
		}

        return false;
	}



	protected function belongsTo($params, $foreignParams)
	{
    	return $this->getAssociation()->manyToOneUnidirectional($params, $foreignParams);
	}


	//TODO: hook for teams
	protected function hasMany($params, $foreignParams)
	{
    	return $this->getAssociation()->oneToManyUnidirectionalWithJoinTable($params, $foreignParams);
	}


	protected function hasManyHasMany($params, $foreignParams)
	{
		return $this->getAssociation()->manyToManyBidirectional($params, $foreignParams);
	}

	protected function hasOne($params, $foreignParams)
	{
		return $this->getAssociation()->oneToOneUnidirectional($params, $foreignParams);
	}

	protected function hasOneBelongsTo($params, $foreignParams)
	{
		return $this->getAssociation()->oneToOneBidirectional($params, $foreignParams);
	}


	protected function belongsToParent($params, $foreignParams)
	{
    	return $this->getAssociation()->oneToManySelfReferencing($params, $foreignParams);
	}




	/*
	+[0] => belongsTo
    [1] => belongsToParent
    [2] => hasManyBelongsToParent
    +[3] => hasMany
    [4] => hasChildrenBelongsToParent
    +[5] => hasOne
    +[6] => hasManyHasMany
    [7] => hasManyBelongsTo
    [8] => hasChildrenHasMany
    [9] => hasChildren
    [10] => belongsToHasMany
    [11] => joint


	[0] => belongsTo
    [1] => belongsToParent
    [2] => hasManyBelongsToParent
    [3] => hasMany
    [4] => hasChildrenBelongsToParent
    [5] => belongsToParentHasChildren
    [6] => hasOne
    [7] => hasManyBelongsTo
    [8] => hasManyHasMany
    [9] => belongsToHasMany
    [10] => joint




	[0] => belongsTo
    [1] => belongsToParent
    [2] => hasManyBelongsToParent
    [3] => hasMany
    [4] => hasChildrenBelongsToParent
    [5] => belongsToParentHasChildren
    [6] => hasOne
    [7] => hasManyHasMany
    [8] => hasManyBelongsTo
    [9] => hasChildrenHasMany
    [10] => hasChildren
    [11] => belongsToHasMany
    [12] => joint
	*/

}