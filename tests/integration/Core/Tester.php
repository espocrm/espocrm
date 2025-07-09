<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\integration\Core;

use Doctrine\DBAL\Schema\Table;
use Espo\Core\Authentication\Authentication;
use Espo\Core\Authentication\AuthenticationData;

use Espo\Core\Application;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\ApplicationRunners\Rebuild;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Dbal\ConnectionFactoryFactory;
use Espo\Core\Utils\Database\Helper as DatabaseHelper;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\PasswordHash;

use Espo\Entities\PortalRole;
use Espo\Entities\Role;
use Espo\Entities\User;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Installer;
use RuntimeException;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Response;

class Tester
{
    private string $configPath = 'tests/integration/config.php';
    private string $envConfigPath = 'tests/integration/config-env.php';

    private string $buildPath = 'build';
    private string $installPath = 'build/test';
    private string $testDataPath = 'tests/integration/testData';
    private string $packageJsonPath = 'package.json';

    private ?Application $application = null;
    private ?DataLoader $dataLoader = null;
    private array $params;

    private ?string $userName = null;
    private ?string $password = null;

    private ?string $portalId = null;
    private ?string $authenticationMethod = null;
    private string $defaultUserPassword = '1';

    private ?RequestWrapper $request = null;

    public function __construct(array $params)
    {
        $this->params = $this->normalizeParams($params);
    }

    private function normalizeParams(array $params): array
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

    private function getParam(string $name): mixed
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return null;
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    protected function getTestConfigData(): array
    {
        $this->changeDirToBase();

        if (file_exists($this->configPath)) {
            $data = include($this->configPath);
        } else if (getenv('TEST_DATABASE_NAME')) {
            $data = include($this->envConfigPath);
        } else {
            die('Config for integration tests ['. $this->configPath .'] is not found');
        }

        $packageData = json_decode(file_get_contents($this->packageJsonPath));

        $version = $packageData->version;

        $data['version'] = $version;

        return $data;
    }

    private function saveTestConfigData(string $optionName, $data): void
    {
        $configData = $this->getTestConfigData();

        if (array_key_exists($optionName, $configData) && $configData[$optionName] === $data) {
            return;
        }

        $configData[$optionName] = $data;

        $fileManager = new FileManager();

        $fileManager->putPhpContents($this->configPath, $configData);
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

            $applicationParams = new Application\ApplicationParams(noErrorHandler: true);

            $this->application = !$portalId ?
                new Application($applicationParams) :
                new PortalApplication($portalId, $applicationParams);

            $auth = $this->application
                ->getContainer()
                ->getByClass(InjectableFactory::class)
                ->createWith(Authentication::class, ['allowAnyAccess' => false]);

            $request = $this->request ??
                new RequestWrapper(
                    (new RequestFactory())->createRequest('POST', '')
                );

            $response = new ResponseWrapper(new Response());

            if (isset($this->userName) || $this->authenticationMethod) {
                $this->password = $this->password ?? $this->defaultUserPassword;

                $authenticationData = AuthenticationData::create()
                    ->withUsername($this->userName)
                    ->withPassword($this->password)
                    ->withMethod($this->authenticationMethod);

                $auth->login($authenticationData, $request, $response);
            } else {
                $this->application->setupSystemUser();
            }
        }

        return $this->application;
    }

    private function getDataLoader(): DataLoader
    {
        if (!isset($this->dataLoader)) {
            $this->dataLoader = new DataLoader($this->getApplication());
        }

        return $this->dataLoader;
    }

    public function initialize(): void
    {
        $this->install();
        $this->loadData();
    }

    private function changeDirToBase(): void
    {
        $installPath = str_replace('/', DIRECTORY_SEPARATOR, $this->installPath);

        $baseDir = str_replace(DIRECTORY_SEPARATOR . $installPath, '', getcwd());

        chdir($baseDir);
        set_include_path($baseDir);
    }

    public function terminate(): void
    {
        $this->changeDirToBase();

        if ($this->getParam('fullReset')) {
            $this->saveTestConfigData('lastModifiedTime', null);
        }
    }

    protected function install(): void
    {
        $fileManager = new FileManager();

        $configData = $this->getTestConfigData();

        $latestEspoDir = Utils::getLatestBuiltPath($this->buildPath);

        if (empty($latestEspoDir)) {
            die("EspoCRM build is not found. Please run \"grunt\" in your terminal.\n");
        }

        if (!isset($configData['siteUrl']) && file_exists('data/config.php')) {
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

        $this->reset($fileManager, $latestEspoDir);

        Utils::fixUndefinedVariables();

        chdir($this->installPath);
        set_include_path($this->installPath);

        if (!file_exists('bootstrap.php')) {
            die("Permission denied to copy espo files.\n");
        }

        require_once('install/core/Installer.php');

        $applicationParams = new Application\ApplicationParams(noErrorHandler: true);

        $installer = new Installer($applicationParams);

        $installer->saveData(array_merge($configData, [
            'language' => 'en_US'
        ]));

        $installer->saveConfig($configData);

        $app = new Application($applicationParams);

        $this->createDatabase($app);
        $this->dropTables($app);

        $installer = new Installer($applicationParams); // reload installer to have all config data
        $installer->rebuild();
        $installer->setSuccess();
    }

    // PDO can't be instantiated as dbname is set but database does not exist.
    private function createDatabase(Application $app): void
    {
        $injectableFactory = $app->getContainer()->getByClass(InjectableFactory::class);

        $databaseHelper = $injectableFactory->create(DatabaseHelper::class);
        $databaseParamsFactory = $injectableFactory->create(DatabaseParamsFactory::class);
        $connectionFactoryFactory = $injectableFactory->create(ConnectionFactoryFactory::class);

        $params = $databaseParamsFactory->create();

        $dbname = $params->getName();

        if (!$dbname) {
            throw new RuntimeException('No "dbname" in database config.');
        }

        $params = $params->withName(null);

        $pdo = $databaseHelper->createPDO($params);

        $connection = $connectionFactoryFactory
            ->create($params->getPlatform(), $pdo)
            ->create($params);

        $schemaManager = $connection->createSchemaManager();
        $platform = $connection->getDatabasePlatform();

        if (in_array($dbname, $schemaManager->listDatabases())) {
            return;
        }

        $schemaManager->createDatabase($platform->quoteIdentifier($dbname));
    }

    private function dropTables(Application $app): void
    {
        $databaseHelper = $app->getContainer()
            ->getByClass(InjectableFactory::class)
            ->create(DatabaseHelper::class);

        $schemaManager = $databaseHelper->getDbalConnection()->createSchemaManager();
        $platform = $databaseHelper->getDbalConnection()->getDatabasePlatform();

        $pdo = $databaseHelper->getPDO();

        $tables = $schemaManager->listTableNames();

        foreach ($tables as $table) {
            $sql = $platform->getDropTableSQL(new Table($table));

            $pdo->query($sql);
        }
    }

    private function reset($fileManager, $latestEspoDir): void
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
            if ($this->isShellEnabled()) {
                shell_exec('rm -rf "' . $this->installPath . '"');
                shell_exec('cp -r "' . $latestEspoDir . '" "' . $this->installPath . '"');
            } else {
                $fileManager->removeInDir($this->installPath);
                $fileManager->copy($latestEspoDir, $this->installPath, true);
            }

            return;
        }

        $fileManager->removeInDir($this->installPath . '/data');
        $fileManager->removeInDir($this->installPath . '/custom/Espo/Custom');
        $fileManager->removeInDir($this->installPath . '/client/custom');
        $fileManager->unlink($this->installPath . '/install/config.php');
    }

    /*private function cleanDirectory(string $path, array $ignoreList = []): void
    {
        if (!file_exists($path)) {
            return;
        }

        $fileManager = new FileManager();

        $list = $fileManager->getFileList($path);

        foreach ($list as $itemName) {
            if (in_array($itemName, $ignoreList)) {
                continue;
            }

            $itemPath = $path . '/' . $itemName;

            if (is_file($itemPath)) {
                $fileManager->unlink($itemPath);
            } else {
                $fileManager->removeInDir($itemPath, true);
            }
        }
    }*/

    private function loadData(): void
    {
        $applyChanges = false;

        if (!empty($this->params['pathToFiles']) && file_exists($this->params['pathToFiles'])) {
            $this->getDataLoader()->loadFiles($this->params['pathToFiles']);

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

    /*public function setData(array $data): void
    {
        $this->getDataLoader()->setData($data);
        $this->getApplication(true, true)->run(Rebuild::class);
    }*/

    public function clearCache(): void
    {
        $this->clearVars();

        $fileManager = new FileManager();

        $fileManager->removeInDir('data/cache');
    }

    private function clearVars(): void
    {
        $this->dataLoader = null;
        $this->application = null;
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

            $fieldName = $isPortal ? 'portalRolesIds' : 'rolesIds';

            if (!isset($userData[$fieldName])) {
                $userData[$fieldName] = [];
            }

            $userData[$fieldName][] = $role->getId();
        }

        $application = $this->getApplication();

        $entityManager = $application->getContainer()->getByClass(EntityManager::class);
        $config = $application->getContainer()->getByClass(Config::class);

        if (!isset($userData['password'])) {
            $userData['password'] = $this->defaultUserPassword;
        }

        $passwordHash = new PasswordHash($config);

        $userData['password'] = $passwordHash->hash($userData['password']);

        if ($isPortal) {
            $userData['type'] = 'portal';
        }

        $user = $entityManager->getNewEntity(User::ENTITY_TYPE);
        $user->set($userData);

        $entityManager->saveEntity($user);

        return $user;
    }

    private function createRole(array $roleData, bool $isPortal = false): Entity
    {
        $entityType = $isPortal ? PortalRole::ENTITY_TYPE : Role::ENTITY_TYPE;

        if (isset($roleData['data']) && is_array($roleData['data'])) {
            $roleData['data'] = json_encode($roleData['data']);
        }

        if (isset($roleData['fieldData']) && is_array($roleData['fieldData'])) {
            $roleData['fieldData'] = json_encode($roleData['fieldData']);
        }

        $application = $this->getApplication();
        $entityManager = $application->getContainer()->getByClass(EntityManager::class);

        $role = $entityManager->getNewEntity($entityType);
        $role->set($roleData);
        $entityManager->saveEntity($role);

        return $role;
    }

    public function normalizePath($path): string
    {
        return $this->getParam('testDataPath') . '/' . $path;
    }

    private function isShellEnabled(): bool
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
