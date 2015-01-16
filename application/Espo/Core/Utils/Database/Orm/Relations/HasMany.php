<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils\Database\Orm\Relations;

class HasMany extends Base
{
    protected function load($linkName, $entityName)
    {
        $linkParams = $this->getLinkParams();
        $foreignLinkName = $this->getForeignLinkName();
        $foreignEntityName = $this->getForeignEntityName();

        $relationType = isset($linkParams['relationName']) ? 'manyMany' : 'hasMany';

        $relation = array(
            $entityName => array (
                'fields' => array(
                       $linkName.'Ids' => array(
                        'type' => 'varchar',
                        'notStorable' => true,
                    ),
                    $linkName.'Names' => array(
                        'type' => 'varchar',
                        'notStorable' => true,
                    ),
                ),
                'relations' => array(
                    $linkName => array(
                        'type' => $relationType,
                        'entity' => $foreignEntityName,
                        'foreignKey' => lcfirst($foreignLinkName.'Id'),
                    ),
                ),
            ),
        );

        return $relation;
    }


}