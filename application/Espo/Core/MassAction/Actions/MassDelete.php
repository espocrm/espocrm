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

namespace Espo\Core\MassAction\Actions;

use Espo\Core\{
    MassAction\QueryBuilder,
    MassAction\Params,
    MassAction\Result,
    MassAction\Data,
    MassAction\MassAction,
    Acl,
    Record\ServiceContainer as RecordServiceContainer,
    ORM\EntityManager,
    Exceptions\Forbidden,
};

class MassDelete implements MassAction
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var RecordServiceContainer
     */
    protected $recordServiceContainer;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(
        QueryBuilder $queryBuilder,
        Acl $acl,
        RecordServiceContainer $recordServiceContainer,
        EntityManager $entityManager
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->acl = $acl;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->entityManager = $entityManager;
    }

    public function process(Params $params, Data $dataWrapped): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, 'delete')) {
            throw new Forbidden("No delete access for '{$entityType}'.");
        }

        if (!$params->hasIds() && $this->acl->get('massUpdatePermission') !== 'yes') {
            throw new Forbidden("No mass-update permission.");
        }

        $service = $this->recordServiceContainer->get($entityType);

        $repository = $this->entityManager->getRDBRepository($entityType);

        $query = $this->queryBuilder->build($params);

        $collection = $repository
            ->clone($query)
            ->sth()
            ->find();

        $ids = [];

        $count = 0;

        foreach ($collection as $entity) {
            if (!$this->acl->check($entity, 'delete')) {
                continue;
            }

            $repository->remove($entity);

            $ids[] = $entity->getId();

            $count++;

            $service->processActionHistoryRecord('delete', $entity);
        }

        $result = [
            'count' => $count,
            'ids' => $ids,
        ];

        return Result::fromArray($result);
    }
}
