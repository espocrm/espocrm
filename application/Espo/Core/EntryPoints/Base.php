<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Core\EntryPoints;

use Espo\Core\Acl;
use Espo\Core\Container;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;

abstract class Base
{

    public static $authRequired = true;

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function run();

    protected function getUser()
    {
        return $this->getContainer()->get('user');
    }

    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Acl
     * @since 1.0
     */
    protected function getAcl()
    {
        return $this->getContainer()->get('acl');
    }

    /**
     * @return EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getServiceFactory()
    {
        return $this->getContainer()->get('serviceFactory');
    }

    /**
     * @return Config
     * @since 1.0
     */
    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getMetadata()
    {
        return $this->getContainer()->get('metadata');
    }
}

