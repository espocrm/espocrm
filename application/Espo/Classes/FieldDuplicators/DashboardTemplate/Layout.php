<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Classes\FieldDuplicators\DashboardTemplate;

use Espo\Core\Record\Duplicator\FieldDuplicator;
use Espo\Core\Utils\ObjectUtil;
use Espo\Core\Utils\Util;
use Espo\Entities\DashboardTemplate;
use Espo\ORM\Entity;
use LogicException;
use RuntimeException;
use stdClass;

/**
 * @implements FieldDuplicator<DashboardTemplate>
 */
class Layout implements FieldDuplicator
{
    public function duplicate(Entity $entity, string $field): stdClass
    {
        $layout = $entity->getLayoutRaw();
        $options = $entity->getDashletsOptionsRaw();

        if (!$layout) {
            return (object) [];
        }

        $copyLayout = [];
        $idMap = [];

        foreach ($layout as $tab) {
            $copyLayout[] = $this->copyTab($tab, $idMap);
        }

        $copyOptions = (object) [];

        foreach (get_object_vars($options) as $id => $item) {
            $copyId = $idMap[$id] ?? null;

            if (!is_string($copyId)) {
                throw new LogicException();
            }

            if (!$item instanceof stdClass) {
                throw new RuntimeException("Bad dashboard options.");
            }

            $copyOptions->$copyId = ObjectUtil::clone($item);
        }

        return (object) [
            DashboardTemplate::FIELD_LAYOUT => $copyLayout,
            DashboardTemplate::FIELD_DASHLETS_OPTIONS => $copyOptions,
        ];
    }

    /**
     * @param array<string, string> $idMap
     */
    private function copyTab(stdClass $tab, array &$idMap): stdClass
    {
        $copy = ObjectUtil::clone($tab);

        $copy->id = Util::generateId();

        $layout = $copy->layout ?? [];

        foreach ($layout as $item) {
            if (!$item instanceof stdClass) {
                throw new RuntimeException("Bad layout dashlet definition.");
            }

            $id = $item->id ?? null;

            if (!is_string($id)) {
                throw new RuntimeException("No string ID in layout dashlet definition.");
            }

            $item->id = Util::generateId();

            $idMap[$id] = $item->id;
        }

        return $copy;
    }
}
