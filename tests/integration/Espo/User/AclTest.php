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

namespace tests\integration\Espo\User;

class AclTest extends \tests\integration\Core\BaseTestCase
{
    protected $dataFile = 'User/Login.php';

    protected $userName = 'admin';
    protected $password = '1';

    /**
     * @expectedException \Espo\Core\Exceptions\Forbidden
     */
    public function testUserAccess()
    {
        $this->createUser('tester', array(
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' => array(
                'Account' => false,
                'Call' =>
                array (
                    'create' => 'yes',
                    'read' => 'team',
                    'edit' => 'team',
                    'delete' => 'no'
                )
            ),
            'fieldData' => array(
                'Call' => array(
                    'direction' => array(
                        'read' => 'yes',
                        'edit' => 'no'
                    )
                )
            )
        ));

        $this->auth('tester');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = array();
        $data = '{"name":"Test Account"}';
        $request = $this->createRequest('POST', $params, array('CONTENT_TYPE' => 'application/json'));
        $result = $controllerManager->process('Account', 'create', $params, $data, $request);
    }

    ///**
    // * @expectedException \Exception
    // * @expectedExceptionCode 403
    // */
    /*public function testUserAccess()
    {
        $this->testCreateUserWithRole();
        $this->auth('tester');

        $this->sendRequest('POST', 'Account', array(
            'name' => 'Test Account',
        ));
    }*/

    /**
     * @expectedException \Espo\Core\Exceptions\Forbidden
     */
    public function testPortalUserAccess()
    {
        $newUser = $this->createUser(array(
                'userName' => 'tester',
                'lastName' => 'tester',
                'portalsIds' => array(
                    'testPortalId'
                )
            ), array(
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' => array (
                'Account' => false,
            ),
            'fieldData' => array (
                'Call' => array (
                    'direction' => array (
                        'read' => 'yes',
                        'edit' => 'no'
                    )
                )
            )
        ), true);

        $this->auth('tester', null, 'testPortalId');

        $app = $this->createApplication();

        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = array();
        $data = '{"name":"Test Account"}';
        $request = $this->createRequest('POST', $params, array('CONTENT_TYPE' => 'application/json'));
        $result = $controllerManager->process('Account', 'create', $params, $data, $request);
    }

    ///**
    // * @expectedException \Exception
    // * @expectedExceptionCode 403
    // */
    /*public function testPortalUserAccess()
    {
        $this->testCreatePortalUserWithRole();
        $this->auth('tester', null, 'testPortalId');

        $this->sendRequest('POST', 'Account', array(
            'name' => 'Test Account',
        ));
    }*/

    public function testUserAccessEditOwn1()
    {
        $user1 = $this->createUser('test-1', [
            "id" => "test-1",
            'data' => [
                'User' => [
                    'read' => 'all',
                    'edit' => 'own'
                ]
            ]
        ]);

        $this->createUser('test-2', []);

        $this->auth('test-1');
        $app = $this->createApplication();
        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = [
            'id' => $user1->id
        ];
        $data = [
            'id' => $user1->id,
            'title' => 'Test'
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);

        $this->assertTrue(is_string($result));

        $params = [
            'id' => $user2->id
        ];
        $data = [
            'id' => $user2->id,
            'title' => 'Test'
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);

        $result = null;
        try {
            $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);
        } catch (\Exception $e) {};

        $this->assertNull($result);


        $params = [
            'id' => $user1->id
        ];
        $data = [
            'id' => $user1->id,
            'isAdmin' => 1,
            'teamsIds' => ['id']
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);
        $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);
        $resultData = json_decode($result);

        $this->assertTrue(!property_exists($resultData, 'isAdmin') || !$resultData->isAdmin);
        $this->assertTrue(
            !property_exists($resultData, 'teamsIds') || !is_array($resultData->teamsIds) || !in_array('id', $$resultData->teamsIds)
        );
    }

    public function testUserAccessEditOwn2()
    {
        $user1 = $this->createUser('test-1', [
            "id" => "test-1",
            'data' => [
                'User' => [
                    'read' => 'all',
                    'edit' => 'no'
                ]
            ]
        ]);

        $this->auth('test-1');
        $app = $this->createApplication();
        $controllerManager = $app->getContainer()->get('controllerManager');

        $params = [
            'id' => $user1->id
        ];
        $data = [
            'id' => $user1->id,
            'title' => 'Test'
        ];
        $request = $this->createRequest('PATCH', $params, ['CONTENT_TYPE' => 'application/json']);

        $result = null;
        try {
            $result = $controllerManager->process('User', 'update', $params, json_encode($data), $request);
        } catch (\Exception $e) {};

        $this->assertNull($result);
    }
}
