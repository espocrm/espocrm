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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
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
            $types = new \stdClass();
            if (!empty($columns)) {
                $columnsData = new \stdClass();
            }

            if ($collection) {
                foreach ($collection as $e) {
                    $id = $e->id;
                    $ids[] = $id;
                    $names->$id = $e->get('name');
                    $types->$id = $e->get('type');
                    if (!empty($columns)) {
                        $columnsData->$id = new \stdClass();
                        foreach ($columns as $column => $f) {
                            $columnsData->$id->$column = $e->get($f);
                        }
                    }
                }
            }

            $this->set($field . 'Ids', $ids);
            $this->set($field . 'Names', $names);
            $this->set($field . 'Types', $types);
            if (!empty($columns)) {
                $this->set($field . 'Columns', $columnsData);
            }
        }
    }
}

