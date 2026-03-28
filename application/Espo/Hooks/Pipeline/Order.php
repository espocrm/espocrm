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

namespace Espo\Hooks\Pipeline;

use Espo\Core\Hook\Hook\AfterRemove;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Entities\Pipeline;
use Espo\Tools\Pipeline\MoveService;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Order as OrderPart;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Repository\Option\RemoveOptions;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements BeforeSave<Pipeline>
 * @implements AfterRemove<Pipeline>
 */
class Order implements BeforeSave, AfterRemove
{
    public function __construct(
        private EntityManager $entityManager,
        private MoveService $moveService,
    ) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew()) {
            return;
        }

        $entityType = $entity->getTargetEntityType();

        $query = SelectBuilder::create()
            ->from(Pipeline::ENTITY_TYPE)
            ->select(Expr::max(Expr::column(Pipeline::FIELD_ORDER)), 'max')
            ->select(Attribute::ID)
            ->group(Attribute::ID)
            ->limit(0, 1)
            ->order(Expr::max(Expr::column(Pipeline::FIELD_ORDER)), OrderPart::DESC)
            ->where([
                Pipeline::FIELD_ENTITY_TYPE => $entityType,
            ])
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($query);

        $row = $sth->fetch();

        $order = $row ? $row['max'] : 0;
        $order ++;

        $entity->set(Pipeline::FIELD_ORDER, $order);
    }

    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        $this->moveService->reOrder($entity::class);
    }
}
