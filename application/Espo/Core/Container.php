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

class Container
{

    private $data = [];

    public function __construct()
    {
    }

    public function get(string $name)
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    private function load($name)
    {
        $loadMethod = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethod)) {
            $obj = $this->$loadMethod();
            $this->data[$name] = $obj;
        } else {

            try {
                $className = $this->get('metadata')->get(['app', 'loaders', ucfirst($name)]);
            } catch (\Exception $e) {}

            if (!isset($className) || !class_exists($className)) {
                $className = '\Espo\Custom\Core\Loaders\\'.ucfirst($name);
                if (!class_exists($className)) {
                    $className = '\Espo\Core\Loaders\\'.ucfirst($name);
                }
            }

            if (class_exists($className)) {
                 $loadClass = new $className($this);
                 $this->data[$name] = $loadClass->load();
            }
        }

        return null;
    }

    public function getServiceClassName(string $name, string $default)
    {
        $metadata = $this->get('metadata');
        $className = $metadata->get(['app', 'serviceContainer', 'classNames',  $name], $default);
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
            $this
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
            $this
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
            $this
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
            $this->get('config')->get('timeZone')
        );
    }

    protected function loadNumber()
    {
        return new \Espo\Core\Utils\NumberUtil(
            $this->get('config')->get('decimalMark'),
            $this->get('config')->get('thousandSeparator')
        );
    }

    protected function loadServiceFactory()
    {
        return new \Espo\Core\ServiceFactory(
            $this
        );
    }

    protected function loadNotificatorFactory()
    {
        return new \Espo\Core\NotificatorFactory(
            $this
        );
    }

    protected function loadMetadata()
    {
        return new \Espo\Core\Utils\Metadata(
            $this->get('fileManager'),
            $this->get('config')->get('useCache')
        );
    }

    protected function loadLayout()
    {
        return new \Espo\Core\Utils\Layout(
            $this->get('fileManager'),
            $this->get('metadata'),
            $this->get('user')
        );
    }

    protected function loadAclManager()
    {
        $className = $this->getServiceClassName('acl', '\\Espo\\Core\\AclManager');
        return new $className(
            $this->get('container')
        );
    }

    protected function loadInternalAclManager()
    {
        $className = $this->getServiceClassName('acl', '\\Espo\\Core\\AclManager');
        return new $className(
            $this->get('container')
        );
    }

    protected function loadAcl()
    {
        $className = $this->getServiceClassName('acl', '\\Espo\\Core\\Acl');
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

    protected function loadThemeManager()
    {
        return new \Espo\Core\Utils\ThemeManager(
            $this->get('config'),
            $this->get('metadata')
        );
    }

    protected function loadInjectableFactory()
    {
        return new \Espo\Core\InjectableFactory(
            $this
        );
    }

    public function setUser(\Espo\Entities\User $user)
    {
        $this->set('user', $user);
    }
}
