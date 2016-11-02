<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2016 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
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

    protected function createApplication($clearCache = true)
    {
        return $this->espoTester->getApplication(true, $clearCache);
    }

    protected function auth($userName, $password = null)
    {
        $this->userName = $userName;
        $this->password = $password;

        if (isset($this->espoTester)) {
            $this->espoTester->auth($userName, $password);
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

    protected function sendRequest($method, $action, $data = null)
    {
        return $this->espoTester->sendRequest($method, $action, $data);
    }

    protected function setUp()
    {
        $params = array(
            'dataFile' => $this->dataFile,
            'pathToFiles' => $this->pathToFiles,
            'className' => get_class($this),
        );

        $this->espoTester = new Tester($params);
        $this->espoTester->initialize();
        $this->auth($this->userName, $this->password);

        $this->beforeStartApplication();
        $this->espoApplication = $this->createApplication();
        $this->afterStartApplication();
    }

    protected function tearDown()
    {
        $this->espoTester->terminate();
        $this->espoTester = NULL;
        $this->espoApplication = NULL;
    }

    protected function beforeStartApplication()
    {

    }

    protected function afterStartApplication()
    {

    }

    /**
     * Create a user with roles
     *
     * @param  string|array $userData - If $userData is a string, then it's a userName with default password
     * @param  array  $roles
     *
     * @return \Espo\Entities\User
     */
    /*protected function createUser($userData, array $roles)
    {
        if (!is_array($userData)) {
            $userData = array(
                'userName' => $userData
            );
        }




    }*/
}
