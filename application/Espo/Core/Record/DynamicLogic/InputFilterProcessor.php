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

namespace Espo\Core\Record\DynamicLogic;

use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\Tools\DynamicLogic\ConditionChecker;
use Espo\Tools\DynamicLogic\ConditionCheckerFactory;
use Espo\Tools\DynamicLogic\Exceptions\BadCondition;
use Espo\Tools\DynamicLogic\Item;
use RuntimeException;
use stdClass;

class InputFilterProcessor
{
    public function __construct(
        private Metadata $metadata,
        private FieldUtil $fieldUtil,
        private ConditionCheckerFactory $conditionCheckerFactory,
    ) {}

    public function process(Entity $entity, stdClass $input): void
    {
        /** @var array<string, array<string, mixed>> $fieldsDefs */
        $fieldsDefs = $this->metadata->get("logicDefs.{$entity->getEntityType()}.fields") ?? [];

        $checker = null;

        foreach ($fieldsDefs as $field => $defs) {
            if ($defs['readOnlySaved'] ?? null) {
                $checker ??= $this->conditionCheckerFactory->create($entity);

                $this->processField($entity, $input, $field, $checker);
            }
        }
    }

    private function processField(Entity $entity, stdClass $input, string $field, ConditionChecker $checker): void
    {
        /** @var ?stdClass[] $group */
        $group = $this->metadata
            ->getObjects("logicDefs.{$entity->getEntityType()}.fields.$field.readOnlySaved.conditionGroup");

        if (!$group) {
            return;
        }

        try {
            $item = Item::fromGroupDefinition($group);

            if (!$checker->check($item)) {
                return;
            }
        } catch (BadCondition $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        foreach ($this->fieldUtil->getAttributeList($entity->getEntityType(), $field) as $attribute) {
            unset($input->$attribute);
        }
    }
}
