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

namespace Espo\Modules\Crm\Classes\Select\Opportunity\Utils;

use Espo\Core\Utils\Metadata;

class StageListProvider
{
    public function __construct(private Metadata $metadata)
    {}

    /**
     * @return string[]
     */
    public function getLost(): array
    {
        $output = [];

        $probabilityMap = $this->getProbabilityMap();

        foreach ($this->getStageList() as $stage) {
            $value = $probabilityMap[$stage] ?? null;

            if ($value === 0 || $value === 0.0) {
                $output[] = $stage;
            }
        }

        return $output;
    }

    /**
     * @return string[]
     */
    public function getWon(): array
    {
        $output = [];

        $probabilityMap = $this->getProbabilityMap();

        foreach ($this->getStageList() as $stage) {
            $value = $probabilityMap[$stage] ?? null;

            if ($value == 100) {
                $output[] = $stage;
            }
        }

        return $output;
    }

    /**
     * @return array<string, ?int>
     */
    private function getProbabilityMap(): array
    {
        /** @var array<string, ?int> $probabilityMap */
        $probabilityMap = $this->metadata->get('entityDefs.Opportunity.fields.stage.probabilityMap') ?? [];

        return $probabilityMap;
    }

    /**
     * @return string[]
     */
    private function getStageList(): array
    {
        return $this->metadata->get('entityDefs.Opportunity.fields.stage.options') ?? [];
    }
}
