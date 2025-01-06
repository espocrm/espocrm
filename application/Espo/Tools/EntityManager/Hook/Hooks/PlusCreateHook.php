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

namespace Espo\Tools\EntityManager\Hook\Hooks;

use Espo\Core\Name\Field;
use Espo\Core\Templates\Entities\BasePlus;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Task;
use Espo\Tools\EntityManager\Hook\CreateHook;
use Espo\Tools\EntityManager\Params;

class PlusCreateHook implements CreateHook
{
    public function __construct(
        private Config $config,
        private Metadata $metadata
    ) {}

    public function process(Params $params): void
    {
        if (
            !in_array($params->getType(), [
                BasePlus::TEMPLATE_TYPE,
                Company::TEMPLATE_TYPE,
                Person::TEMPLATE_TYPE,
            ])
        ) {
            return;
        }

        $name = $params->getName();

        $activitiesEntityTypeList = $this->config->get('activitiesEntityList', []);
        $historyEntityTypeList = $this->config->get('historyEntityList', []);

        $entityTypeList = array_merge($activitiesEntityTypeList, $historyEntityTypeList);
        $entityTypeList[] = Task::ENTITY_TYPE;
        $entityTypeList = array_unique($entityTypeList);

        foreach ($entityTypeList as $entityType) {
            if (!$this->metadata->get(['entityDefs', $entityType, 'fields', Field::PARENT, 'entityList'])) {
                continue;
            }

            $list = $this->metadata->get(['entityDefs', $entityType, 'fields', Field::PARENT, 'entityList'], []);

            if (!in_array($name, $list)) {
                $list[] = $name;

                $data = [
                    'fields' => [
                        Field::PARENT => ['entityList' => $list]
                    ]
                ];

                $this->metadata->set('entityDefs', $entityType, $data);
            }
        }

        $this->metadata->save();
    }
}
