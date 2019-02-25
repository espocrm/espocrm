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
use \Espo\Core\Exceptions\Error;

use \Espo\Core\Utils\Util;

class ServiceFactory
{
    private $container;

    protected $cacheFile = 'data/cache/application/services.php';

    /**
     * @var array - path to Service files
     */
    protected $paths = [
        'corePath' => 'application/Espo/Services',
        'modulePath' => 'application/Espo/Modules/{*}/Services',
        'customPath' => 'custom/Espo/Custom/Services',
    ];

    protected $data;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getFileManager()
    {
        return $this->container->get('fileManager');
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function init()
    {
        $classParser = $this->getContainer()->get('classParser');
        $classParser->setAllowedMethods(null);
        $this->data = $classParser->getData($this->paths, $this->cacheFile);
    }

    protected function getClassName($name)
    {
        if (!isset($this->data)) {
            $this->init();
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return false;
    }

    public function checkExists($name) {
        $className = $this->getClassName($name);
        if (!empty($className)) {
            return true;
        }
    }

    public function create($name)
    {
        $className = $this->getClassName($name);
        if (empty($className)) {
            throw new Error("Service '{$name}' was not found.");
        }
        return $this->createByClassName($className);
    }

    protected function createByClassName($className)
    {
        if (class_exists($className)) {
            $service = new $className();
            $dependencyList = $service->getDependencyList();
            foreach ($dependencyList as $name) {
                $service->inject($name, $this->container->get($name));
            }
            if (method_exists($service, 'prepare')) {
                $service->prepare();
            }
            return $service;
        }
        throw new Error("Class '$className' does not exist.");
    }
}
