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

namespace Espo\Core\Portal;

use Espo\Core\Application\ApplicationParams;
use Espo\Entities\Portal;
use Espo\ORM\EntityManager;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Application as BaseApplication;
use Espo\Core\Container\ContainerBuilder;
use Espo\Core\Portal\Container as PortalContainer;
use Espo\Core\Portal\Container\ContainerConfiguration as PortalContainerConfiguration;
use Espo\Core\Portal\Utils\Config;
use LogicException;

class Application extends BaseApplication
{
    /**
     * @throws Forbidden
     * @throws NotFound
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        ?string $portalId,
        ?ApplicationParams $params = null,
    ) {
        date_default_timezone_set('UTC');

        $this->initContainer($params);
        $this->initPortal($portalId);
        $this->initAutoloads();
        $this->initPreloads();
    }

    protected function initContainer(?ApplicationParams $params): void
    {
        $container = (new ContainerBuilder())
            ->withConfigClassName(Config::class)
            ->withContainerClassName(PortalContainer::class)
            ->withContainerConfigurationClassName(PortalContainerConfiguration::class)
            ->withParams($params)
            ->build();

        if (!$container instanceof PortalContainer) {
            throw new LogicException("Wrong container created.");
        }

        $this->container = $container;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    protected function initPortal(?string $portalId): void
    {
        if (!$portalId) {
            throw new LogicException("Portal ID was not passed to Portal\Application.");
        }

        $entityManager = $this->container->getByClass(EntityManager::class);

        $portal = $entityManager->getEntityById(Portal::ENTITY_TYPE, $portalId);

        if (!$portal) {
            $portal = $entityManager
                ->getRDBRepositoryByClass(Portal::class)
                ->where(['customId' => $portalId])
                ->findOne();
        }

        if (!$portal) {
            throw new NotFound("Portal $portalId not found.");
        }

        if (!$portal->isActive()) {
            throw new Forbidden("Portal $portalId is not active.");
        }

        $container = $this->container;

        if (!$container instanceof PortalContainer) {
            throw new LogicException();
        }

        $container->setPortal($portal);
    }

    protected function initPreloads(): void
    {
        parent::initPreloads();

        foreach ($this->getMetadata()->get(['app', 'portalContainerServices']) ?? [] as $name => $defs) {
            if ($defs['preload'] ?? false) {
                $this->container->get($name);
            }
        }
    }
}
