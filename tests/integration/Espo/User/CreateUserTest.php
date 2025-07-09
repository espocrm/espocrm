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

namespace tests\integration\Espo\User;

use tests\integration\Core\BaseTestCase;

class CreateUserTest extends BaseTestCase
{
    protected ?string $dataFile = 'User/Login.php';

    protected ?string $userName = 'admin';
    protected ?string $password = '1';

    public function testCreateUser()
    {
        $newUser = $this->createUser('tester');

        $this->assertInstanceOf('Espo\\ORM\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->getId()));
        $this->assertEquals('tester', $newUser->get('userName'));
    }

    public function testCreateUserWithAttributes()
    {
        $newUser = $this->createUser([
            'userName' => 'tester',
            'firstName' => 'Test',
            'lastName' => 'Tester',
            'emailAddress' => 'test@tester.com',
        ]);

        $this->assertInstanceOf('Espo\\ORM\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->getId()));
        $this->assertEquals('tester', $newUser->get('userName'));
        $this->assertEquals('Test', $newUser->get('firstName'));
        $this->assertEquals('Tester', $newUser->get('lastName'));
        $this->assertEquals('test@tester.com', $newUser->get('emailAddress'));
    }

    public function testCreateUserWithRole()
    {
        $newUser = $this->createUser('tester', [
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' =>
            [
                'Account' => false,
                'Call' =>
                [
                    'create' => 'yes',
                    'read' => 'team',
                    'edit' => 'team',
                    'delete' => 'no',
                ],
            ],
            'fieldData' =>
            [
                'Call' =>
                [
                    'direction' =>
                    [
                        'read' => 'yes',
                        'edit' => 'no',
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf('Espo\\ORM\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->getId()));
        $this->assertEquals('tester', $newUser->get('userName'));
    }

    public function testCreatePortalUserWithRole()
    {
        $newUser = $this->createUser([
                'userName' => 'tester',
                'lastName' => 'tester',
                'portalsIds' => [
                    'testPortalId',
                ],
        ], [
            'assignmentPermission' => 'team',
            'userPermission' => 'team',
            'portalPermission' => 'not-set',
            'data' => [
                'Account' => false,
            ],
            'fieldData' => [
                'Call' => [
                    'direction' => [
                        'read' => 'yes',
                        'edit' => 'no',
                    ],
                ],
            ],
        ], true);

        $this->assertInstanceOf('Espo\\ORM\\Entity', $newUser);
        $this->assertTrue(!empty($newUser->getId()));
        $this->assertEquals('tester', $newUser->get('userName'));
    }
}
