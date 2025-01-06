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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Functions\BaseFunction;

use Espo\Core\Di;
use RuntimeException;
use stdClass;

/**
 * @noinspection PhpUnused
 */
class CreateType extends BaseFunction implements
    Di\EntityManagerAware
{
    use Di\EntityManagerSetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $args = $this->evaluate($args);

        if (!is_array($args)) {
            throw new RuntimeException();
        }

        $entityType = $args[0];

        if (!is_string($entityType)) {
            $this->throwBadArgumentType(1, 'string');
        }

        $data = $this->getData($args, $entityType);

        $entity = $this->entityManager->createEntity($entityType, $data);

        return $entity->getId();
    }

    /**
     * @param array<int, mixed> $args
     * @return array<string, mixed>
     * @throws BadArgumentType
     */
    private function getData(array $args, mixed $entityType): array
    {
        if (count($args) >= 2 && $args[1] instanceof stdClass) {
            return get_object_vars($args[1]);
        }

        $data = [];

        $i = 1;

        while ($i < count($args) - 1) {
            $attribute = $args[$i];

            if (!is_string($entityType)) {
                $this->throwBadArgumentType($i + 1, 'string');
            }

            /** @var string $attribute */

            $value = $args[$i + 1];

            $data[$attribute] = $value;

            $i = $i + 2;
        }

        return $data;
    }
}
