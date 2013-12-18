<?php

namespace Espo\Core\Utils\Database\Converters;

use Espo\Core\Utils\Util;

class Links
{
    private $metadata;

    private $relations;


	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;

		$this->relations = new Relations();
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

	protected function linkParent($params, $foreignParams)
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
[2] => hasMany
[3] => hasChildrenBelongsToParent
[4] => hasManyHasMany
[5] => hasOne
[6] => hasManyBelongsTo
[7] => belongsToHasMany
[8] => joint
[9] => belongsToParentHasChildren
	*/

}