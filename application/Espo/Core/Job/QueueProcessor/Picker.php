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

namespace Espo\Core\Job\QueueProcessor;

use Espo\Core\Job\QueueUtil;
use Espo\Entities\Job;
use RuntimeException;

/**
 * Picks jobs for a portion distributing by weights if needed.
 */
class Picker
{
    public function __construct(
        private QueueUtil $queueUtil,
    ) {}

    /**
     * @param Params $params
     * @return iterable<Job>
     */
    public function pick(Params $params): iterable
    {
        $paramsList = $params->getSubQueueParamsList();

        if (!$paramsList) {
            return $this->queueUtil->getPendingJobs($params);
        }

        $groups = [];

        foreach ($paramsList as $itemParams) {
            $groups[] = iterator_to_array($this->queueUtil->getPendingJobs($itemParams));
        }

        return $this->pickJobsRecursively($paramsList, $groups, $params->getLimit());
    }

    /**
     * @param Params[] $paramsList,
     * @param Job[][] $groups
     * @return Job[]
     */
    private function pickJobsRecursively(
        array $paramsList,
        array $groups,
        int $limit,
    ): array {

        $totalWeight = array_reduce($paramsList, fn ($c, $it) => $c + $it->getWeight(), 0.0);

        /** @var Job[][] $leftovers */
        $leftovers = [];
        $output = [];

        foreach ($paramsList as $i => $itemParams) {
            if (!array_key_exists($i, $groups)) {
                throw new RuntimeException();
            }

            $jobs = $groups[$i];
            $weight = $itemParams->getWeight();

            $portion = (int) round($weight / $totalWeight * $limit);

            $pickedJobs = [];

            while (count($pickedJobs) < $portion) {
                if (count($jobs) === 0) {
                    break;
                }

                $pickedJobs[] = array_shift($jobs);
            }

            $output = array_merge($output, $pickedJobs);

            $leftovers[] = $jobs;
        }

        $left = $limit - count($output);
        $leftoverCount = array_reduce($leftovers, fn ($c, $it) => $c + count($it), 0);

        if ($left && $leftoverCount) {
            foreach ($leftovers as $i => $jobs) {
                if (count($jobs) === 0) {
                    unset($leftovers[$i]);
                    unset($paramsList[$i]);
                }
            }

            $leftovers = array_values($leftovers);
            $paramsList = array_values($paramsList);

            $rest = $this->pickJobsRecursively(
                paramsList: $paramsList,
                groups: $leftovers,
                limit: $leftoverCount,
            );

            $output = array_merge($output, $rest);
            $output = array_slice($output, 0, $limit);
        }

        return $output;
    }
}
