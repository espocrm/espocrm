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

namespace Espo\Tools\Export\Processors\Xlsx;

use Espo\ORM\Defs;

class FieldHelper
{
    public function __construct(
        private Defs $ormDefs
    ) {}

    public function isForeign(string $entityType, string $name): bool
    {
        if (str_contains($name, '_')) {
            return true;
        }

        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->hasField($name) &&
            $entityDefs->getField($name)->getType() === 'foreign';
    }

    public function getData(string $entityType, string $name): ?FieldData
    {
        $entityDefs = $this->ormDefs->getEntity($entityType);

        if (!$this->isForeign($entityType, $name)) {
            if (!$entityDefs->hasField($name)) {
                return null;
            }

            $type = $entityDefs
                ->getField($name)
                ->getType();

            return new FieldData($entityType, $name, $type);
        }

        $link = null;
        $field = null;

        if (
            $entityDefs->hasField($name) &&
            $entityDefs->getField($name)->getType() === 'foreign'
        ) {
            $fieldDefs = $entityDefs->getField($name);

            $link = $fieldDefs->getParam('link');
            $field = $fieldDefs->getParam('field');
        }
        else if (str_contains($name, '_')) {
            [$link, $field] = explode('_', $name);
        }

        if (!$link || !$field) {
            return null;
        }

        $entityDefs = $this->ormDefs->getEntity($entityType);

        if (!$entityDefs->hasRelation($link)) {
            return null;
        }

        $relationDefs = $entityDefs->getRelation($link);

        if (!$relationDefs->hasForeignEntityType()) {
            return null;
        }

        $foreignEntityType = $relationDefs->getForeignEntityType();

        $type = $this->ormDefs
            ->getEntity($foreignEntityType)
            ->getField($field)
            ->getType();

        return new FieldData($foreignEntityType, $field, $type);
    }
}
