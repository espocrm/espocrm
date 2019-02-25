<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\EntryPoints;

use \Espo\Core\Container;

use \Espo\Core\Exceptions\Forbidden;

abstract class Base
{
    private $container;

    public static $authRequired = true;

    public static $notStrictAuth = false;

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getUser()
    {
        return $this->getContainer()->get('user');
    }

    protected function getAcl()
    {
        return $this->getContainer()->get('acl');
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getServiceFactory()
    {
        return $this->getContainer()->get('serviceFactory');
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getMetadata()
    {
        return $this->getContainer()->get('metadata');
    }

    protected function getDateTime()
    {
        return $this->getContainer()->get('dateTime');
    }

    protected function getNumber()
    {
        return $this->getContainer()->get('number');
    }

    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    protected function getLanguage()
    {
        return $this->getContainer()->get('language');
    }

    protected function getClientManager()
    {
        return $this->getContainer()->get('clientManager');
    }

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

}

