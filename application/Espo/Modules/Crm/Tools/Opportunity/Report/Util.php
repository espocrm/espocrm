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

namespace Espo\Modules\Crm\Tools\Opportunity\Report;

use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Opportunity as OpportunityEntity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;

class Util
{
    private Metadata $metadata;
    private EntityManager $entityManager;

    public function __construct(
        Metadata $metadata,
        EntityManager $entityManager
    ) {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
    }

    /**
     * A grouping-by with distinct will give wrong results. Need to use sub-query.
     *
     * @param array<string|int, mixed> $whereClause
     */
    public function handleDistinctReportQueryBuilder(SelectBuilder $queryBuilder, array $whereClause): void
    {
        if (!$queryBuilder->build()->isDistinct()) {
            return;
        }

        $subQuery = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(OpportunityEntity::ENTITY_TYPE)
            ->select(Attribute::ID)
            ->where($whereClause)
            ->build();

        $queryBuilder->where([
            'id=s' => $subQuery,
        ]);
    }

    /**
     * @return string[]
     */
    public function getLostStageList(): array
    {
        $list = [];

        $probabilityMap =  $this->metadata
            ->get(['entityDefs', OpportunityEntity::ENTITY_TYPE, 'fields', 'stage', 'probabilityMap']) ?? [];

        $stageList = $this->metadata->get('entityDefs.Opportunity.fields.stage.options', []);

        foreach ($stageList as $stage) {
            $value = $probabilityMap[$stage] ?? 0;

            if (!$value) {
                $list[] = $stage;
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    public function getWonStageList(): array
    {
        $list = [];

        $probabilityMap =  $this->metadata
            ->get(['entityDefs', OpportunityEntity::ENTITY_TYPE, 'fields', 'stage', 'probabilityMap']) ?? [];

        $stageList = $this->metadata->get('entityDefs.Opportunity.fields.stage.options', []);

        foreach ($stageList as $stage) {
            $value = $probabilityMap[$stage] ?? 0;

            if ($value == 100) {
                $list[] = $stage;
            }
        }

        return $list;
    }
}
