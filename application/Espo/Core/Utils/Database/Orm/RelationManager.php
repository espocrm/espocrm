<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;

class RelationManager
{
    private $metadata;

    private $config;

    public function __construct(Metadata $metadata, Config $config)
    {
        $this->metadata = $metadata;
        $this->config = $config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
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
     * Get foreign link.
     *
     * @param string $parentLinkName
     * @param array $parentLinkParams
     * @param array $currentEntityDefs
     *
     * @return array|false Formatted as ['name', 'params'].
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

    public function convert($linkName, $linkParams, $entityName, $ormMetadata)
    {
        $entityDefs = $this->getMetadata()->get('entityDefs');

        $foreignEntityName = $this->getLinkEntityName($entityName, $linkParams);
        $foreignLink = $this->getForeignLink($linkName, $linkParams, $entityDefs[$foreignEntityName]);

        $currentType = $linkParams['type'];

        $relType = $currentType;
        if ($foreignLink !== false) {
            $relType .= '_' . $foreignLink['params']['type'];
        }
        $relType = Util::toCamelCase($relType);

        $relationName = $this->isRelationExists($relType) ? $relType /*hasManyHasMany*/ : $currentType /*hasMany*/;

        //relationDefs defined in separate file
        if (isset($linkParams['relationName'])) {
            $className = $this->getRelationClass($linkParams['relationName']);
            if (!$className) {
                $relationName = $this->isRelationExists($relType) ? $relType : $currentType;
                $className = $this->getRelationClass($relationName);
            }
        } else {
            $className = $this->getRelationClass($relationName);
        }

        if (isset($className) && $className !== false) {
            $foreignLinkName = (is_array($foreignLink) && array_key_exists('name', $foreignLink)) ? $foreignLink['name'] : null;

            $helperClass = new $className($this->metadata, $ormMetadata, $entityDefs, $this->config);
            return $helperClass->process($linkName, $entityName, $foreignLinkName, $foreignEntityName);
        }
        //END: relationDefs defined in separate file

        return null;
    }

}
