<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Export\Format\Xlsx;

use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Defs;

class FieldHelper
{
    public function __construct(
        private Defs $ormDefs
    ) {}

    public function isForeignReference(string $name): bool
    {
        return str_contains($name, '_');
    }

    private function isForeign(string $entityType, string $name): bool
    {
        if ($this->isForeignReference($name)) {
            return true;
        }

        $entityDefs = $this->ormDefs->getEntity($entityType);

        return
            $entityDefs->hasField($name) &&
            $entityDefs->getField($name)->getType() === FieldType::FOREIGN;
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

            return new FieldData($entityType, $name, $type, null);
        }

        $link = null;
        $field = null;

        if (
            $entityDefs->hasField($name) &&
            $entityDefs->getField($name)->getType() === FieldType::FOREIGN
        ) {
            $fieldDefs = $entityDefs->getField($name);

            $link = $fieldDefs->getParam('link');
            $field = $fieldDefs->getParam('field');
        } else if (str_contains($name, '_')) {
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

        return new FieldData($foreignEntityType, $field, $type, $link);
    }
}
