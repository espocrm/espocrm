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
namespace Espo\Core\Services;

use Espo\Core\Interfaces\Injectable;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Entities\User;

abstract class Base implements
    Injectable
{

    protected $dependencies = array(
        'config',
        'entityManager',
        'user',
    );

    protected $injections = array();

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    public function getDependencyList()
    {
        return $this->dependencies;
    }

    /**
     * @return EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    /**
     * @return Config
     * @since 1.0
     */
    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    /**
     * @return User
     * @since 1.0
     */
    protected function getUser()
    {
        return $this->getInjection('user');
    }
}

