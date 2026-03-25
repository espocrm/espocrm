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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Acl\SystemRestriction;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Functions\BaseFunction;

use Espo\Core\Di;

/**
 * @noinspection PhpUnused
 */
class RelationColumnType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    public function process(ArgumentList $args)
    {
        $args = $this->evaluate($args);

        if (count($args) < 5) {
            $this->throwTooFewArguments(5);
        }

        $entityType = $args[0];
        $id = $args[1];
        $link = $args[2];
        $foreignId = $args[3];
        $column = $args[4];

        if (!is_string($entityType)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!is_string($id)) {
            throw BadArgumentType::create(2, 'string');
        }

        if (!is_string($link)) {
            throw BadArgumentType::create(3, 'string');
        }

        if (!is_string($foreignId)) {
            throw BadArgumentType::create(4, 'string');
        }

        if (!is_string($column)) {
            throw BadArgumentType::create(5, 'string');
        }

        $this->assertLinkRead($entityType, $link);

        $em = $this->entityManager;

        if (!$em->hasRepository($entityType)) {
            $this->throwError("Repository '$entityType' does not exist.");
        }

        $entity = $em->getEntityById($entityType, $id);

        if (!$entity) {
            return null;
        }

        return $em->getRelation($entity, $link)
            ->getColumnById($foreignId, $column);
    }

    /**
     * @throws NotAllowedUsage
     */
    private function assertLinkRead(string $entityType, string $link): void
    {
        $restriction = $this->injectableFactory->create(SystemRestriction::class);

        if (!$restriction->checkLinkRead($entityType, $link) ) {
            throw new NotAllowedUsage("Cannot read restricted link $entityType.$link.");
        }
    }
}
