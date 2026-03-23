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

namespace Espo\Core\Formula\Functions;

use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Processor;
use Espo\Core\Formula\Utils\EntityUtil;
use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use stdClass;

class SetAttributeType extends Base
{
    public function __construct(
        private EntityUtil $entityUtil,
        string $name,
        Processor $processor,
        ?Entity $entity = null,
        ?stdClass $variables = null,
    ) {
        parent::__construct(
            name: $name,
            processor: $processor,
            entity: $entity,
            variables: $variables,
        );
    }

    /**
     * @return mixed
     * @throws Error
     */
    public function process(stdClass $item)
    {
        if (count($item->value) < 2) {
            throw TooFewArguments::create(2);
        }

        $attribute = $this->evaluate($item->value[0]);

        if (!is_string($attribute)) {
            throw BadArgumentType::create(1, 'string');
        }

        if ($attribute === Attribute::ID) {
            throw new NotAllowedUsage("Not allowed to set `id` attribute.");
        }

        $value = $this->evaluate($item->value[1]);

        $entity = $this->getEntity();

        $entityType = $entity->getEntityType();

        if (in_array($attribute, $this->entityUtil->getWriteRestrictedAttributeList($entityType))) {
            throw new NotAllowedUsage("Cannot write $entityType.$attribute.");
        }

        $entity->set($attribute, $value);

        $this->entityUtil->assertUpdateAccess($entity);

        return $value;
    }
}
