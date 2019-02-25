<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\integration\Espo\Record;

class FieldValidationTest extends \tests\integration\Core\BaseTestCase
{
    private function setFieldsDefs($app, $entityType, $data)
    {
        $metadata = $app->getContainer()->get('metadata');
        $metadata->set('entityDefs', $entityType, [
            'fields' => $data
        ]);
        $metadata->save();
    }

    public function testRequiredVarchar1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => null
        ]);
    }

    public function testUpdateRequiredVarchar1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true
            ]
        ]);

        $entity = $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test'
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Account')->update($entity->id, (object) [
            'name' => ''
        ]);
    }

    public function testMassUpdateRequiredVarchar1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true
            ]
        ]);

        $entity = $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test'
        ]);

        $result = $app->getContainer()->get('serviceFactory')->create('Account')->massUpdate(
            [
                'ids' => [$entity->id]
            ],
            (object) [
                'name' => ''
            ]
        );

        $this->assertEquals(0, $result->count);
    }

    public function testMassUpdateRequiredVarchar2()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true
            ]
        ]);

        $entity = $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test'
        ]);

        $result = $app->getContainer()->get('serviceFactory')->create('Account')->massUpdate(
            [
                'ids' => [$entity->id]
            ],
            (object) [
                'name' => 'hello'
            ]
        );

        $this->assertEquals(1, $result->count);
    }

    public function testRequiredVarchar2()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [

        ]);
    }

    public function testRequiredVarchar3()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true
            ]
        ]);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test'
        ]);

        $this->assertTrue(true);
    }

    public function testMaxLength1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
                'maxLength' => 5
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => '123456'
        ]);
    }

    public function testMaxLength2()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
                'maxLength' => 5
            ]
        ]);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => '12345'
        ]);

        $this->assertTrue(true);
    }

    public function testRequiredLink1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'assignedUser' => [
                'required' => true
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test',
            'assignedUserId' => null,
        ]);
    }

    public function testRequiredLink2()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'assignedUser' => [
                'required' => true
            ]
        ]);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test',
            'assignedUserId' => '1',
        ]);

        $this->assertTrue(true);
    }

    public function testRequiredLinkMultiple1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Account', [
            'teams' => [
                'required' => true
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Account')->create((object) [
            'name' => 'test',
            'teamsIds' => [],
        ]);
    }

    public function testRequiredCurrency1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Lead', [
            'opportunityAmount' => [
                'required' => true
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Lead')->create((object) [
            'lastName' => 'test',
            'opportunityAmount' => null,
        ]);
    }

    public function testRequiredCurrency2()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Lead', [
            'opportunityAmount' => [
                'required' => true
            ]
        ]);

        $app->getContainer()->get('serviceFactory')->create('Lead')->create((object) [
            'lastName' => 'test',
            'opportunityAmount' => 100,
            'opportunityAmountCurrency' => null,
        ]);

        $this->assertTrue(true);
    }

    public function testRequiredCurrency3()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Lead', [
            'opportunityAmount' => [
                'required' => true
            ]
        ]);

        $app->getContainer()->get('serviceFactory')->create('Lead')->create((object) [
            'lastName' => 'test',
            'opportunityAmount' => 100,
            'opportunityAmountCurrency' => 'USD',
        ]);

        $this->assertTrue(true);
    }

    public function testRequiredEnum1()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Lead', [
            'status' => [
                'required' => true,
                'default' => null
            ]
        ]);
        $app = $this->createApplication();

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $e = $app->getContainer()->get('serviceFactory')->create('Lead')->create((object) [
            'lastName' => 'test'
        ]);

        $this->assertTrue($status === null);
    }

    public function testRequiredEnum2()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Lead', [
            'status' => [
                'required' => true
            ]
        ]);

        $this->expectException(\Espo\Core\Exceptions\BadRequest::class);

        $app->getContainer()->get('serviceFactory')->create('Lead')->create((object) [
            'lastName' => 'test',
            'status' => null
        ]);
    }

    public function testRequiredEnum3()
    {
        $app = $this->createApplication();
        $this->setFieldsDefs($app, 'Lead', [
            'status' => [
                'required' => true
            ]
        ]);

        $e = $app->getContainer()->get('serviceFactory')->create('Lead')->create((object) [
            'lastName' => 'test',
            'status' => 'New'
        ]);

        $this->assertTrue(true);
    }
}
