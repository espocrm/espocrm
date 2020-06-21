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

/**
 * DI container for services. Lazy initialization is used. Services are instantiated only once.
 * See https://docs.espocrm.com/development/di/.
 */
class Container
{
    private $data = [];

    public function __construct()
    {
    }

    /**
     * Obtain a service.
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

        if ($this->getLoaderClassName($name)) return true;

        if ($this->getServiceClassName($name)) return true;

        return false;
    }

    /**
     * Inject a user after authentication.
     */
    public function setUser(User $user)
    {
        $this->set('user', $user);
    }

    protected function set(string $name, object $obj)
    {
        $this->data[$name] = $obj;
    }

    private function load(string $name)
    {
        $loadMethodName = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethodName)) {
            $obj = $this->$loadMethodName();
            $this->data[$name] = $obj;
            return;
        }

        $loaderClassName = $this->getLoaderClassName($name);

        $object = null;

        if ($loaderClassName) {
            $loadClass = $this->get('injectableFactory')->create($loaderClassName);
            $object = $loadClass->load();
            $this->data[$name] = $object;
        } else {
            $className = $this->getServiceClassName($name);

            if ($className && class_exists($className)) {
                $dependencyList = $this->getServiceDependencyList($name);
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

    protected function getLoaderClassName(string $name) : ?string
    {
        $metadata = $this->get('metadata');

        try {
            $className = $metadata->get(['app', 'containerServices', $name, 'loaderClassName']);
            if (!$className) {
                // deprecated
                $className = $metadata->get(['app', 'loaders', ucfirst($name)]);
            }
        } catch (\Exception $e) {}

        if (!isset($className) || !class_exists($className)) {
            $className = '\Espo\Custom\Core\Loaders\\'.ucfirst($name);
            if (!class_exists($className)) {
                $className = '\Espo\Core\Loaders\\'.ucfirst($name);
            }
        }

        if (!class_exists($className)) {
            return null;
        }

        return $className;
    }

    protected function getServiceDependencyList(string $name) : ?array
    {
        return $this->get('metadata')->get(['app', 'containerServices', $name, 'dependencyList']) ?? null;
    }

    protected function getServiceClassName(string $name) : ?string
    {
        $metadata = $this->get('metadata');

        $className =
            $metadata->get(['app', 'containerServices',  $name, 'className']) ??
            $metadata->get(['app', 'serviceContainer', 'classNames',  $name]) ?? // deprecated
            null;

        return $className;
    }

    protected function loadContainer()
    {
        return $this;
    }

    protected function loadSlim()
    {
        return new \Espo\Core\Utils\Api\Slim();
    }

    protected function loadFileStorageManager()
    {
        return new \Espo\Core\FileStorage\Manager(
            $this->get('metadata')->get(['app', 'fileStorage', 'implementationClassNameMap']),
            $this->get('injectableFactory')
        );
    }

    protected function loadLog()
    {
        $config = $this->get('config');

        $path = $config->get('logger.path', 'data/logs/espo.log');
        $rotation = $config->get('logger.rotation', true);

        $log = new \Espo\Core\Utils\Log('Espo');
        $levelCode = $log::toMonologLevel($config->get('logger.level', 'WARNING'));

        if ($rotation) {
            $maxFileNumber = $config->get('logger.maxFileNumber', 30);
            $handler = new \Espo\Core\Utils\Log\Monolog\Handler\RotatingFileHandler($path, $maxFileNumber, $levelCode);
        } else {
            $handler = new \Espo\Core\Utils\Log\Monolog\Handler\StreamHandler($path, $levelCode);
        }
        $log->pushHandler($handler);

        $errorHandler = new \Monolog\ErrorHandler($log);
        $errorHandler->registerExceptionHandler(null, false);
        $errorHandler->registerErrorHandler([], false);

        return $log;
    }

    protected function loadFileManager()
    {
        return new \Espo\Core\Utils\File\Manager(
            $this->get('config')
        );
    }

    protected function loadControllerManager()
    {
        return new \Espo\Core\ControllerManager(
            $this->get('injectableFactory'),
            $this->get('classFinder'),
            $this->get('metadata') // TODO remove
        );
    }

    protected function loadPreferences()
    {
        return $this->get('entityManager')->getEntity('Preferences', $this->get('user')->id);
    }

    protected function loadConfig()
    {
        return new \Espo\Core\Utils\Config(
            new \Espo\Core\Utils\File\Manager()
        );
    }

    protected function loadHookManager()
    {
        return new \Espo\Core\HookManager(
            $this->get('injectableFactory'),
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('config'),
        );
    }

    protected function loadOutput()
    {
        return new \Espo\Core\Utils\Api\Output(
            $this->get('slim')
        );
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

    protected function loadNumber()
    {
        return new \Espo\Core\Utils\NumberUtil(
            $this->get('config')->get('decimalMark'),
            $this->get('config')->get('thousandSeparator')
        );
    }

    protected function loadMetadata()
    {
        return new \Espo\Core\Utils\Metadata(
            $this->get('fileManager'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadAclManager()
    {
        $className = $this->getServiceClassName('aclManager') ?? 'Espo\\Core\\AclManager';
        return new $className(
            $this->get('container')
        );
    }

    protected function loadInternalAclManager()
    {
        return $this->loadAclManager();
    }

    protected function loadAcl()
    {
        $className = $this->getServiceClassName('acl') ?? 'Espo\\Core\\Acl';
        return new $className(
            $this->get('aclManager'),
            $this->get('user')
        );
    }

    protected function loadSchema()
    {
        return new \Espo\Core\Utils\Database\Schema\Schema(
            $this->get('config'),
            $this->get('metadata'),
            $this->get('fileManager'),
            $this->get('entityManager'),
            $this->get('classParser'),
            $this->get('ormMetadata')
        );
    }

    protected function loadOrmMetadata()
    {
        return new \Espo\Core\Utils\Metadata\OrmMetadata(
            $this->get('metadata'),
            $this->get('fileManager'),
            $this->get('config')
        );
    }

    protected function loadClassParser()
    {
        return new \Espo\Core\Utils\File\ClassParser(
            $this->get('fileManager'),
            $this->get('config'),
            $this->get('metadata')
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

    protected function loadCrypt()
    {
        return new \Espo\Core\Utils\Crypt(
            $this->get('config')
        );
    }

    protected function loadScheduledJob()
    {
        return new \Espo\Core\Utils\ScheduledJob(
            $this
        );
    }

    protected function loadDataManager()
    {
        return new \Espo\Core\DataManager(
            $this
        );
    }

    protected function loadFieldManager()
    {
        return new \Espo\Core\Utils\FieldManager(
            $this
        );
    }

    protected function loadFieldManagerUtil()
    {
        return new \Espo\Core\Utils\FieldManagerUtil(
            $this->get('metadata')
        );
    }

    protected function loadInjectableFactory()
    {
        return new InjectableFactory($this);
    }
}
