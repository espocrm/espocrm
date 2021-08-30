<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Core;

use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;

use Espo\Core\Application;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\ApplicationRunners\Rebuild;
use Espo\Core\Utils\PasswordHash;

use Espo\Entities\User;

use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Response;

class Tester
{
    protected $configPath = 'tests/integration/config.php';

    protected $buildedPath = 'build';

    protected $installPath = 'build/test';

    protected $testDataPath = 'tests/integration/testData';

    protected $packageJsonPath = 'package.json';

    private $application;

    private $apiClient;

    private $dataLoader;

    protected $params;

    /**
     * Username used for authentication.
     */
    protected $userName = null;

    /**
     * Password used for authentication.
     */
    protected $password = null;

    protected $portalId = null;

    protected $authenticationMethod = null;

    protected $defaultUserPassword = '1';

    public function __construct(array $params)
    {
        $this->params = $this->normalizeParams($params);
    }

    protected function normalizeParams(array $params)
    {
        $namespaceToRemove = 'tests\\integration\\Espo';

        $classPath = preg_replace(
            '/^' . preg_quote($namespaceToRemove) . '\\\\(.+)Test$/',
            '${1}',
            $params['className']
        );

        $params['testDataPath'] = realpath($this->testDataPath);

        if (isset($params['dataFile'])) {
            $params['dataFile'] = realpath($this->testDataPath) . '/' . $params['dataFile'];

            if (!file_exists($params['dataFile'])) {
                die('"dataFile" is not found, path: '.$params['dataFile'].'.');
            }
        } else {
            $params['dataFile'] = realpath($this->testDataPath) . '/' .
                str_replace('\\', '/', $classPath) . '.php';
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

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    protected function getTestConfigData()
    {
        if (!file_exists($this->configPath)) {
            die('Config for integration tests ['. $this->configPath .'] is not found');
        }

        $data = include($this->configPath);

        $packageData = json_decode(file_get_contents($this->packageJsonPath));

        $version = $packageData->version;

        $data['version'] = $version;

        return $data;
    }

    protected function saveTestConfigData($optionName, $data)
    {
        $configData = $this->getTestConfigData();

        if (array_key_exists($optionName, $configData) && $configData[$optionName] === $data) {
            return true;
        }

        $configData[$optionName] = $data;

        $fileManager = new \Espo\Core\Utils\File\Manager();

        return $fileManager->putPhpContents($this->configPath, $configData);
    }

    public function auth(
        $userName,
        $password = null,
        $portalId = null,
        $authenticationMethod = null,
        $request = null
    ): void {

        $this->userName = $userName;
        $this->password = $password;
        $this->portalId = $portalId;
        $this->authenticationMethod = $authenticationMethod;
        $this->request = $request;
    }

    public function getApplication(
        bool $reload = false,
        bool $clearCache = true,
        ?string $portalId = null
    ): Application {

        $portalId = $portalId ?? $this->portalId ?? null;

        if (!isset($this->application) || $reload)  {

            if ($clearCache) {
                $this->clearCache();
            }

            $this->application = !$portalId ? new Application() : new PortalApplication($portalId);

            $auth = $this->application->getContainer()
                ->get('injectableFactory')
                ->createWith(Authentication::class, [
                    'allowAnyAccess' => false,
                ]);

            $request = $this->request ?? new RequestWrapper(
                (new RequestFactory())->createRequest('POST', '')
            );

            $response = new ResponseWrapper(new Response());

            if (isset($this->userName) || $this->authenticationMethod) {
                $this->password = isset($this->password) ? $this->password : $this->defaultUserPassword;

                $authenticationData = AuthenticationData::create()
                    ->withUsername($this->userName)
                    ->withPassword($this->password)
                    ->withMethod($this->authenticationMethod);

                $auth->login($authenticationData, $request, $response);
            }
            else {
                $this->application->setupSystemUser();
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

        if ($this->getParam('fullReset')) {
            $this->saveTestConfigData('lastModifiedTime', null);
        }
    }

    protected function install()
    {
        $fileManager = new \Espo\Core\Utils\File\Manager();

        $configData = $this->getTestConfigData();

        $latestEspoDir = Utils::getLatestBuildedPath($this->buildedPath);

        if (empty($latestEspoDir)) {
            die("EspoCRM build is not found. Please run \"grunt\" in your terminal.\n");
        }

        if (!isset($configData['siteUrl'])) {
            $mainConfigData = include('data/config.php');

            if (isset($mainConfigData['siteUrl'])) {
                $configData['siteUrl'] = $mainConfigData['siteUrl'] . '/' . $this->installPath;
            }
        }

        if (isset($configData['siteUrl'])) {
            $this->params['siteUrl'] = $configData['siteUrl'];
        }

        if (!file_exists($this->installPath)) {
            $fileManager->mkdir($this->installPath);
        }

        if (!is_writable($this->installPath)) {
            die("Permission denied for directory [".$this->installPath."].\n");
        }

        //reset DB, remove and copy Espo files
        Utils::checkCreateDatabase($configData['database']);
        $this->reset($fileManager, $latestEspoDir);

        Utils::fixUndefinedVariables();

        chdir($this->installPath);
        set_include_path($this->installPath);

        if (!file_exists('bootstrap.php')) {
            die("Permission denied to copy espo files.\n");
        }

        require_once('install/core/Installer.php');

        $installer = new \Installer();

        $installer->saveData(array_merge($configData, [
            'language' => 'en_US'
        ]));

        $installer->saveConfig($configData);

        $installer = new \Installer(); //reload installer to get all config data
        $installer->buildDatabase();
        $installer->setSuccess();
    }

    protected function reset($fileManager, $latestEspoDir)
    {
        $configData = $this->getTestConfigData();

        $fullReset = true;

        if (file_exists($latestEspoDir . '/application')) {
            $modifiedTime = filemtime($latestEspoDir . '/application');

            if (
                !$this->getParam('fullReset') &&
                isset($configData['lastModifiedTime']) &&
                $configData['lastModifiedTime'] == $modifiedTime
            ) {
                $fullReset = false;
            }

            $this->saveTestConfigData('lastModifiedTime', $modifiedTime);
        }

        if ($fullReset) {
            Utils::dropTables($configData['database']);

            if ($this->isShellEnabled()) {
                shell_exec('rm -rf "' . $this->installPath . '"');
                shell_exec('cp -r "' . $latestEspoDir . '" "' . $this->installPath . '"');
            } else {
                $fileManager->removeInDir($this->installPath);
                $fileManager->copy($latestEspoDir, $this->installPath, true);
            }

            return true;
        }

        //Utils::truncateTables($configData['database']);
        Utils::dropTables($configData['database']);
        $fileManager->removeInDir($this->installPath . '/data');
        $fileManager->removeInDir($this->installPath . '/custom/Espo/Custom');
        $fileManager->removeInDir($this->installPath . '/client/custom');
        $fileManager->unlink($this->installPath . '/install/config.php');

        return true;
    }

    protected function cleanDirectory($fileManager, $path, array $ignoreList = [])
    {
        if (!file_exists($path)) {
            return true;
        }

        $list = $fileManager->getFileList($path);

        foreach ($list as $itemName) {
            if (in_array($itemName, $ignoreList)) continue;

            $itemPath = $path . '/' . $itemName;

            if (is_file($itemPath)) {
                $fileManager->unlink($itemPath);
            } else {
                $fileManager->removeInDir($itemPath, true);
            }
        }

        return true;
    }

    protected function loadData()
    {
        $applyChanges = false;

        if (!empty($this->params['pathToFiles']) && file_exists($this->params['pathToFiles'])) {
            $result = $this->getDataLoader()->loadFiles($this->params['pathToFiles']);
            $this->getApplication(true, true)->run(Rebuild::class);
        }

        if (!empty($this->params['dataFile'])) {
            $this->getDataLoader()->loadData($this->params['dataFile']);
            $applyChanges = true;
        }

        if (!empty($this->params['initData'])) {
            $this->getDataLoader()->setData($this->params['initData']);
            $applyChanges = true;
        }

        if ($applyChanges) {
            $this->getApplication(true, true)->run(Rebuild::class);
        }
    }

    public function setData(array $data)
    {
        $this->getDataLoader()->setData($data);
        $this->getApplication(true, true)->run(Rebuild::class);
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
     * Create a user with roles.
     *
     * @param string|array $userData If $userData is a string, then it's a userName with default password.
     */
    public function createUser($userData, ?array $roleData = null, $isPortal = false): User
    {
        if (!is_array($userData)) {
            $userData = [
                'userName' => $userData,
                'lastName' => $userData,
            ];
        }

        if (!empty($roleData)) {
            if (!isset($roleData['name'])) {
                $roleData['name'] = $userData['userName'] . 'Role';
            }

            $role = $this->createRole($roleData, $isPortal);

            if (isset($role)) {
                $fieldName = $isPortal ? 'portalRolesIds' : 'rolesIds';

                if (!isset($userData[$fieldName])) {
                    $userData[$fieldName] = [];
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

        $passwordHash = new PasswordHash($config);

        $userData['password'] = $passwordHash->hash($userData['password']);

        if ($isPortal) {
            $userData['type'] = 'portal';
        }

        $user = $entityManager->getEntity('User');

        $user->set($userData);

        $entityManager->saveEntity($user);

        return $user;
    }

    protected function createRole(array $roleData, $isPortal = false)
    {
        $entityName = $isPortal ? 'PortalRole' : 'Role';

        if (isset($roleData['data']) && is_array($roleData['data'])) {
            $roleData['data'] = json_encode($roleData['data']);
        }

        if (isset($roleData['fieldData']) && is_array($roleData['fieldData'])) {
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

    protected function isShellEnabled()
    {
        if (!function_exists('exec') || !is_callable('shell_exec')) {
            return false;
        }

        $result = shell_exec("echo test");

        if (empty($result)) {
            return false;
        }

        return true;
    }
}
