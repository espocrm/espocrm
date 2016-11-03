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

namespace tests\integration\Espo\User;

class LoginTest extends \tests\integration\Core\BaseTestCase
{
    protected $userName = 'admin';
    protected $password = '1';

    public function testLogin()
    {
        $user = $this->getContainer()->get('user');

        $this->assertEquals('Espo\\Entities\\User', get_class($user));
        $this->assertEquals('1', $user->get('id'));
        $this->assertEquals('admin', $user->get('userName'));
    }

    public function testWrongCredentials()
    {
        $this->auth('admin', 'wrong-password');
        $application = $this->createApplication();

        $this->assertNull($application->getContainer()->get('user'));
    }

    public function testCreateUser()
    {
        $newUser = $this->createUser('tester');

        $this->assertInstanceOf('\\Espo\\Orm\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->id));
        $this->assertEquals('tester', $newUser->get('userName'));
    }

    public function testCreateUserWithAttributes()
    {
        $newUser = $this->createUser(array(
            'userName' => 'tester',
            'firstName' => 'Test',
            'lastName' => 'Tester',
            'emailAddress' => 'test@tester.com',
        ));

        $this->assertInstanceOf('\\Espo\\Orm\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->id));
        $this->assertEquals('tester', $newUser->get('userName'));
        $this->assertEquals('Test', $newUser->get('firstName'));
        $this->assertEquals('Tester', $newUser->get('lastName'));
        $this->assertEquals('test@tester.com', $newUser->get('emailAddress'));
    }

    public function testCreateUserWithRole()
    {
        $newUser = $this->createUser('tester', array(
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' =>
            array (
                'Account' => false,
                'Call' =>
                array (
                    'create' => 'yes',
                    'read' => 'team',
                    'edit' => 'team',
                    'delete' => 'no',
                ),
            ),
            'fieldData' =>
            array (
                'Call' =>
                array (
                    'direction' =>
                    array (
                        'read' => 'yes',
                        'edit' => 'no',
                    ),
                ),
            ),
        ));

        $this->assertInstanceOf('\\Espo\\Orm\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->id));
        $this->assertEquals('tester', $newUser->get('userName'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 403
     */
    public function testAccessUser()
    {
        $this->testCreateUserWithRole();
        $this->auth('tester');

        $this->sendRequest('POST', 'Account', array(
            'name' => 'Test Account',
        ));
    }

    public function testCreatePortalUserWithRole()
    {
        $newUser = $this->createUser(array(
                'userName' => 'tester',
                'lastName' => 'tester',
                'portalsIds' => array(
                    'testPortalId',
                ),
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
                        'edit' => 'no',
                    ),
                ),
            ),
        ), true);

        $this->assertInstanceOf('\\Espo\\Orm\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->id));
        $this->assertEquals('tester', $newUser->get('userName'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 403
     */
    public function testAccessPortalUser()
    {
        $this->testCreatePortalUserWithRole();
        $this->auth('tester', null, 'testPortalId');

        $this->sendRequest('POST', 'Account', array(
            'name' => 'Test Account',
        ));
    }
}
