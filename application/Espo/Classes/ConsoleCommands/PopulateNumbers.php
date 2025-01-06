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
use Espo\Core\Console\Exceptions\ArgumentNotSpecified;
use Espo\Core\Console\Exceptions\InvalidArgument;
use Espo\Core\Console\IO;
use Espo\Core\Exceptions\Error;
use Espo\Core\FieldProcessing\NextNumber\BeforeSaveProcessor;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;

class PopulateNumbers implements Command
{
    private BeforeSaveProcessor $beforeSaveProcessor;
    private EntityManager $entityManager;

    public function __construct(
        BeforeSaveProcessor $beforeSaveProcessor,
        EntityManager $entityManager
    ) {
        $this->beforeSaveProcessor = $beforeSaveProcessor;
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Error
     */
    public function run(Params $params, IO $io): void
    {
        $entityType = $params->getArgument(0);
        $field = $params->getArgument(1);

        $orderBy = $params->getOption('orderBy') ?? Field::CREATED_AT;
        $order = strtoupper($params->getOption('order') ?? Order::ASC);

        if (!$entityType) {
            throw new ArgumentNotSpecified("No entity type argument.");
        }

        if (!$field) {
            throw new ArgumentNotSpecified("No field argument.");
        }

        if ($order !== Order::ASC && $order !== Order::DESC) {
            throw new InvalidArgument("Bad order option.");
        }

        $fieldType = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getField($field)
            ->getType();

        if ($fieldType !== 'number') {
            throw new InvalidArgument("Field `{$field}` is not of `number` type.");
        }

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->where([
                $field => null,
            ])
            ->order($orderBy, $order)
            ->sth()
            ->find();

        foreach ($collection as $i => $entity) {
            if (!$entity instanceof CoreEntity) {
                throw new Error();
            }

            $this->beforeSaveProcessor->processPopulate($entity, $field);
            $this->entityManager->saveEntity($entity, [SaveOption::IMPORT => true]);

            if ($i % 1000 === 0) {
                $io->write('.');
            }
        }

        $io->writeLine('');
        $io->writeLine('Done.');
    }
}
