<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Job;

use Espo\Core\Utils\Metadata;

class MetadataProvider
{
    private $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

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

    public function getScheduledJobNameList(): array
    {
        return array_keys($this->metadata->get(['app', 'scheduledJobs']) ?? []);
    }

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
