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

namespace Espo\Classes\Select\PipelineStage\AccessControlFilters;

use Espo\Core\Select\AccessControl\Filter;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\Entities\User;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\Pipeline\PipelineDataProvider;
use Espo\Tools\Pipeline\PipelineDataUserFilter;

class Mandatory implements Filter
{
    public function __construct(
        private User $user,
        private PipelineDataProvider $pipelineDataProvider,
        private PipelineDataUserFilter $filter,
    ) {}

    public function apply(SelectBuilder $queryBuilder): void
    {
        if ($this->user->isAdmin()) {
            return;
        }

        $data = $this->pipelineDataProvider->get();
        $data = $this->filter->filter($data);

        $entityTypes = array_keys($data);

        $ids = [];

        foreach ($data as $items) {
            foreach ($items as $item) {
                $ids[] = $item->id;
            }
        }

        $alias = 'pipelineAccess';

        $queryBuilder
            ->join(PipelineStage::FIELD_PIPELINE, $alias)
            ->where([
                $alias . '.' . Pipeline::FIELD_ENTITY_TYPE => $entityTypes,
                $alias . '.' . Attribute::ID => $ids,
            ]);
    }
}
