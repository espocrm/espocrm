<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Functions\Base;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\Core\Di;
use stdClass;

/**
 * @noinspection PhpUnused
 */
class CountRelatedType extends Base implements
    Di\EntityManagerAware,
    Di\InjectableFactoryAware,
    Di\UserAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;
    use Di\UserSetter;

    /**
     * @return int
     * @throws Error
     */
    public function process(stdClass $item)
    {
        if (count($item->value) < 1) {
            throw new Error("countRelated: roo few arguments.");
        }

        $link = $this->evaluate($item->value[0]);

        if (empty($link)) {
            throw new Error("countRelated: no link passed.");
        }

        $filter = null;

        if (count($item->value) > 1) {
            $filter = $this->evaluate($item->value[1]);
        }

        $entity = $this->getEntity();

        $entityManager = $this->entityManager;

        $foreignEntityType = $entity->getRelationParam($link, RelationParam::ENTITY);

        if (empty($foreignEntityType)) {
            throw new Error();
        }

        $builder = $this->injectableFactory->create(SelectBuilderFactory::class)
            ->create()
            ->forUser($this->user)
            ->from($foreignEntityType);

        if ($filter) {
            (new FindQueryUtil())->applyFilter($builder, $filter, 2);
        }

        try {
            $query = $builder->build();
        } catch (BadRequest|Forbidden $e) {
            throw new Error($e->getMessage());
        }

        return $entityManager->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, $link)
            ->clone($query)
            ->count();
    }
}
