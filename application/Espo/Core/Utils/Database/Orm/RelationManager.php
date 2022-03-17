<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
    private Metadata $metadata;

    private Config $config;

    public function __construct(Metadata $metadata, Config $config)
    {
        $this->metadata = $metadata;
        $this->config = $config;
    }

    protected function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @param string $entityName
     * @param array<string,mixed> $linkParams
     * @return string
     */
    public function getLinkEntityName($entityName, $linkParams)
    {
        return isset($linkParams['entity']) ? $linkParams['entity'] : $entityName;
    }

    /**
     * @param string $relationName
     */
    public function relationExists($relationName): bool
    {
        if ($this->getRelationClass($relationName) !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param string $relationName
     * @return class-string<\Espo\Core\Utils\Database\Orm\Relations\Base>|false
     */
    protected function getRelationClass($relationName)
    {
        $relationName = ucfirst($relationName);

        $className = 'Espo\Custom\Core\Utils\Database\Orm\Relations\\'.$relationName;

        if (!class_exists($className)) {
            $className = 'Espo\Core\Utils\Database\Orm\Relations\\'.$relationName;
        }

        if (class_exists($className)) {
            /** @var class-string<\Espo\Core\Utils\Database\Orm\Relations\Base> */
            return $className;
        }

        return false;
    }

    /**
     * @param string $relationName
     */
    protected function methodExists($relationName): bool
    {
        $className = $this->getRelationClass($relationName);

        if ($className === false) {
            return false;
        }

        return method_exists($className, 'load');
    }

    /**
     * Get foreign link.
     *
     * @param array<string,mixed> $parentLinkParams
     * @param array<string,mixed> $currentEntityDefs
     *
     * @return array{name:string,params:array<string,mixed>}|false
     */
    private function getForeignLink($parentLinkParams, $currentEntityDefs)
    {
        if (isset($parentLinkParams['foreign']) && isset($currentEntityDefs['links'][$parentLinkParams['foreign']])) {
            return [
                'name' => $parentLinkParams['foreign'],
                'params' => $currentEntityDefs['links'][$parentLinkParams['foreign']],
            ];
        }

        return false;
    }

    /**
     * @param string $linkName
     * @param array<string,mixed> $linkParams
     * @param string $entityName
     * @param array<string,mixed> $ormMetadata
     * @return ?array<string,mixed>
     */
    public function convert($linkName, $linkParams, $entityName, $ormMetadata)
    {
        $entityDefs = $this->getMetadata()->get('entityDefs');

        $foreignEntityName = $this->getLinkEntityName($entityName, $linkParams);
        $foreignLink = $this->getForeignLink($linkParams, $entityDefs[$foreignEntityName]);

        $currentType = $linkParams['type'];

        $relType = $currentType;

        if ($foreignLink !== false) {
            $relType .= '_' . $foreignLink['params']['type'];
        }

        $relType = Util::toCamelCase($relType);

        $relationName = $this->relationExists($relType) ? $relType /*hasManyHasMany*/ : $currentType /*hasMany*/;

        // relationDefs defined in separate file
        if (isset($linkParams['relationName'])) {
            $className = $this->getRelationClass($linkParams['relationName']);

            if (!$className) {
                $relationName = $this->relationExists($relType) ? $relType : $currentType;
                $className = $this->getRelationClass($relationName);
            }
        } else {
            $className = $this->getRelationClass($relationName);
        }

        if ($className) {
            $foreignLinkName = (is_array($foreignLink) && array_key_exists('name', $foreignLink)) ?
                $foreignLink['name'] : null;

            $helperClass = new $className($this->metadata, $ormMetadata, $entityDefs, $this->config);

            return $helperClass->process($linkName, $entityName, $foreignLinkName, $foreignEntityName);
        }
        //END: relationDefs defined in separate file

        return null;
    }
}
