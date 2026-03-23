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

namespace Espo\Core\Formula\Functions\EntityGroup;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Exceptions\NotPassedEntity;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Entity;

/**
 * @noinspection PhpUnused
 */
class SetLinkMultipleColumnType implements Func
{
    public function __construct(
        private SystemRestriction $systemRestriction,
        private ?Entity $entity = null,
    ) {}

    public function process(EvaluatedArgumentList $arguments): null
    {
        $entity = $this->entity ?? throw new NotPassedEntity();

        if (!$entity instanceof CoreEntity) {
            throw new Error("Non-core entity.");
        }

        if (count($arguments) < 4) {
            throw TooFewArguments::create(4);
        }

        $link = $arguments[0];
        $id = $arguments[1];
        $column = $arguments[2];
        $value = $arguments[3];

        if (!is_string($link)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!is_string($id)) {
            throw BadArgumentType::create(2, 'string');
        }

        if (!is_string($column)) {
            throw BadArgumentType::create(3, 'string');
        }

        $entityType = $entity->getEntityType();

        if (!$this->systemRestriction->checkFieldWrite($entityType, $link)) {
            throw new NotAllowedUsage("Cannot write restricted field $entityType.$link.");
        }

        $entity->setLinkMultipleColumn($link, $column, $id, $value);

        return null;
    }
}
