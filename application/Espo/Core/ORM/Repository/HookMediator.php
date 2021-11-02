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

namespace Espo\Core\ORM\Repository;

use Espo\ORM\{
    Entity,
    Repository\EmptyHookMediator,
    Query\Select,
};

use Espo\Core\HookManager;

class HookMediator extends EmptyHookMediator
{
    protected $hookManager;

    public function __construct(HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
    }

    public function afterRelate(
        Entity $entity,
        string $relationName,
        Entity $foreignEntity,
        ?array $columnData,
        array $options
    ): void {

        if (!empty($options['skipHooks'])) {
            return;
        }

        $hookData = [
            'relationName' => $relationName,
            'relationData' => $columnData,
            'foreignEntity' => $foreignEntity,
            'foreignId' => $foreignEntity->getId()
        ];

        $this->hookManager->process(
            $entity->getEntityType(),
            'afterRelate',
            $entity,
            $options,
            $hookData
        );
    }
    public function afterUnrelate(Entity $entity, string $relationName, Entity $foreignEntity, array $options): void
    {
        if (!empty($options['skipHooks'])) {
            return;
        }

        $hookData = [
            'relationName' => $relationName,
            'foreignEntity' => $foreignEntity,
            'foreignId' => $foreignEntity->getId(),
        ];

        $this->hookManager->process(
            $entity->getEntityType(),
            'afterUnrelate',
            $entity,
            $options,
            $hookData
        );
    }

    public function afterMassRelate(Entity $entity, string $relationName, Select $query, array $options): void
    {
        if (!empty($options['skipHooks'])) {
            return;
        }

        $hookData = [
            'relationName' => $relationName,
            'query' => $query,
        ];

        $this->hookManager->process(
            $entity->getEntityType(),
            'afterMassRelate',
            $entity,
            $options,
            $hookData
        );
    }
}
