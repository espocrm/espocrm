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

use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;

use Slim\Psr7\Factory\RequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

abstract class BaseTestCase extends \PHPUnit\Framework\TestCase
{
    protected $espoTester;

    private $espoApplication;

    /**
     * Path to file with data
     *
     * @var string|null
     */
    protected $dataFile = null;

    /**
     * Path to files which needs to be copied
     *
     * @var string|null
     */
    protected $pathToFiles = null;

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

    protected $initData = null;

    protected $authenticationMethod = null;

    protected function createApplication($clearCache = true, $portalId = null)
    {
        return $this->espoTester->getApplication(true, $clearCache, $portalId);
    }

    protected function auth(
        $userName = null, $password = null, $portalId = null, $authenticationMethod = null, ?RequestWrapper $request = null
    ) {
        $this->userName = $userName;
        $this->password = $password;
        $this->portalId = $portalId;
        $this->authenticationMethod = $authenticationMethod;

        if (isset($this->espoTester)) {
            $this->espoTester->auth($userName, $password, $portalId, $authenticationMethod, $request);
        }
    }

    /**
     * Get Espo Application
     *
     * @return \Espo\Core\Application
     */
    protected function getApplication()
    {
        return $this->espoApplication;
    }

    /**
     * Get Espo container
     *
     * @return \Espo\Core\Container
     */
    protected function getContainer()
    {
        return $this->getApplication()->getContainer();
    }

    protected function normalizePath($path)
    {
        return $this->espoTester->normalizePath($path);
    }

    protected function sendRequest($method, $action, $data = null)
    {
        return $this->espoTester->sendRequest($method, $action, $data);
    }

    protected function setUp() : void
    {
        $params = array(
            'className' => get_class($this),
            'dataFile' => $this->dataFile,
            'pathToFiles' => $this->pathToFiles,
            'initData' => $this->initData,
        );

        $this->espoTester = new Tester($params);

        $this->beforeSetUp();

        $this->espoTester->initialize();
        $this->auth($this->userName, $this->password, null, $this->authenticationMethod);

        $this->beforeStartApplication();
        $this->espoApplication = $this->createApplication();
        $this->afterStartApplication();
    }

    protected function tearDown() : void
    {
        $this->espoTester->terminate();
        $this->espoTester = NULL;
        $this->espoApplication = NULL;
    }

    protected function createUser($userData, array $role = null, $isPortal = false)
    {
        return $this->espoTester->createUser($userData, $role, $isPortal);
    }

    protected function beforeSetUp()
    {

    }

    protected function beforeStartApplication()
    {

    }

    protected function afterStartApplication()
    {

    }

    protected function createRequest(
        string $method, array $queryParams = [], array $headers = [], ?string $body = null, array $routeParams = []
    ) : RequestWrapper {
        $request = (new RequestFactory())->createRequest($method, 'http://localhost/?' . http_build_query($queryParams));

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

    protected function createResponse()
    {
        return new ResponseWrapper(
            (new ResponseFactory())->createResponse()
        );
    }

    protected function setData(array $data)
    {
        $this->espoTester->setData($data);
    }

    protected function fullReset()
    {
        $this->espoTester->setParam('fullReset', true);
    }
}
