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

namespace Espo\Core;
use \Espo\Core\Exceptions\NotFound,
    \Espo\Core\Utils\Util;


class EntryPointManager
{
    private $container;

    private $fileManager;

    protected $data = null;

    protected $cacheFile = 'data/cache/application/entryPoints.php';

    protected $allowedMethods = array(
        'run',
    );

    /**
     * @var array - path to entryPoint files
     */
    private $paths = array(
        'corePath' => 'application/Espo/EntryPoints',
        'modulePath' => 'application/Espo/Modules/{*}/EntryPoints',
        'customPath' => 'custom/Espo/Custom/EntryPoints',
    );


    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;
        $this->fileManager = $container->get('fileManager');
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    public function checkAuthRequired($name)
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new NotFound();
        }
        return $className::$authRequired;
    }

    public function checkNotStrictAuth($name)
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new NotFound();
        }
        return $className::$notStrictAuth;
    }

    public function run($name, $data = array())
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new NotFound();
        }
        $entryPoint = new $className($this->container);

        $entryPoint->run($data);
    }

    protected function getClassName($name)
    {
        $name = Util::normilizeClassName($name);

        if (!isset($this->data)) {
            $this->init();
        }

        $name = ucfirst($name);
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return false;
    }


    protected function init()
    {
        $classParser = $this->getContainer()->get('classParser');
        $classParser->setAllowedMethods($this->allowedMethods);
        $this->data = $classParser->getData($this->paths, $this->cacheFile);
    }

}

