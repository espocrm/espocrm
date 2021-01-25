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

namespace Espo\Services;

use Espo\Core\{
    ServiceFactory,
    Utils\Metadata,
    Utils\Util,
    ORM\EntityManager,
    ORM\Entity,
};

use Espo\Entities\User;

class LastViewed
{
    protected $serviceFactory;
    protected $metadata;
    protected $entityManager;
    protected $user;

    public function __construct(
        ServiceFactory $serviceFactory,
        Metadata $metadata,
        EntityManager $entityManager,
        User $user
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function getList($params) : object
    {
        $repository = $this->entityManager->getRepository('ActionHistoryRecord');

        $actionHistoryRecordService = $this->serviceFactory->create('ActionHistoryRecord');

        $scopes = $this->metadata->get('scopes');

        $targetTypeList = array_filter(array_keys($scopes), function ($item) use ($scopes) {
            return !empty($scopes[$item]['object']) || !empty($scopes[$item]['lastViewed']);
        });

        $offset = $params['offset'];
        $maxSize = $params['maxSize'];

        $selectParams = [
            'whereClause' => [
                'userId' => $this->user->id,
                'action' => 'read',
                'targetType' => $targetTypeList
            ],
            'orderBy' => [[4, true]],
            'select' => ['targetId', 'targetType', 'MAX:number', ['MAX:createdAt', 'createdAt']],
            'groupBy' => ['targetId', 'targetType']
        ];

        $collection = $repository->limit($offset, $params['maxSize'] + 1)->find($selectParams);

        foreach ($collection as $i => $entity) {
            $actionHistoryRecordService->loadParentNameFields($entity);
            $entity->set('id', Util::generateId());
        }

        if ($maxSize && count($collection) > $maxSize) {
            $total = -1;
            unset($collection[count($collection) - 1]);
        } else {
            $total = -2;
        }

        return (object) [
            'total' => $total,
            'collection' => $collection
        ];
    }
}
