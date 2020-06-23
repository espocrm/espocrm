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

namespace Espo\Core;

use Espo\Core\InjectableFactory;
use Espo\Entities\User;

use Espo\Core\Exceptions\Error;

/**
 * DI container for services. Lazy initialization is used. Services are instantiated only once.
 * See https://docs.espocrm.com/development/di/.
 */
class Container
{
    private $data = [];

    private $loaderClassNames;

    protected $configuration;

    public function __construct(string $configurationClassName, array $loaderClassNames = [])
    {
        $this->loaderClassNames = $loaderClassNames;
        $this->configuration = $this->get('injectableFactory')->create($configurationClassName);
    }

    /**
     * Obtain a service object.
     */
    public function get(string $name) : ?object
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    /**
     * Check whether a service can be obtained.
     */
    public function has(string $name) : bool
    {
        if (isset($this->data[$name])) return true;

        $loadMethodName = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethodName)) return true;

        if ($this->configuration->getLoaderClassName($name)) return true;
        if ($this->configuration->getServiceClassName($name)) return true;

        return false;
    }

    /**
     * Set a service object. Must be configured as settable.
     */
    public function set(string $name, object $object)
    {
        if (!$this->configuration->isSettable($name)) {
            throw new Error("Service '{$name}' is not settable.");
        }
        $this->setForced($name, $object);
    }

    protected function setForced(string $name, object $object)
    {
        $this->data[$name] = $object;
    }

    private function load(string $name)
    {
        $loadMethodName = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethodName)) {
            $obj = $this->$loadMethodName();
            $this->data[$name] = $obj;
            return;
        }

        $loaderClassName = $this->loaderClassNames[$name] ?? $this->configuration->getLoaderClassName($name);

        $object = null;

        if ($loaderClassName) {
            $loadClass = $this->get('injectableFactory')->create($loaderClassName);
            $object = $loadClass->load();
            $this->data[$name] = $object;
        } else {
            $className = $this->configuration->getServiceClassName($name);

            if ($className && class_exists($className)) {
                $dependencyList = $this->configuration->getServiceDependencyList($name);
                if (!is_null($dependencyList)) {
                    $dependencyObjectList = [];
                    foreach ($dependencyList as $item) {
                        $dependencyObjectList[] = $this->get($item);
                    }
                    $reflector = new \ReflectionClass($className);
                    $object = $reflector->newInstanceArgs($dependencyObjectList);
                } else {
                    $object = $this->get('injectableFactory')->create($className);
                }

                $this->data[$name] = $object;
            }
        }
    }

    protected function loadContainer()
    {
        return $this;
    }

    protected function loadInjectableFactory()
    {
        return new InjectableFactory($this);
    }

    protected function loadPreferences()
    {
        return $this->get('entityManager')->getEntity('Preferences', $this->get('user')->id);
    }

    protected function loadDateTime()
    {
        return new \Espo\Core\Utils\DateTime(
            $this->get('config')->get('dateFormat'),
            $this->get('config')->get('timeFormat'),
            $this->get('config')->get('timeZone'),
            $this->get('config')->get('language')
        );
    }

    protected function loadLanguage()
    {
        return new \Espo\Core\Utils\Language(
            \Espo\Core\Utils\Language::detectLanguage($this->get('config'), $this->get('preferences')),
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadBaseLanguage()
    {
        return new \Espo\Core\Utils\Language(
            'en_US',
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadDefaultLanguage()
    {
        return new \Espo\Core\Utils\Language(
            \Espo\Core\Utils\Language::detectLanguage($this->get('config')),
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('config')->get('useCache')
        );
    }
}
