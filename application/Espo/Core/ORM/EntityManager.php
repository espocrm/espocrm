<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\ORM;

use Espo\Core\Utils\Util;

class EntityManager extends \Espo\ORM\EntityManager
{
    protected $espoMetadata;

    private $hookManager;

    protected $user;

    protected $container;

    private $repositoryClassNameHash = [];

    private $entityClassNameHash = [];

    private $helper;

    public function setContainer(\Espo\Core\Container $container)
    {
        $this->container = $container;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getEspoMetadata()
    {
        return $this->espoMetadata;
    }

    public function setEspoMetadata($espoMetadata)
    {
        $this->espoMetadata = $espoMetadata;
    }

    public function setHookManager(\Espo\Core\HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
    }

    public function getHookManager()
    {
        return $this->hookManager;
    }

    public function getRepositoryClassName($name)
    {
        if (!array_key_exists($name, $this->repositoryClassNameHash)) {
            $this->repositoryClassNameHash[$name] = $this->getContainer()->get('classFinder')->find('Repositories', $name);
        }
        return $this->repositoryClassNameHash[$name];
    }

    public function getEntityClassName($name)
    {
        if (!array_key_exists($name, $this->entityClassNameHash)) {
            $this->entityClassNameHash[$name] = $this->getContainer()->get('classFinder')->find('Entities', $name);
        }
        return $this->entityClassNameHash[$name];
    }

    public function setHelper(Helper $helper)
    {
        $this->helper = $helper;
    }

    public function getHelper()
    {
        return $this->helper;
    }
}
