<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\integration\Core;

class Tester
{
    protected $configPath = 'tests/integration/config.php';

    protected $buildedPath = 'build';

    protected $installPath = 'build/test';

    protected $testDataPath = 'tests/integration/testData';

    private $application;

    private $apiClient;

    private $dataLoader;

    protected $params;

    /**
     * Espo username which is used for authentication
     *
     * @var null
     */
    protected $userName = null;

    /**
     * Espo user password which is used for authentication
     *
     * @var null
     */
    protected $password = null;

    protected $portalId = null;

    protected $defaultUserPassword = '1';

    public function __construct(array $params)
    {
        $this->params = $this->normalizeParams($params);
    }

    protected function normalizeParams(array $params)
    {
        $namespaceToRemove = 'tests\\integration\\Espo';
        $classPath = preg_replace('/^'.preg_quote($namespaceToRemove).'\\\\(.+)Test$/', '${1}', $params['className']);

        $params['testDataPath'] = realpath($this->testDataPath);

        if (isset($params['dataFile'])) {
            $params['dataFile'] = realpath($this->testDataPath) . '/' . $params['dataFile'];
            if (!file_exists($params['dataFile'])) {
                die('"dataFile" is not found, path: '.$params['dataFile'].'.');
            }
        } else {
            $params['dataFile'] = realpath($this->testDataPath) . '/' . str_replace('\\', '/', $classPath) . '.php';
        }

        if (isset($params['pathToFiles'])) {
            $params['pathToFiles'] = realpath($this->testDataPath) . '/' . $params['pathToFiles'];
            if (!file_exists($params['pathToFiles'])) {
                die('"pathToFiles" is not found, path: '.$params['pathToFiles'].'.');
            }
        } else {
            $params['pathToFiles'] = realpath($this->testDataPath) . '/' . str_replace('\\', '/', $classPath);
        }

        return $params;
    }

    protected function getParam($name, $returns = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return $returns;
    }

    public function auth($userName, $password = null, $portalId = null)
    {
        $this->userName = $userName;
        $this->password = $password;
        $this->portalId = $portalId;
    }

    public function getApplication($reload = false, $clearCache = true)
    {
        if (!isset($this->application) || $reload)  {

            if ($clearCache) {
                $this->clearCache();
            }

            $this->application = empty($this->portalId) ? new \Espo\Core\Application() : new \Espo\Core\Portal\Application($this->portalId);
            $auth = new \Espo\Core\Utils\Auth($this->application->getContainer());

            if (isset($this->userName)) {
                $this->password = isset($this->password) ? $this->password : $this->defaultUserPassword;
                $auth->login($this->userName, $this->password);
            } else {
                $auth->useNoAuth();
            }
        }

        return $this->application;
    }

    protected function getApiClient()
    {
        if (!isset($this->apiClient)) {
            $this->apiClient = new ApiClient($this->getParam('siteUrl'));
        }

        return $this->apiClient;
    }

    protected function getDataLoader()
    {
        if (!isset($this->dataLoader)) {
            $this->dataLoader = new DataLoader($this->getApplication());
        }

        return $this->dataLoader;
    }

    public function initialize()
    {
        $this->install();
        $this->loadData();
    }

    public function terminate()
    {
        $baseDir = str_replace('/' . $this->installPath, '', getcwd());

        chdir($baseDir);
        set_include_path($baseDir);
    }

    protected function install()
    {
        $mainApplication = new \Espo\Core\Application();
        $fileManager = $mainApplication->getContainer()->get('fileManager');

        $latestEspo = Utils::getLatestBuildedPath($this->buildedPath);

        $configData = include($this->configPath);
        $configData['siteUrl'] = $mainApplication->getContainer()->get('config')->get('siteUrl') . '/' . $this->installPath;
        $this->params['siteUrl'] = $configData['siteUrl'];

        if (!file_exists($this->installPath)) {
            $fileManager->mkdir($this->installPath);
        }

        if (!is_writable($this->installPath)) {
            die("Permission denied for directory [".$this->installPath."].\n");
        }

        //remove and copy Espo files
        Utils::dropTables($configData['database']);
        $fileManager->removeInDir($this->installPath);
        $tt = $fileManager->copy($latestEspo, $this->installPath, true);

        Utils::fixUndefinedVariables();

        chdir($this->installPath);
        set_include_path($this->installPath);

        if (!file_exists('bootstrap.php')) {
            die("Permission denied to copy espo files.\n");
        }

        require_once('install/core/Installer.php');

        $installer = new \Installer();
        $installer->saveData(array(), 'en_US');
        $installer->saveConfig($configData);

        $installer = new \Installer(); //reload installer to get all config data
        $installer->buildDatabase();
        $installer->setSuccess();
    }

    protected function loadData()
    {
        if (!empty($this->params['pathToFiles'])) {
            $this->getDataLoader()->loadFiles($this->params['pathToFiles']);
            $this->clearVars();
            $this->getApplication()->runRebuild();
        }

        if (!empty($this->params['dataFile'])) {
            $this->getDataLoader()->loadData($this->params['dataFile']);
        }
    }

    public function clearCache()
    {
        $this->clearVars();

        $fileManager = new \Espo\Core\Utils\File\Manager();
        return $fileManager->removeInDir('data/cache');
    }

    protected function clearVars()
    {
        $this->dataLoader = null;
        $this->application = null;
        $this->apiClient = null;
    }

    public function sendRequest($method, $action, $data = null)
    {
        $apiClient = $this->getApiClient();
        $apiClient->setUserName($this->userName);
        $apiClient->setPassword(isset($this->password) ? $this->password : $this->defaultUserPassword);
        $apiClient->setPortalId($this->portalId);

        return $apiClient->request($method, $action, $data);
    }

    /**
     * Create a user with roles
     *
     * @param  string|array $userData - If $userData is a string, then it's a userName with default password
     * @param  array  $role
     *
     * @return \Espo\Entities\User
     */
    public function createUser($userData, array $roleData = null, $isPortal = false)
    {
        if (!is_array($userData)) {
            $userData = array(
                'userName' => $userData,
                'lastName' => $userData,
            );
        }

        //create a role
        if (!empty($roleData)) {
            if (!isset($roleData['name'])) {
                $roleData['name'] = $userData['userName'] . 'Role';
            }

            $role = $this->createRole($roleData, $isPortal);

            if (isset($role)) {
                $fieldName = $isPortal ? 'portalRolesIds' : 'rolesIds';
                if (!isset($userData[$fieldName])) {
                    $userData[$fieldName] = array();
                }
                $userData[$fieldName][] = $role->id;
            }
        }

        $application = $this->getApplication();
        $entityManager = $application->getContainer()->get('entityManager');
        $config = $application->getContainer()->get('config');

        if (!isset($userData['password'])) {
            $userData['password'] = $this->defaultUserPassword;
        }

        $passwordHash = new \Espo\Core\Utils\PasswordHash($config);
        $userData['password'] = $passwordHash->hash($userData['password']);

        if ($isPortal) {
            $userData['isPortalUser'] = true;
        }

        $user = $entityManager->getEntity('User');
        $user->set($userData);
        $entityManager->saveEntity($user);

        return $user;
    }

    protected function createRole(array $roleData, $isPortal = false)
    {
        $entityName = $isPortal ? 'PortalRole' : 'Role';

        if (is_array($roleData['data'])) {
            $roleData['data'] = json_encode($roleData['data']);
        }

        if (is_array($roleData['fieldData'])) {
            $roleData['fieldData'] = json_encode($roleData['fieldData']);
        }

        $application = $this->getApplication();
        $entityManager = $application->getContainer()->get('entityManager');

        $role = $entityManager->getEntity($entityName);
        $role->set($roleData);

        $entityManager->saveEntity($role);

        return $role;
    }

    public function normalizePath($path)
    {
        return $this->getParam('testDataPath') . '/' . $path;
    }
}