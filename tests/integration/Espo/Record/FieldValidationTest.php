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

namespace tests\integration\Espo\Record;

use Espo\Core\Application;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\FieldValidation\Type;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\EntityManager;
use Espo\Tools\App\SettingsService as SettingsService;
use tests\integration\Core\BaseTestCase;

class FieldValidationTest extends BaseTestCase
{
    private function setFieldsDefs(Application $app, string $entityType, array $data)
    {
        $metadata = $app->getContainer()->getByClass(Metadata::class);

        $metadata->set('entityDefs', $entityType, [
            'fields' => $data,
        ]);

        $metadata->save();
    }

    public function testRequiredVarchar1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => null,
            ], CreateParams::create());
    }

    public function testUpdateRequiredVarchar1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $entity = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create(
                (object) [
                    'name' => 'test'
                ],
                CreateParams::create()
            )
            ->getEntity();

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->update(
                $entity->getId(),
                (object) [
                    'name' => '',
                ],
                UpdateParams::create()
            );
    }

    public function testRequiredVarchar2()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create(
                (object) [

                ],
                CreateParams::create()
            );
    }

    public function testRequiredVarchar3()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'name' => [
                'required' => true,
            ],
        ]);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => 'test',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testMaxLength1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'name' => [
                'required' => true,
                'maxLength' => 5,
            ],
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => '123456',
            ], CreateParams::create());
    }

    public function testMaxLength2()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'name' => [
                'required' => true,
                'maxLength' => 5,
            ]
        ]);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => '12345',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredLink1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'assignedUser' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => 'test',
                'assignedUserId' => null,
            ], CreateParams::create());
    }

    private function getAdminUser(): User
    {
        $repository = $this->getContainer()
            ->getByClass(EntityManager::class)
            ->getRDBRepositoryByClass(User::class);

        $user = $repository
            ->where(['type' => User::TYPE_ADMIN])
            ->findOne();

        if (!$user) {
            $user = $repository->getNew();
            $user->set('userName', 'test-admin');
            $user->set('type', User::TYPE_ADMIN);

            $repository->save($user);
        }

        return $user;
    }

    public function testRequiredLink2()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'assignedUser' => [
                'required' => true,
            ]
        ]);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => 'test',
                'assignedUserId' => $this->getAdminUser()->getId(),
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredLinkMultiple1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'teams' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->create((object) [
                'name' => 'test',
                'teamsIds' => [],
            ], CreateParams::create());
    }

    public function testRequiredCurrency1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Lead', [
            'opportunityAmount' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Lead')
            ->create((object) [
                'lastName' => 'test',
                'opportunityAmount' => null,
            ], CreateParams::create());
    }

    public function testRequiredCurrency2()
    {
        $this->setFieldsDefs($this->getApplication(), 'Lead', [
            'opportunityAmount' => [
                'required' => true,
            ]
        ]);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Lead')
            ->create((object) [
                'lastName' => 'test',
                'opportunityAmount' => 100,
                'opportunityAmountCurrency' => null,
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredCurrency3()
    {
        $this->setFieldsDefs($this->getApplication(), 'Lead', [
            'opportunityAmount' => [
                'required' => true,
            ]
        ]);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Lead')
            ->create((object) [
                'lastName' => 'test',
                'opportunityAmount' => 100,
                'opportunityAmountCurrency' => 'USD',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testRequiredEnum1()
    {
        $this->setFieldsDefs($this->getApplication(), 'Lead', [
            'status' => [
                'required' => true,
                'default' => null,
            ]
        ]);

        $this->getDataManager()->clearCache();
        $this->getDataManager()->rebuildMetadata();

        $this->authenticate(null);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Lead')
            ->create((object) [
                'lastName' => 'test',
            ], CreateParams::create());
    }

    public function testRequiredEnum2()
    {
        $this->setFieldsDefs($this->getApplication(), 'Lead', [
            'status' => [
                'required' => true,
            ]
        ]);

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Lead')
            ->create((object) [
                'lastName' => 'test',
                'status' => null,
            ], CreateParams::create());
    }

    public function testRequiredEnum3()
    {
        $this->setFieldsDefs($this->getApplication(), 'Lead', [
            'status' => [
                'required' => true,
            ]
        ]);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Lead')
            ->create((object) [
                'lastName' => 'test',
                'status' => 'New',
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testSkipRequired()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Meeting')
            ->create((object) [
                'name' => 'test',
                'dateStart' => '2021-01-01 00:00:00',
                'duration' => 1000,
                'assignedUserId' => $this->getAdminUser()->getId(),
            ], CreateParams::create());

        $this->assertTrue(true);
    }

    public function testSettings()
    {
        $service = $this->getInjectableFactory()->create(SettingsService::class);

        $this->expectException(BadRequest::class);

        $service->setConfigData((object) [
            'activitiesEntityList' => 'should-be-array',
        ]);
    }

    public function testCurrencyValid(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Lead::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->create((object) [
            'lastName' => 'Test 1',
            'opportunityAmount' => '100.10',
        ], CreateParams::create());

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->create((object) [
            'lastName' => 'Test 2',
            'opportunityAmount' => '100',
        ], CreateParams::create());

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->create((object) [
            'lastName' => 'Test 3',
            'opportunityAmount' => '',
        ], CreateParams::create());

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->create((object) [
            'lastName' => 'Test 4',
            'opportunityAmount' => null,
        ], CreateParams::create());

        $this->expectException(BadRequest::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->create((object) [
            'lastName' => 'Test Bad 1',
            'opportunityAmount' => 'bad-value',
        ], CreateParams::create());
    }

    public function testPhoneNumber(): void
    {
        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        $configWriter = $this->getInjectableFactory()->create(ConfigWriter::class);
        $configWriter->set('phoneNumberExtensions', true);
        $configWriter->save();

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->create((object)[
            'name' => 'Test 1',
            'phoneNumberData' => [
                (object)[
                    'phoneNumber' => '+38 09 044 433 22 ext. 001',
                ],
            ],
        ], CreateParams::create());

        $thrown = false;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            $service->create((object)[
                'name' => 'Test 2',
                'phoneNumberData' => [
                    (object)[
                        'phoneNumber' => '+38 09 044 433 22 ext. ABC',
                    ],
                    (object)[
                        'phoneNumber' => '+38 09 044 433 33 ext. 1234567',
                    ],
                ],
            ], CreateParams::create());
        } catch (BadRequest) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testDynamicLogicRequired(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('logicDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'description' => [
                    Type::REQUIRED => [
                        'conditionGroup' => [
                            [
                                'type' => 'equals',
                                'attribute' => 'type',
                                'value' => 'Customer',
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $metadata->save();

        $this->getDataManager()->clearCache();
        $this->getDataManager()->rebuildMetadata();

        $service = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(Account::class);

        $account = $service->create((object) [
            'name' => 'Test',
        ], CreateParams::create())->getEntity();

        $isThrown = false;

        try {
            $service->update($account->getId(), (object) [
                'type' => 'Customer',
            ], UpdateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        $isThrown = false;

        try {
            $service->update($account->getId(), (object) [
                'type' => 'Customer',
                'description' => 'Test.',
            ], UpdateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }

        $this->assertFalse($isThrown);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testDecimal(): void
    {
        $metadata = $this->getMetadata();
        $metadata->set('entityDefs', 'Account', [
            'fields' => [
                'test' => [
                    'type' => FieldType::DECIMAL,
                    'min' => '-1.0',
                    'max' => '100.0',
                ]
            ],
        ]);
        $metadata->save();

        $this->getDataManager()->rebuild();

        $this->reCreateApplication();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Account::class);

        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-0',
                'test' => null,
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertFalse($isThrown);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-1',
                'test' => '10.0',
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertFalse($isThrown);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-2',
                'test' => '-1.0',
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertFalse($isThrown);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-3',
                'test' => '100.0',
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertFalse($isThrown);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-4',
                'test' => '-10.0',
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertTrue($isThrown);

        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-5',
                'test' => '100.1',
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertTrue($isThrown);

        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-6',
                'test' => 'abc',
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertTrue($isThrown);

        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-7',
                'test' => ['test'],
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertTrue($isThrown);

        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-8',
                'test' => 10.0,
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertFalse($isThrown);

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $isThrown = false;
        try {
            $service->create((object) [
                'name' => 'Test-9',
                'test' => 10,
            ], CreateParams::create());
        } catch (BadRequest) {
            $isThrown = true;
        }
        $this->assertFalse($isThrown);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testDependsOn(): void
    {
        $em = $this->getEntityManager();

        $account = $em->createEntity('Account', [
            'name' => 'Test',
        ]);

        $this->setFieldsDefs($this->getApplication(), 'Account', [
            'description' => [
                'required' => true,
                'validationDependsOnFieldList' => ['name'],
            ],
        ]);

        $this->getDataManager()->clearCache();
        $this->getDataManager()->rebuildMetadata();

        $this->expectException(BadRequest::class);

        $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->get('Account')
            ->update($account->getId(), (object) [
                'name' => 'Test 1',
            ], UpdateParams::create());
    }
}
