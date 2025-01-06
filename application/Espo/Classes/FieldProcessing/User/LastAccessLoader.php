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

namespace Espo\Classes\FieldProcessing\User;

use Espo\Core\Name\Field;
use Espo\Entities\AuthLogRecord;
use Espo\Entities\AuthToken;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\Core\Acl;
use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\ORM\EntityManager;

use DateTime;
use Espo\ORM\Name\Attribute;
use Exception;

/**
 * @implements Loader<User>
 * @noinspection PhpUnused
 */
class LastAccessLoader implements Loader
{
    private EntityManager $entityManager;
    private Acl $acl;

    public function __construct(EntityManager $entityManager, Acl $acl)
    {
        $this->entityManager = $entityManager;
        $this->acl = $acl;
    }

    public function process(Entity $entity, Params $params): void
    {
        if (!$this->acl->checkField($entity->getEntityType(), 'lastAccess')) {
            return;
        }

        $authToken = $this->entityManager
            ->getRDBRepository(AuthToken::ENTITY_TYPE)
            ->select([Attribute::ID, 'lastAccess'])
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
            } catch (Exception) {}
        }

        $where = [
            'userId' => $entity->getId(),
            'isDenied' => false,
        ];

        if ($dt) {
            $where['requestTime>'] = $dt->format('U');
        }

        $authLogRecord = $this->entityManager
            ->getRDBRepository(AuthLogRecord::ENTITY_TYPE)
            ->select([Attribute::ID, Field::CREATED_AT])
            ->where($where)
            ->order('requestTime', true)
            ->findOne();

        if ($authLogRecord) {
            $lastAccess = $authLogRecord->get(Field::CREATED_AT);
        }

        $entity->set('lastAccess', $lastAccess);
    }
}
