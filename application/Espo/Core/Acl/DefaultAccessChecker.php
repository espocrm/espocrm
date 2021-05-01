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

namespace Espo\Core\Acl;

use Espo\Entities\User;

use Espo\ORM\Entity;

use Espo\Core\{
    Acl\Table,
    Acl\AccessChecker\ScopeCheckerData,
    Acl\AccessChecker\ScopeChecker,
    AclManager,
    Utils\Config,
};

use DateTime;
use Exception;

/**
 * A default implementation for access checking.
 */
class DefaultAccessChecker implements

    AccessEntityCreateChecker,
    AccessEntityReadChecker,
    AccessEntityEditChecker,
    AccessEntityDeleteChecker,
    AccessEntityStreamChecker
{
    private const ATTR_CREATED_BY_ID = 'createdById';

    private const ATTR_CREATED_AT = 'createdAt';

    private const ATTR_ASSIGNED_USER_ID = 'assignedUserId';

    private const ALLOW_DELETE_OWN_CREATED_PERIOD = '24 hours';

    private $aclManager;

    private $config;

    private $scopeChecker;

    public function __construct(
        AclManager $aclManager,
        Config $config,
        ScopeChecker $scopeChecker
    ) {
        $this->aclManager = $aclManager;
        $this->config = $config;
        $this->scopeChecker = $scopeChecker;
    }

    private function checkEntity(User $user, Entity $entity, ScopeData $data, string $action): bool
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwnChecker(
                function () use ($user, $entity): bool {
                    return $this->aclManager->checkOwnershipOwn($user, $entity);
                }
            )
            ->setInTeamChecker(
                function () use ($user, $entity): bool {
                    return $this->aclManager->checkOwnershipTeam($user, $entity);
                }
            )
            ->build();

        return $this->scopeChecker->check($data, $action, $checkerData);
    }

    private function checkScope(User $user, ScopeData $data, ?string $action = null): bool
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwn(true)
            ->setInTeam(true)
            ->build();

        return $this->scopeChecker->check($data, $action, $checkerData);
    }

    public function check(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data);
    }

    public function checkCreate(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_CREATE);
    }

    public function checkRead(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_READ);
    }

    public function checkEdit(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_EDIT);
    }

    public function checkDelete(User $user, ScopeData $data): bool
    {
        if ($this->checkScope($user, $data, Table::ACTION_DELETE)) {
            return true;
        }

        if ($data->getCreate() === Table::LEVEL_NO) {
            return false;
        }

        if ($this->config->get('aclAllowDeleteCreated')) {
            return true;
        }

        return false;
    }

    public function checkStream(User $user, ScopeData $data): bool
    {
        return $this->checkScope($user, $data, Table::ACTION_STREAM);
    }

    public function checkEntityCreate(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_CREATE);
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_READ);
    }

    public function checkEntityEdit(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_EDIT);
    }

    public function checkEntityStream(User $user, Entity $entity, ScopeData $data): bool
    {
        return $this->checkEntity($user, $entity, $data, Table::ACTION_STREAM);
    }

    public function checkEntityDelete(User $user, Entity $entity, ScopeData $data): bool
    {
        if ($this->checkEntity($user, $entity, $data, Table::ACTION_DELETE)) {
            return true;
        }

        if ($data->getCreate() === Table::LEVEL_NO) {
            return false;
        }

        if (
            !$this->config->get('aclAllowDeleteCreated') ||
            !$entity->has(self::ATTR_CREATED_BY_ID) ||
            $entity->get(self::ATTR_CREATED_BY_ID) !== $user->getId()
        ) {
            return false;
        }

        $isDeletedAllowed = false;

        if (!$entity->has(self::ATTR_ASSIGNED_USER_ID)) {
            $isDeletedAllowed = true;
        }
        else {
            if (!$entity->get(self::ATTR_ASSIGNED_USER_ID)) {
                $isDeletedAllowed = true;
            }
            else if ($entity->get(self::ATTR_ASSIGNED_USER_ID) === $entity->get(self::ATTR_CREATED_BY_ID)) {
                $isDeletedAllowed = true;
            }
        }

        if (!$isDeletedAllowed) {
            return false;
        }

        $createdAt = $entity->get(self::ATTR_CREATED_AT);

        if (!$createdAt) {
            return true;
        }

        $deleteThresholdPeriod =
            $this->config->get('aclAllowDeleteCreatedThresholdPeriod') ??
            self::ALLOW_DELETE_OWN_CREATED_PERIOD;

        if (self::isDateTimeAfterPeriod($createdAt, $deleteThresholdPeriod)) {
            return false;
        }

        return true;
    }

    private static function isDateTimeAfterPeriod(string $value, string $period): bool
    {
        try {
            $dt = new DateTime($value);
        }
        catch (Exception $e) {
            return false;
        }

        $dt->modify($period);

        $dtNow = new DateTime();

        if ($dtNow->format('U') > $dt->format('U')) {
            return true;
        }

        return false;
    }
}
