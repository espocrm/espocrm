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

namespace Espo\Core\ORM;

class Entity extends \Espo\ORM\Entity
{

    public function loadLinkMultipleField($field, $columns = null)
    {
        if ($this->hasRelation($field) && $this->hasField($field . 'Ids')) {

            $defs = array();
            if (!empty($columns)) {
                $defs['additionalColumns'] = $columns;
            }

            $collection = $this->get($field, $defs);
            $ids = array();
            $names = new \stdClass();
            if (!empty($columns)) {
                $columnsData = new \stdClass();
            }

            foreach ($collection as $e) {
                $id = $e->id;
                $ids[] = $id;
                $names->$id = $e->get('name');
                if (!empty($columns)) {
                    $columnsData->$id = new \stdClass();
                    foreach ($columns as $column => $f) {
                        $columnsData->$id->$column = $e->get($f);
                    }
                }
            }
            $this->set($field . 'Ids', $ids);
            $this->set($field . 'Names', $names);
            if (!empty($columns)) {
                $this->set($field . 'Columns', $columnsData);
            }
        }
    }
}

