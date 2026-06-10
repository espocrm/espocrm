<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Application;
use Espo\Core\Binding\BindingProcessor;
use Espo\Core\Container;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

use Espo\ORM\TransactionManager;
use Exception;
use integration\Core\NoTransaction;
use PHPUnit\Framework\TestCase;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use RuntimeException;
use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Note. Rebuild code were removed after PHPUnit upgrading.
 */
abstract class BaseTestCase extends TestCase
{
    private ?Tester $espoTester = null;
    private ?Application $espoApplication = null;
    private ?string $authenticationMethod = null;

    /** Path to file with data. */
    protected ?string $dataFile = null;
    /** Path to files which needs to be copied. */
    protected ?string $pathToFiles = null;
    /** Username used for authentication. */
    protected ?string $userName = null;
    /** Password used for authentication. */
    protected ?string $password = null;

    private ?TransactionManager $transactionManager = null;

    /**
     * @var ?array{
     *     entities?: array<string, array<string, mixed>>,
     *     files?: string,
     *     config?: array<string, mixed>,
     *     preferences?: array<string, mixed>,
     * }
     */
    protected $initData = null;

    protected function createApplication(
        bool $clearCache = true,
        ?string $portalId = null,
        ?BindingProcessor $binding = null,
        bool $reuse = false,
    ): Application {

        if (!$this->isNotCleanTest() && !$reuse) {
            if ($this->transactionManager?->isStarted()) {
                try {
                    $this->transactionManager->commit();
                } catch (Exception) {}
            }

            Registry::$isCleanAndReady = false;
        }

        return $this->espoTester->getApplication(
            reload: true,
            clearCache: $clearCache,
            portalId: $portalId,
            binding: $binding,
            reuse: $reuse,
        );
    }

    protected function setApplication(Application $application): void
    {
        $this->espoApplication = $application;
    }

    /**
     * Set authentication credentials. It does not authenticate.
     */
    protected function auth(
        ?string $userName = null,
        ?string $password = null,
        ?string $portalId = null,
        ?string $authenticationMethod = null,
        ?RequestWrapper $request = null,
    ): void {

        $this->userName = $userName;
        $this->password = $password;
        $this->authenticationMethod = $authenticationMethod;

        $this->espoTester?->auth(
            userName: $userName,
            password: $password,
            portalId: $portalId,
            authenticationMethod: $authenticationMethod,
            request: $request,
        );
    }

    /**
     * Get the application.
     */
    protected function getApplication(): Application
    {
        return $this->espoApplication;
    }

    /**
     * Get container.
     */
    protected function getContainer(): Container
    {
        return $this->getApplication()->getContainer();
    }

    protected function getFileManager(): FileManager
    {
        return $this->getApplication()->getContainer()->getByClass(FileManager::class);
    }

    protected function getDataManager(): DataManager
    {
        return $this->getApplication()->getContainer()->getByClass(DataManager::class);
    }

    protected function getInjectableFactory(): InjectableFactory
    {
        return $this->getApplication()->getContainer()->getByClass(InjectableFactory::class);
    }

    protected function getMetadata(): Metadata
    {
        return $this->getApplication()->getContainer()->getByClass(Metadata::class);
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->getApplication()->getContainer()->getByClass(EntityManager::class);
    }

    protected function getConfig(): Config
    {
        return $this->getApplication()->getContainer()->getByClass(Config::class);
    }

    protected function normalizePath(string $path): string
    {
        return $this->espoTester->normalizePath($path);
    }

    protected function setUp(): void
    {
        $params = [
            'className' => get_class($this),
            'dataFile' => $this->dataFile,
            'pathToFiles' => $this->pathToFiles,
            'initData' => $this->initData,
        ];

        $this->espoTester = new Tester($params);

        $this->beforeSetUp();

        try {
            $this->espoTester->install();
            $this->espoTester->loadData();
        } catch (Error $e) {
            throw new RuntimeException("Initialization error.", previous: $e);
        }

        $this->auth($this->userName, $this->password, null, $this->authenticationMethod);

        $this->beforeStartApplication();

        $this->espoApplication = $this->espoTester->getApplication(reload: true);

        $this->afterStartApplication();

        $this->setUpTransaction();

        $this->espoApplication = $this->espoTester->getApplication();
    }

    /**
     * Authenticates. The user is supposed to be created by the test before.
     *
     * @param ?string $username A username. Null for the system user.
     */
    protected function authenticate(
        ?string $username = null,
        ?string $method = null,
        ?RequestWrapper $request = null,
    ): void {

        $this->auth($username, authenticationMethod: $method, request: $request);

        $this->getEntityManager()->getMetadata()->updateData();

        $this->getContainer()->reset([
            'entityManager',
            'ormMetadataData',
            'ormDefs',
            'metadata',
            'config',
            'module',
            'fileManager',
            'applicationParams',
        ]);

        if ($username === null && $method === null) {
            $this->espoApplication->setupSystemUser();

            return;
        }

        $this->espoTester->login();
    }

    /**
     * Re-create an application.
     */
    protected function reCreateApplication(bool $reuse = false): void
    {
        $this->espoApplication = $this->createApplication(
            reuse: $reuse,
        );
    }

    protected function tearDown(): void
    {
        $isNotClean = $this->isNotCleanTest();

        if (
            !$isNotClean &&
            $this->transactionManager?->isStarted()
        ) {
            try {
                $this->transactionManager->rollback();
            } catch (Exception) {}
        }

        $this->transactionManager = null;

        $this->espoTester->terminate();
        $this->espoTester = null;
        $this->espoApplication = null;

        if ($isNotClean) {
            Registry::$isCleanAndReady = false;
        }
    }

    /**
     * @param array<string, mixed>|string $userData Data or a user name.
     * @param ?array<string, mixed> $role
     */
    protected function createUser($userData, ?array $role = null, bool $isPortal = false): User
    {
        return $this->espoTester->createUser($userData, $role, $isPortal);
    }

    protected function beforeSetUp(): void
    {}

    protected function beforeStartApplication(): void
    {}

    protected function afterStartApplication(): void
    {}

    /**
     * @param array<string, mixed> $queryParams
     */
    protected function createRequest(
        string $method,
        array $queryParams = [],
        array $headers = [],
        ?string $body = null,
        array $routeParams = [],
    ): RequestWrapper {

        $request = (new RequestFactory())
            ->createRequest($method, 'http://localhost/?' . http_build_query($queryParams));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body) {
            $request = $request->withBody(
                (new StreamFactory)->createStream($body)
            );
        }

        return new RequestWrapper($request, '', $routeParams);
    }

    protected function createResponse(): ResponseWrapper
    {
        return new ResponseWrapper(
            (new ResponseFactory())->createResponse()
        );
    }

    protected function fullReset(): void
    {
        $this->espoTester->setParam('fullReset', true);
    }

    private function isNotCleanTest(): bool
    {
        if ($this->dataFile || $this->pathToFiles || $this->initData) {
            return true;
        }

        try {
            $reflectionMethod = new ReflectionMethod($this, $this->name());
            $reflectionClass = new ReflectionClass($this);
        } catch (ReflectionException $e) {
            throw new RuntimeException(previous: $e);
        }

        return $reflectionMethod->getAttributes(NoTransaction::class) ||
            $reflectionClass->getAttributes(NoTransaction::class);
    }

    private function setUpTransaction(): void
    {
        $this->transactionManager = null;

        if (!$this->isNotCleanTest()) {
            $this->transactionManager = $this->getEntityManager()->getTransactionManager();

            $this->transactionManager->start();
        }
    }
}
