<?php

namespace Espo\Core\Utils\Database;

use Espo\Core\Utils\Util;

class Links
{
    private $metadata;

    private $relations;


	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;

		$this->relations = new \Espo\Core\Utils\Database\Relations();
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getRelations()
	{
		return $this->relations;
	}


	public function getLinkEntityName($entityName, $link)
	{
		if (isset($link['params'])) {
        	return isset($link['params']['entity']) ? $link['params']['entity'] : $entityName;
		}
		/*if (!isset($link['entity']) && isset($link['entities'])) {
			return $link['entities'];
		} */
		return isset($link['entity']) ? $link['entity'] : $entityName;
	}


	public function process($method, $entityName, $link, $foreignLink = array())
	{
		$params = array();
		$params['entityName'] = $entityName;
        $params['link'] = $link;

        $foreignParams = array();
		$foreignParams['entityName'] = $this->getLinkEntityName($entityName, $link);
		$foreignParams['link'] = $foreignLink;

		//$params['targetEntity'] = $this->getMetadata()->getEntityPath($foreignParams['entityName']);
		//$foreignParams['targetEntity'] = $this->getMetadata()->getEntityPath($params['entityName']);
		$params['targetEntity'] = $foreignParams['entityName'];
		$foreignParams['targetEntity'] = $params['entityName'];

		if (method_exists($this, $method)) {
        	return $this->$method($params, $foreignParams);
		}

        return false;
	}



	protected function belongsTo($params, $foreignParams)
	{
    	return $this->getRelations()->belongsTo($params, $foreignParams);
	}

	//TODO: hook for teams
	protected function hasMany($params, $foreignParams)
	{
    	return $this->getRelations()->hasMany($params, $foreignParams);
	}


	protected function hasManyHasMany($params, $foreignParams)
	{
    	return $this->getRelations()->manyMany($params, $foreignParams);
	}


	protected function hasChildren($params, $foreignParams)
	{
		return $this->getRelations()->hasChildren($params, $foreignParams);
	}


	protected function belongsToParent($params, $foreignParams)
	{
    	return $this->getRelations()->belongsToParent($params, $foreignParams);
	}




	/*protected function hasChildrenBelongsToParent()
	{
    	return $this->getRelations()->hasChildren($params, $foreignParams);
	}*/

	/*protected function hasManyBelongsTo($params, $foreignParams)
	{
		$hasMany = $this->getRelations()->hasMany($params, $foreignParams);
		$belongsTo = $this->getRelations()->belongsTo($foreignParams, $params);
        //$belongsTo [Contact] [relations] [account] ['foreignKey'] = 'id'; ???

		return Util::merge($hasMany, $belongsTo);
	}  */


	/*
	[0] => belongsTo
    [1] => belongsToParent
    [2] => hasManyBelongsToParent
    [3] => hasMany
    [4] => hasChildrenBelongsToParent
    [5] => hasOne
    [6] => hasManyHasMany
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
    [11] => belongsToHasMany
    [12] => joint
	*/

}