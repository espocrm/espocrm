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
namespace Espo\Core\Controllers;

use Espo\Core\Acl;
use Espo\Core\Container;
use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Preferences;
use Espo\Entities\User;

abstract class Base
{

    public static $defaultAction = 'index';

    protected $name;

    private $container;

    private $requestMethod;

    public function __construct(Container $container, $requestMethod = null)
    {
        $this->container = $container;
        if (isset($requestMethod)) {
            $this->setRequestMethod($requestMethod);
        }
        if (empty($this->name)) {
            $name = get_class($this);
            if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
                $name = $matches[1];
            }
            $this->name = $name;
        }
        $this->checkControllerAccess();
    }

    protected function checkControllerAccess()
    {
        return;
    }

    /**
     * @return Container

     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Get request method name (Uppercase)
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return $this->requestMethod;
    }

    protected function setRequestMethod($requestMethod)
    {
        $this->requestMethod = strtoupper($requestMethod);
    }

    /**
     * @return User

     */
    protected function getUser()
    {
        return $this->container->get('user');
    }

    /**
     * @return Acl

     */
    protected function getAcl()
    {
        return $this->container->get('acl');
    }

    /**
     * @return Config

     */
    protected function getConfig()
    {
        return $this->container->get('config');
    }

    /**
     * @return Preferences

     */
    protected function getPreferences()
    {
        return $this->container->get('preferences');
    }

    /**
     * @return Metadata

     */
    protected function getMetadata()
    {
        return $this->container->get('metadata');
    }

    protected function getService($name)
    {
        return $this->getServiceFactory()->create($name);
    }

    /**
     * @return ServiceFactory

     */
    protected function getServiceFactory()
    {
        return $this->container->get('serviceFactory');
    }
}

