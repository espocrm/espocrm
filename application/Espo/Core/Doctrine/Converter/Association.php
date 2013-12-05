<?php

namespace Espo\Core\Doctrine\Converter;

//use Espo\Core\Utils\Util;

class Association
{
	public function manyToOneUnidirectional($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array(
	        	'manyToOne' => array(
	            	$params['usLinkName'] => array(
	                	'targetEntity' => $params['targetEntity'],
	                	'joinColumn' => array(
	                    	'name' => $params['usLinkName'].'_id',
	                    	'referencedColumnName' => 'id',
						),
					),
				),
			),
		);
	}


	public function oneToManyUnidirectionalWithJoinTable($params, $foreignParams)
	{
		return array (
		  $params['entityName'] =>
		  array (
		    'manyToMany' =>
		    array (
		      $params['usLinkName'] =>
		      array (
		        'targetEntity' => $params['targetEntity'],
		        'joinTable' =>
		        array (
		          'name' => $params['joinTable'],
		          'joinColumns' =>
		          array (
		            $params['usEntityName'].'_id' =>
		            array (
		              'referencedColumnName' => 'id',
		            ),
		          ),
		          'inverseJoinColumns' =>
		          array (
		            $foreignParams['usEntityName'].'_id' =>
		            array (
		              'referencedColumnName' => 'id',
		              'unique' => true,
		            ),
		          ),
		        ),
		      ),
		    ),
		  ),
		);
	}


	public function manyToManyBidirectional($params, $foreignParams)
	{
		return array (
		  $params['entityName'] =>
		  array (
		    'manyToMany' =>
		    array (
		      $params['usLinkName'] =>
		      array (
		        'targetEntity' => $params['targetEntity'],
		        'inversedBy' => $foreignParams['usLinkName'],
		        'joinTable' =>
		        array (
		          'name' => $params['joinTable'],
		          'joinColumns' =>
		          array (
		            $params['usEntityName'].'_id' =>
		            array (
		              'referencedColumnName' => 'id',
		            ),
		          ),
		          'inverseJoinColumns' =>
		          array (
		            $foreignParams['usEntityName'].'_id' =>
		            array (
		              'referencedColumnName' => 'id',
		            ),
		          ),
		        ),
		      ),
		    ),
		  ),

		  $foreignParams['entityName'] =>
		  array (
		    'manyToMany' =>
		    array (
		      $foreignParams['usLinkName'] =>
		      array (
		        'targetEntity' => $foreignParams['targetEntity'],
		        'mappedBy' => $params['usLinkName'],
		      ),
		    ),
		  ),
		);
	}

	public function oneToOneUnidirectional($params, $foreignParams)
	{
		return array (
		  $params['entityName'] =>
		  array (
		    'oneToOne' =>
		    array (
		      $params['usLinkName'] =>
		      array (
		        'targetEntity' => $params['targetEntity'],
		        'joinColumn' =>
		        array (
		          'name' => $params['usLinkName'].'_id',
		          'referencedColumnName' => 'id',
		        ),
		      ),
		    ),
		  ),
		);
	}

	public function oneToOneBidirectional($params, $foreignParams)
	{
		return array (
		  $params['entityName'] =>
		  array (
		    'oneToOne' =>
		    array (
		      $params['usLinkName'] =>
		      array (
		        'targetEntity' => $params['targetEntity'],
		        'mappedBy' => $foreignParams['usLinkName'],
		      ),
		    ),
		  ),

		  $foreignParams['entityName'] =>
		  array (
		    'oneToOne' =>
		    array (
		      $foreignParams['usLinkName'] =>
		      array (
		        'targetEntity' => $foreignParams['targetEntity'],
		        'inversedBy' => $params['usLinkName'],
		        'joinColumn' =>
		        array (
		          'name' => $params['usLinkName'].'_id',
		          'referencedColumnName' => 'id',
		        ),
		      ),
		    ),
		  ),
		);
	}

	public function oneToManySelfReferencing($params, $foreignParams)
	{
		return array (
		  'Category' =>
		  array (
		    'type' => 'entity',
		    'oneToMany' =>
		    array (
		      'children' =>
		      array (
		        'targetEntity' => 'Category',
		        'mappedBy' => 'parent',
		      ),
		    ),
		    'manyToOne' =>
		    array (
		      'parent' =>
		      array (
		        'targetEntity' => 'Category',
		        'inversedBy' => 'children',
		      ),
		    ),
		  ),
		);
	}







}