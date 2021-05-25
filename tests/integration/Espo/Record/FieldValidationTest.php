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

namespace tests\integration\Espo\Record;

use Espo\Core\{
    Exceptions\BadRequest,
    Application,
    Record\CreateParams,
    Record\UpdateParams,
};

use Espo\Services\Settings as SettingsService;

class FieldValidationTest extends \tests\integration\Core\BaseTestCase
{
    private function setFieldsDefs(Application $app, string $entityType, array $data)
    {
        $metadata = $app->getContainer()->get('metadata');

        $metadata->set('entityDefs', $entityType, [
            'fields' => $data,
        ]);

        $metadata->save();
    }

    public function testRequiredVarchar1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => null,
            ], CreateParams::create());
    }

    public function testUpdateRequiredVarchar1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $entity = $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create(
                (object) [
                    'name' => 'test'
                ],
                CreateParams::create()
            );

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->update(
                $entity->id,
                (object) [
                    'name' => '',
                ],
                UpdateParams::create()
            );
    }

    public function testRequiredVarchar2()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create(
                (object) [

                ],
                CreateParams::create()
            );
    }

    public function testRequiredVarchar3()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => 'test',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testMaxLength1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
                'maxLength' => 5,
            ],
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => '123456',
            ], CreateParams::create());
    }

    public function testMaxLength2()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'name' => [
                'required' => true,
                'maxLength' => 5,
            ]
        ]);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => '12345',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredLink1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'assignedUser' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => 'test',
                'assignedUserId' => null,
            ], CreateParams::create());
    }

    public function testRequiredLink2()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'assignedUser' => [
                'required' => true,
            ]
        ]);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => 'test',
                'assignedUserId' => '1',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredLinkMultiple1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Account', [
            'teams' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Account')
            ->create((object) [
                'name' => 'test',
                'teamsIds' => [],
            ], CreateParams::create());
    }

    public function testRequiredCurrency1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Lead', [
            'opportunityAmount' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Lead')
            ->create((object) [
                'lastName' => 'test',
                'opportunityAmount' => null,
            ], CreateParams::create());
    }

    public function testRequiredCurrency2()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Lead', [
            'opportunityAmount' => [
                'required' => true,
            ]
        ]);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Lead')
            ->create((object) [
                'lastName' => 'test',
                'opportunityAmount' => 100,
                'opportunityAmountCurrency' => null,
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredCurrency3()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Lead', [
            'opportunityAmount' => [
                'required' => true,
            ]
        ]);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Lead')
            ->create((object) [
                'lastName' => 'test',
                'opportunityAmount' => 100,
                'opportunityAmountCurrency' => 'USD',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredEnum1()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Lead', [
            'status' => [
                'required' => true,
                'default' => null,
            ]
        ]);

        $app = $this->createApplication();

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Lead')
            ->create((object) [
                'lastName' => 'test',
            ], CreateParams::create());
    }

    public function testRequiredEnum2()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Lead', [
            'status' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Lead')
            ->create((object) [
                'lastName' => 'test',
                'status' => null,
            ], CreateParams::create());
    }

    public function testRequiredEnum3()
    {
        $app = $this->createApplication();

        $this->setFieldsDefs($app, 'Lead', [
            'status' => [
                'required' => true,
            ]
        ]);

        $app->getContainer()
            ->get('serviceFactory')
            ->create('Lead')
            ->create((object) [
                'lastName' => 'test',
                'status' => 'New',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testSkipRequired()
    {
        $this->getContainer()
            ->get('serviceFactory')
            ->create('Meeting')
            ->create((object) [
                'name' => 'test',
                'dateStart' => '2021-01-01 00:00:00',
                'duration' => 1000,
                'assignedUserId' => '1',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testSettings()
    {
        /* @var $service SettingsService */
        $service = $this->getContainer()->get('serviceFactory')->create('Settings');

        $this->expectException(BadRequest::class);

        $service->setConfigData((object) [
            'activitiesEntityList' => 'should-be-array',
        ]);
    }
}
