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

use Espo\Core\Cron\ScheduledJob;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Crypt;
use Espo\Core\Utils\Database\Schema\Schema;
use Espo\Core\Utils\FieldManager;
use Espo\Core\Utils\File\ClassParser;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;

class Container
{

    private $data = array();

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    public function setUser($user)
    {
        $this->data['user'] = $user;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadSlim()
    {
        return new Utils\Api\Slim();
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadFileManager()
    {
        return new Manager(
            $this->get('config')
        );
    }

    public function get($name)
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        return $this->data[$name];
    }

    private function load($name)
    {
        /**
         * @var Loaders\Base $loadClass
         */
        $loadMethod = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethod)) {
            $obj = $this->$loadMethod();
            $this->data[$name] = $obj;
        } else {
            $className = '\Espo\Custom\Core\Loaders\\' . ucfirst($name);
            if (!class_exists($className)) {
                $className = '\Espo\Core\Loaders\\' . ucfirst($name);
            }
            if (class_exists($className)) {
                $loadClass = new $className($this);
                $this->data[$name] = $loadClass->load();
            }
        }
        return null;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadPreferences()
    {
        /**
         * @var EntityManager $entityManager
         */
        $entityManager = $this->get('entityManager');
        return $entityManager->getEntity('Preferences', $this->get('user')->id);
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadConfig()
    {
        return new Utils\Config(
            new Manager()
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadHookManager()
    {
        return new HookManager(
            $this
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadOutput()
    {
        return new Utils\Api\Output(
            $this->get('slim')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadMailSender()
    {
        $className = $this->getServiceClassName('mailSernder', '\\Espo\\Core\\Mail\\Sender');
        return new $className(
            $this->get('config')
        );
    }

    protected function getServiceClassName($name, $default)
    {
        /**
         * @var Metadata $metadata
         */
        $metadata = $this->get('metadata');
        $className = $metadata->get('app.serviceContainer.classNames.' . $name, $default);
        return $className;
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadDateTime()
    {
        /**
         * @var Config $config
         */
        $config = $this->get('config');
        return new Utils\DateTime(
            $config->get('dateFormat'),
            $config->get('timeFormat'),
            $config->get('timeZone')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadServiceFactory()
    {
        return new ServiceFactory(
            $this
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadSelectManagerFactory()
    {
        return new SelectManagerFactory(
            $this->get('entityManager'),
            $this->get('user'),
            $this->get('acl'),
            $this->get('metadata')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadMetadata()
    {
        return new Metadata(
            $this->get('config'),
            $this->get('fileManager')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadLayout()
    {
        return new Utils\Layout(
            $this->get('fileManager'),
            $this->get('metadata')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadAcl()
    {
        $className = $this->getServiceClassName('acl', '\\Espo\\Core\\Acl');
        return new $className(
            $this->get('user'),
            $this->get('config'),
            $this->get('fileManager'),
            $this->get('metadata')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadSchema()
    {
        return new Schema(
            $this->get('config'),
            $this->get('metadata'),
            $this->get('fileManager'),
            $this->get('entityManager'),
            $this->get('classParser')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadClassParser()
    {
        return new ClassParser(
            $this->get('fileManager'),
            $this->get('config'),
            $this->get('metadata')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadLanguage()
    {
        return new Language(
            $this->get('fileManager'),
            $this->get('config'),
            $this->get('preferences')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadCrypt()
    {
        return new Crypt(
            $this->get('config')
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadScheduledJob()
    {
        return new ScheduledJob(
            $this
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadDataManager()
    {
        return new DataManager(
            $this
        );
    }

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function loadFieldManager()
    {
        return new FieldManager(
            $this->get('metadata'),
            $this->get('language')
        );
    }
}

