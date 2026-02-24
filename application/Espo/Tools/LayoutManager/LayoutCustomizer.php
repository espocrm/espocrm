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

namespace Espo\Tools\LayoutManager;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Json;
use Espo\Tools\Layout\LayoutProvider;
use RuntimeException;
use stdClass;

/**
 * @since 9.4.0
 */
class LayoutCustomizer
{
    public function __construct(
        private LayoutProvider $layoutProvider,
        private LayoutManager $layoutManager,
    ) {}

    /**
     * @throws Error
     */
    public function addDetailField(string $entityType, string $field, string $layoutName): void
    {
        $layoutData = $this->getDetailLayout($entityType, $layoutName);

        if ($this->hasInDetail($layoutData, $field)) {
            return;
        }

        $lastPanel = $layoutData[count($layoutData) - 1];

        if (!$lastPanel instanceof stdClass) {
            throw new RuntimeException("Bad layout panel definition in $entityType.$layoutName.");
        }

        if (isset($lastPanel->cols) && is_array($lastPanel->cols)) {
            $cols = $lastPanel->cols;

            $cols[] = [[(object) ['name' => $field]]];

            $lastPanel->cols = $cols;
        } else {
            $rows = $lastPanel->rows ?? [];

            $rows[] = [(object) ['name' => $field], false];

            $lastPanel->rows = $rows;
        }

        $this->layoutManager->set($layoutData, $entityType, $layoutName);
        $this->layoutManager->save();
    }

    /**
     * @throws Error
     */
    public function removeInDetail(string $entityType, string $field, string $layoutName): void
    {
        $panels = $this->getDetailLayout($entityType, $layoutName);

        $cell = $this->getCellFromDetail($panels, $field);

        if (!$cell) {
            return;
        }

        foreach ($panels as $panelItem) {
            if (isset($panelItem->cols)) {
                $rowItems =& $panelItem->cols;
            } else if (isset($panelItem->rows)) {
                $rowItems =& $panelItem->rows;
            } else {
                continue;
            }

            if (!is_array($rowItems)) {
                continue;
            }

            foreach ($rowItems as &$rowItem) {
                if (!is_array($rowItem)) {
                    continue;
                }

                foreach ($rowItem as $i => $cellItem) {
                    if (!$cellItem instanceof stdClass) {
                        continue;
                    }

                    if (($cellItem->name ?? null) === $field) {
                        $rowItem[$i] = false;
                    }
                }
            }
        }

        $this->layoutManager->set($panels, $entityType, $layoutName);
        $this->layoutManager->save();
    }

    /**
     * @param array<int, mixed> $panels
     */
    private function hasInDetail(array $panels, string $field): bool
    {
        return $this->getCellFromDetail($panels, $field) !== null;
    }

    /**
     * @param array<int, mixed> $panels
     */
    private function getCellFromDetail(array $panels, string $field): ?stdClass
    {
        foreach ($panels as $panelItem) {
            $rowItems = $panelItem->cols ?? $panelItem->rows ?? null;

            if (!is_array($rowItems)) {
                continue;
            }

            foreach ($rowItems as $rowItem) {
                if (!is_array($rowItem)) {
                    continue;
                }

                foreach ($rowItem as $cellItem) {
                    if (!$cellItem instanceof stdClass) {
                        continue;
                    }

                    if (($cellItem->name ?? null) === $field) {
                        return $cellItem;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, mixed>
     */
    private function getDetailLayout(string $entityType, string $layoutName): array
    {
        $layoutString = $this->layoutProvider->get($entityType, $layoutName);

        if (!$layoutString) {
            $layoutString = '[]';
        }

        $layoutData = Json::decode($layoutString);

        if (!is_array($layoutData)) {
            throw new RuntimeException("Bad layout $entityType.$layoutName.");
        }

        return $layoutData;
    }
}
