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
namespace Espo\Core;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\File\ClassParser;
use Espo\Core\Utils\Util;

class EntryPointManager
{

    protected $data = null;

    protected $cacheFile = 'data/cache/application/entryPoints.php';

    protected $allowedMethods = array(
        'run',
    );

    private $container;

    private $fileManager;

    /**
     * @var array - path to entryPoint files
     */
    private $paths = array(
        'corePath' => 'application/Espo/EntryPoints',
        'modulePath' => 'application/Espo/Modules/{*}/EntryPoints',
        'customPath' => 'custom/Espo/Custom/EntryPoints',
    );

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->fileManager = $container->get('fileManager');
    }

    public function checkAuthRequired($name)
    {
        $className = $this->getClassName($name);
        if ($className === false) {
            throw new NotFound();
        }
        return $className::$authRequired;
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
        /**
         * @var ClassParser $classParser
         */
        $classParser = $this->getContainer()->get('classParser');
        $classParser->setAllowedMethods($this->allowedMethods);
        $this->data = $classParser->getData($this->paths, $this->cacheFile);
    }

    protected function getContainer()
    {
        return $this->container;
    }

    public function run($name)
    {
        /**
         * @var EntryPoints\Base $entryPoint
         */
        $className = $this->getClassName($name);
        if ($className === false) {
            throw new NotFound();
        }
        $entryPoint = new $className($this->container);
        $entryPoint->run();
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }
}

