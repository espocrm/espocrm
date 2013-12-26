<?php

namespace Espo\ORM;

/**
 * Model interface.
 */
interface IEntity
{
	const ID = 'id';
	const VARCHAR = 'varchar';
	const INT = 'int';
	const FLOAT = 'float';
	const TEXT = 'text';
	const BOOL = 'bool';
	const FOREIGN_ID = 'foreignId';
	const FOREIGN = 'foreign';
	const FOREIGN_TYPE = 'foreignType';
	const DATE = 'date';
	const DATETIME = 'datetime';
	
	const MANY_MANY = 'manyMany';
	const HAS_MANY = 'hasMany';
	const BELONGS_TO = 'belongsTo';
	const HAS_ONE = 'hasOne';
	const BELONGS_TO_PARENT = 'belongsToParent';
	const HAS_CHILDREN = 'hasChildren';
	
	/**
	 * Push values from the array.
	 * E.g. insert values into the bean from a request data.
	 * @param array $arr Array of field - value pairs
	 */
	function populateFromArray(array $arr);
	
	/**
	 * Resets all fields in the current model.
	 */
	function reset();
	
	/**
	 * Set field.
	 */
	function set($name, $value);
	
	/**
	 * Get field.
	 */
	function get($name);
	
	/**
	 * Check field is set.
	 */
	function has($name);
	
	/**
	 * Clear field.
	 */
	function clear($name);
	
}


