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
namespace Espo\Core\Hooks;

use Espo\Core\Acl;
use Espo\Core\Interfaces\Injectable;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;

class Base implements
    Injectable
{

    public static $order = 9;

    protected $entityName;

    protected $dependencies = array(
        'entityManager',
        'config',
        'metadata',
        'acl',
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

    public function getDependencyList()
    {
        return $this->dependencies;
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    /**
     * @return User
     * @since 1.0
     */
    protected function getUser()
    {
        return $this->injections['user'];
    }

    /**
     * @return Acl
     * @since 1.0
     */
    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    /**
     * @return Config
     * @since 1.0
     */
    protected function getConfig()
    {
        return $this->injections['config'];
    }

    /**
     * @return Metadata
     * @since 1.0
     */
    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    /**
     * @return \Espo\Core\ORM\Repositories\RDB
     * @since 1.0
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityName);
    }

    /**
     * @return EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }
}

