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

namespace Espo\Classes\AclPortal\Note;

use Espo\Entities\User;

use Espo\ORM\Entity;

use Espo\Core\{
    Portal\AclManager,
    Acl\ScopeData,
    Acl\AccessEntityCREDChecker,
    Portal\Acl\DefaultAccessChecker,
    Portal\Acl\Traits\DefaultAccessCheckerDependency,
    ORM\EntityManager,
    Utils\Config,
};

use DateTime;
use Exception;

class AccessChecker implements AccessEntityCREDChecker
{
    use DefaultAccessCheckerDependency;

    private const EDIT_PERIOD = '7 days';

    private const DELETE_PERIOD = '1 month';

    private $defaultAccessChecker;

    private $aclManager;

    private $entityManager;

    private $config;

    public function __construct(
        DefaultAccessChecker $defaultAccessChecker,
        AclManager $aclManager,
        EntityManager $entityManager,
        Config $config
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
        $this->aclManager = $aclManager;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');

        if (!$parentId || !$parentType) {
            return $this->defaultAccessChecker->checkEntityCreate($user, $entity, $data);
        }

        $parent = $this->entityManager->getEntity($parentType, $parentId);

        if ($parent && $this->aclManager->checkEntityStream($user, $parent)) {
            return true;
        }

        return $this->defaultAccessChecker->checkEntityCreate($user, $entity, $data);
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($entity->get('type') !== 'Post') {
            return false;
        }

        if ($entity->get('type') === 'Post' && $entity->get('targetType')) {
            return false;
        }

        if (!$entity->get('parentId') || !$entity->get('parentType')) {
            return false;
        }

        $parent = $this->entityManager->getEntity($entity->get('parentType'), $entity->get('parentId'));

        if ($parent) {
            if ($this->aclManager->checkEntityStream($user, $parent)) {
                return true;
            }
        }

        return false;
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        if (!$this->defaultAccessChecker->checkEntityEdit($user, $entity, $data)) {
            return false;
        }

        if (!$this->aclManager->checkOwnershipOwn($user, $entity)) {
            return true;
        }

        $createdAt = $entity->get('createdAt');

        if (!$createdAt) {
            return true;
        }

        $noteEditThresholdPeriod =
            '-' .  $this->config->get('noteEditThresholdPeriod', self::EDIT_PERIOD);

        $dt = new DateTime();

        $dt->modify($noteEditThresholdPeriod);

        try {
            if ($dt->format('U') > (new DateTime($createdAt))->format('U')) {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        if (!$this->defaultAccessChecker->checkEntityDelete($user, $entity, $data)) {
            return false;
        }

        if (!$this->aclManager->checkOwnershipOwn($user, $entity)) {
            return true;
        }

        $createdAt = $entity->get('createdAt');

        if (!$createdAt) {
            return true;
        }

        $deleteThresholdPeriod =
            '-' . $this->config->get('noteDeleteThresholdPeriod', self::DELETE_PERIOD);

        $dt = new DateTime();

        $dt->modify($deleteThresholdPeriod);

        try {
            if ($dt->format('U') > (new DateTime($createdAt))->format('U')) {
                return false;
            }
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }
}
