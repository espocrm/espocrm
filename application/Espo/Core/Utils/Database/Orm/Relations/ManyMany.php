<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Database\Orm\Relations;

use Espo\Core\Utils\Util;

class ManyMany extends Base
{
    protected function load($linkName, $entityType)
    {
        $foreignEntityName = $this->getForeignEntityName();
        $foreignLinkName = $this->getForeignLinkName();

        $linkParams = $this->getLinkParams();

        if (!empty($linkParams['relationName'])) {
            $relationName = $linkParams['relationName'];
        } else {
            $relationName = $this->getJoinTable($entityType, $foreignEntityName);
        }

        $isStub = !$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $linkName]);

        $key1 = lcfirst($entityType) . 'Id';
        $key2 = lcfirst($foreignEntityName) . 'Id';

        if ($key1 === $key2) {
            if (strcmp($linkName, $foreignLinkName)) {
                $key1 = 'leftId';
                $key2 = 'rightId';
            } else {
                $key1 = 'rightId';
                $key2 = 'leftId';
            }
        }

        return [
            $entityType => [
                'fields' => [
                    $linkName.'Ids' => [
                        'type' => 'jsonArray',
                        'notStorable' => true,
                        'isLinkStub' => $isStub,
                    ],
                    $linkName.'Names' => [
                        'type' => 'jsonObject',
                        'notStorable' => true,
                        'isLinkStub' => $isStub,
                    ],
                ],
                'relations' => [
                    $linkName => [
                        'type' => 'manyMany',
                        'entity' => $foreignEntityName,
                        'relationName' => $relationName,
                        'key' => 'id',
                        'foreignKey' => 'id',
                        'midKeys' => [
                            $key1,
                            $key2,
                        ],
                        'foreign' => $foreignLinkName,
                    ],
                ],
            ],
        ];
    }

    protected function getJoinTable($tableName1, $tableName2)
    {
        $tables = $this->getSortEntities($tableName1, $tableName2);

        return Util::toCamelCase( implode('_', $tables) );
    }

    protected function getSortEntities($entity1, $entity2)
    {
        $entities = array(
            Util::toCamelCase(lcfirst($entity1)),
            Util::toCamelCase(lcfirst($entity2)),
        );

        sort($entities);

        return $entities;
    }

}