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

namespace Espo\Core\Job;

use Espo\Core\Utils\Metadata;

class MetadataProvider
{
    public function __construct(private Metadata $metadata)
    {}

    /**
     * @return string[]
     */
    public function getPreparableJobNameList(): array
    {
        $list = [];

        $items = $this->metadata->get(['app', 'scheduledJobs']) ?? [];

        foreach ($items as $name => $item) {
            $isPreparable = (bool) ($item['preparatorClassName'] ?? null);

            if ($isPreparable) {
                $list[] = $name;
            }
        }

        return $list;
    }

    public function isJobSystem(string $name): bool
    {
        return (bool) $this->metadata->get(['app', 'scheduledJobs', $name, 'isSystem']);
    }

    public function isJobPreparable(string $name): bool
    {
        return (bool) $this->metadata->get(['app', 'scheduledJobs', $name, 'preparatorClassName']);
    }

    public function getPreparatorClassName(string $name): ?string
    {
        return $this->metadata->get(['app', 'scheduledJobs', $name, 'preparatorClassName']);
    }

    public function getJobClassName(string $name): ?string
    {
        return $this->metadata->get(['app', 'scheduledJobs', $name, 'jobClassName']);
    }

    /**
     * @return string[]
     */
    public function getScheduledJobNameList(): array
    {
        /** @var array<string, mixed> $items */
        $items = $this->metadata->get(['app', 'scheduledJobs']) ?? [];

        return array_keys($items);
    }

    /**
     * @return string[]
     */
    public function getNonSystemScheduledJobNameList(): array
    {
        return array_filter(
            $this->getScheduledJobNameList(),
            function (string $item) {
                $isSystem = (bool) $this->metadata->get(['app', 'scheduledJobs', $item, 'isSystem']);

                return !$isSystem;
            }
        );
    }
}
