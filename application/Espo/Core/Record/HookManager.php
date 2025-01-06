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

namespace Espo\Core\Record;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\CreateHook;
use Espo\Core\Record\Hook\DeleteHook;
use Espo\Core\Record\Hook\LinkHook;
use Espo\Core\Record\Hook\ReadHook;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Record\Hook\UnlinkHook;
use Espo\Core\Record\Hook\UpdateHook;
use Espo\Core\Record\Hook\Provider;
use Espo\Core\Record\Hook\Type;
use Espo\ORM\Entity;

class HookManager
{
    public function __construct(private Provider $provider)
    {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processEarlyBeforeCreate(Entity $entity, CreateParams $params): void
    {
        foreach ($this->getEarlyBeforeCreateHookList($entity->getEntityType()) as $hook) {
            if ($hook instanceof SaveHook) {
                $hook->process($entity);

                continue;
            }

            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processBeforeCreate(Entity $entity, CreateParams $params): void
    {
        foreach ($this->getBeforeCreateHookList($entity->getEntityType()) as $hook) {
            if ($hook instanceof SaveHook) {
                $hook->process($entity);

                continue;
            }

            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processAfterCreate(Entity $entity, CreateParams $params): void
    {
        foreach ($this->getAfterCreateHookList($entity->getEntityType()) as $hook) {
            if ($hook instanceof SaveHook) {
                $hook->process($entity);

                continue;
            }

            $hook->process($entity, $params);
        }
    }

    public function processBeforeRead(Entity $entity, ReadParams $params): void
    {
        foreach ($this->getBeforeReadHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processEarlyBeforeUpdate(Entity $entity, UpdateParams $params): void
    {
        foreach ($this->getEarlyBeforeUpdateHookList($entity->getEntityType()) as $hook) {
            if ($hook instanceof SaveHook) {
                $hook->process($entity);

                continue;
            }

            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processBeforeUpdate(Entity $entity, UpdateParams $params): void
    {
        foreach ($this->getBeforeUpdateHookList($entity->getEntityType()) as $hook) {
            if ($hook instanceof SaveHook) {
                $hook->process($entity);

                continue;
            }

            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processAfterUpdate(Entity $entity, UpdateParams $params): void
    {
        foreach ($this->getAfterUpdateHookList($entity->getEntityType()) as $hook) {
            if ($hook instanceof SaveHook) {
                $hook->process($entity);

                continue;
            }

            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processBeforeDelete(Entity $entity, DeleteParams $params): void
    {
        foreach ($this->getBeforeDeleteHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $params);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Conflict
     */
    public function processAfterDelete(Entity $entity, DeleteParams $params): void
    {
        foreach ($this->getAfterDeleteHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $params);
        }
    }

    public function processBeforeLink(Entity $entity, string $link, Entity $foreignEntity): void
    {
        foreach ($this->getBeforeLinkHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $link, $foreignEntity);
        }
    }

    public function processBeforeUnlink(Entity $entity, string $link, Entity $foreignEntity): void
    {
        foreach ($this->getBeforeUnlinkHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $link, $foreignEntity);
        }
    }

    public function processAfterLink(Entity $entity, string $link, Entity $foreignEntity): void
    {
        foreach ($this->getAfterLinkHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $link, $foreignEntity);
        }
    }

    public function processAfterUnlink(Entity $entity, string $link, Entity $foreignEntity): void
    {
        foreach ($this->getAfterUnlinkHookList($entity->getEntityType()) as $hook) {
            $hook->process($entity, $link, $foreignEntity);
        }
    }

    /**
     * @return ReadHook<Entity>[]
     */
    private function getBeforeReadHookList(string $entityType): array
    {
        /** @var ReadHook<Entity>[] */
        return $this->provider->getList($entityType, Type::BEFORE_READ);
    }

    /**
     * @return (CreateHook<Entity>|SaveHook<Entity>)[]
     */
    private function getEarlyBeforeCreateHookList(string $entityType): array
    {
        /** @var (CreateHook<Entity>|SaveHook<Entity>)[] */
        return $this->provider->getList($entityType, Type::EARLY_BEFORE_CREATE);
    }

    /**
     * @return (CreateHook<Entity>|SaveHook<Entity>)[]
     */
    private function getBeforeCreateHookList(string $entityType): array
    {
        /** @var (CreateHook<Entity>|SaveHook<Entity>)[] */
        return $this->provider->getList($entityType, Type::BEFORE_CREATE);
    }

    /**
     * @return (CreateHook<Entity>|SaveHook<Entity>)[]
     */
    private function getAfterCreateHookList(string $entityType): array
    {
        /** @var (CreateHook<Entity>|SaveHook<Entity>)[] */
        return $this->provider->getList($entityType, Type::AFTER_CREATE);
    }

    /**
     * @return (UpdateHook<Entity>|SaveHook<Entity>)[]
     */
    private function getEarlyBeforeUpdateHookList(string $entityType): array
    {
        /** @var (UpdateHook<Entity>|SaveHook<Entity>)[] */
        return $this->provider->getList($entityType, Type::EARLY_BEFORE_UPDATE);
    }

    /**
     * @return (UpdateHook<Entity>|SaveHook<Entity>)[]
     */
    private function getBeforeUpdateHookList(string $entityType): array
    {
        /** @var (UpdateHook<Entity>|SaveHook<Entity>)[] */
        return $this->provider->getList($entityType, Type::BEFORE_UPDATE);
    }

    /**
     * @return (UpdateHook<Entity>|SaveHook<Entity>)[]
     */
    private function getAfterUpdateHookList(string $entityType): array
    {
        /** @var (UpdateHook<Entity>|SaveHook<Entity>)[] */
        return $this->provider->getList($entityType, Type::AFTER_UPDATE);
    }

    /**
     * @return DeleteHook<Entity>[]
     */
    private function getBeforeDeleteHookList(string $entityType): array
    {
        /** @var DeleteHook<Entity>[] */
        return $this->provider->getList($entityType, Type::BEFORE_DELETE);
    }

    /**
     * @return DeleteHook<Entity>[]
     */
    private function getAfterDeleteHookList(string $entityType): array
    {
        /** @var DeleteHook<Entity>[] */
        return $this->provider->getList($entityType, Type::AFTER_DELETE);
    }

    /**
     * @return LinkHook<Entity>[]
     */
    private function getBeforeLinkHookList(string $entityType): array
    {
        /** @var LinkHook<Entity>[] */
        return $this->provider->getList($entityType, Type::BEFORE_LINK);
    }

    /**
     * @return UnlinkHook<Entity>[]
     */
    private function getBeforeUnlinkHookList(string $entityType): array
    {
        /** @var UnlinkHook<Entity>[] */
        return $this->provider->getList($entityType, Type::BEFORE_UNLINK);
    }

    /**
     * @return LinkHook<Entity>[]
     */
    private function getAfterLinkHookList(string $entityType): array
    {
        /** @var LinkHook<Entity>[] */
        return $this->provider->getList($entityType, Type::AFTER_LINK);
    }

    /**
     * @return UnlinkHook<Entity>[]
     */
    private function getAfterUnlinkHookList(string $entityType): array
    {
        /** @var UnlinkHook<Entity>[] */
        return $this->provider->getList($entityType, Type::AFTER_UNLINK);
    }
}
