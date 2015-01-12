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

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util;

class RelationManager
{
    private $metadata;

    private $entityDefs;

    public function __construct(\Espo\Core\Utils\Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getEntityDefs()
    {
        if (empty($this->entityDefs)) {
            $this->entityDefs = $this->getMetadata()->get('entityDefs');
        }

        return $this->entityDefs;
    }


    public function getLinkEntityName($entityName, $linkParams)
    {
        return isset($linkParams['entity']) ? $linkParams['entity'] : $entityName;
    }

    public function isRelationExists($relationName)
    {
        if ($this->getRelationClass($relationName) !== false) {
            return true;
        }

        return false;
    }

    protected function getRelationClass($relationName)
    {
        $relationName = ucfirst($relationName);

        $className = '\Espo\Custom\Core\Utils\Database\Orm\Relations\\'.$relationName;
        if (!class_exists($className)) {
            $className = '\Espo\Core\Utils\Database\Orm\Relations\\'.$relationName;
        }

        if (class_exists($className)) {
            return $className;
        }

        return false;
    }

    protected function isMethodExists($relationName)
    {
        $className = $this->getRelationClass($relationName);

        return method_exists($className, 'load');
    }

    /**
    * Get foreign Link
    *
    * @param string $parentLinkName
    * @param array $parentLinkParams
    * @param array $currentEntityDefs
    *
    * @return array - in format array('name', 'params')
    */
    private function getForeignLink($parentLinkName, $parentLinkParams, $currentEntityDefs)
    {
        if (isset($parentLinkParams['foreign']) && isset($currentEntityDefs['links'][$parentLinkParams['foreign']])) {
            return array(
                'name' => $parentLinkParams['foreign'],
                'params' => $currentEntityDefs['links'][$parentLinkParams['foreign']],
            );
        }

        return false;
    }

    public function convert($linkName, $linkParams, $entityName, $ormMeta)
    {
        $entityDefs = $this->getEntityDefs();

        $foreignEntityName = $this->getLinkEntityName($entityName, $linkParams);
        $foreignLink = $this->getForeignLink($linkName, $linkParams, $entityDefs[$foreignEntityName]);

        $currentType = $linkParams['type'];

        $method = $currentType;
        if ($foreignLink !== false) {
            $method .= '_' . $foreignLink['params']['type'];
        }
        $method = Util::toCamelCase($method);

        $relationName = $this->isRelationExists($method) ? $method /*hasManyHasMany*/ : $currentType /*hasMany*/;

        //relationDefs defined in separate file
        if (isset($linkParams['relationName']) && $this->isMethodExists($linkParams['relationName'])) {
            $className = $this->getRelationClass($linkParams['relationName']);
        } else if ($this->isMethodExists($relationName)) {
            $className = $this->getRelationClass($relationName);
        }

        if (isset($className) && $className !== false) {
            $helperClass = new $className($this->metadata, $ormMeta, $entityDefs);
            return $helperClass->process($linkName, $entityName, $foreignLink['name'], $foreignEntityName);
        }
        //END: relationDefs defined in separate file

        return null;
    }

}