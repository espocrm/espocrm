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

namespace Espo\Classes\FieldProcessing\User;

use Espo\ORM\Entity;

use Espo\Core\{
    FieldProcessing\Loader,
    FieldProcessing\Loader\Params,
    ORM\EntityManager,
    Acl,
    Acl\Table,
};

use DateTime;
use Exception;

class LastAccessLoader implements Loader
{
    private $entityManager;

    private $acl;

    public function __construct(EntityManager $entityManager, Acl $acl)
    {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
    }

    public function process(Entity $entity, Params $params): void
    {
        $forbiddenFieldList = $this->acl
            ->getScopeForbiddenFieldList($entity->getEntityType(), Table::ACTION_READ);

        if (in_array('lastAccess', $forbiddenFieldList)) {
            return;
        }

        $authToken = $this->entityManager
            ->getRDBRepository('AuthToken')
            ->select(['id', 'lastAccess'])
            ->where([
                'userId' => $entity->getId(),
            ])
            ->order('lastAccess', 'DESC')
            ->findOne();

        $lastAccess = null;

        if ($authToken) {
            $lastAccess = $authToken->get('lastAccess');
        }

        $dt = null;

        if ($lastAccess) {
            try {
                $dt = new DateTime($lastAccess);
            }
            catch (Exception $e) {}
        }

        $where = [
            'userId' => $entity->getId(),
            'isDenied' => false,
        ];

        if ($dt) {
            $where['requestTime>'] = $dt->format('U');
        }

        $authLogRecord = $this->entityManager
            ->getRDBRepository('AuthLogRecord')
            ->select(['id', 'createdAt'])
            ->where($where)
            ->order('requestTime', true)
            ->findOne();

        if ($authLogRecord) {
            $lastAccess = $authLogRecord->get('createdAt');
        }

        $entity->set('lastAccess', $lastAccess);
    }
}
