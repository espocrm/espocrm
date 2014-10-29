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
namespace Espo\Core\Jobs;

use Espo\Core\Container;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;

abstract class Base
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    abstract public function run();

    /**
     * @return EntityManager

     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    /**
     * @return Container

     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return ServiceFactory

     */
    protected function getServiceFactory()
    {
        return $this->getContainer()->get('serviceFactory');
    }

    /**
     * @return Config

     */
    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    /**
     * @return Metadata

     */
    protected function getMetadata()
    {
        return $this->getContainer()->get('metadata');
    }

    /**
     * @return User

     */
    protected function getUser()
    {
        return $this->getContainer()->get('user');
    }
}

