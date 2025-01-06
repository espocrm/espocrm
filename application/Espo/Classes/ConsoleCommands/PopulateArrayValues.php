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

namespace Espo\Classes\ConsoleCommands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Exceptions\Error;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\ArrayValue;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\ArrayValue as ArrayValueRepository;

class PopulateArrayValues implements Command
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Error
     */
    public function run(Params $params, IO $io): void
    {
        $entityType = $params->getArgument(0);
        $field = $params->getArgument(1);

        if (!$entityType || !$field) {
            throw new Error("Entity type and field should be passed as arguments.");
        }

        if (!$this->entityManager->hasRepository($entityType)) {
            throw new Error("Bad entity type.");
        }

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if (!$defs->hasAttribute($field)) {
            throw new Error("Bad field.");
        }

        if ($defs->getAttribute($field)->getType() !== Entity::JSON_ARRAY) {
            throw new Error("Non-array field.");
        }

        if ($defs->getAttribute($field)->isNotStorable()) {
            throw new Error("Not-storable field.");
        }

        if (!$defs->getAttribute($field)->getParam('storeArrayValues')) {
            throw new Error("Array values disabled for the field..");
        }

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->sth()
            ->find();

        /** @var ArrayValueRepository $repository */
        $repository = $this->entityManager->getRepository(ArrayValue::ENTITY_TYPE);

        foreach ($collection as $i => $entity) {
            if (!$entity instanceof CoreEntity) {
                throw new Error();
            }

            $repository->storeEntityAttribute($entity, $field);

            if ($i % 1000 === 0) {
                $io->write('.');
            }
        }

        $io->writeLine('');
        $io->writeLine('Done.');
    }
}
