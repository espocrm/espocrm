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

namespace tests\integration\Espo\Core\Select;

use Espo\Core\{
    Application,
    Container,
    Select\SelectBuilderFactory,
    Select\SearchParams,
};

use Espo\Classes\Select\Email\AdditionalAppliers\Main as EmailAdditionalApplier;

class SelectBuilderTest extends \tests\integration\Core\BaseTestCase
{
    /**
     * @var SelectBuilderFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $injectableFactory = $this->getContainer()->get('injectableFactory');

        $this->factory = $injectableFactory->create(SelectBuilderFactory::class);
    }

    protected function initTest(array $aclData = [], bool $skipLogin = false, bool $isPortal = false) : Application
    {
        $this->createUser('tester', [
            'data' => $aclData,
        ]);

        if (!$skipLogin) {
            $this->auth('tester');
        }

        $app = $this->createApplication();

        $injectableFactory = $app->getContainer()->get('injectableFactory');

        $this->factory = $injectableFactory->create(SelectBuilderFactory::class);

        $this->user = $app->getContainer()->get('user');

        return $app;
    }

    protected function initTestPortal(array $aclData = [], bool $skipLogin = false) : Application
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $this->contact = $em->createEntity('Contact', []);
        $this->account = $em->createEntity('Account', []);
        $this->portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser(
            [
                'userName' => 'tester',
                'portalsIds' => [$this->portal->id],
                'contactId' => $this->contact->id,
                'accountsIds' => [$this->account->id],
            ],
            [
                'data' => $aclData,
            ],
            true
        );

        if (!$skipLogin) {
            $this->auth('tester', null, $this->portal->id);
        }

        $app = $this->createApplication();

        $injectableFactory = $app->getContainer()->get('injectableFactory');

        $this->factory = $injectableFactory->create(SelectBuilderFactory::class);

        $container = $app->getContainer();

        $this->user = $container->get('user');

        return $app;
    }

    public function testBuild1()
    {
        $app = $this->initTest(
            [
                'Account' => [
                    'read' => 'team',
                ],
            ]
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->id;

        $builder = $this->factory->create();

        $searchParams = SearchParams::fromRaw([
            'orderBy' => 'name',
            'order' => SearchParams::ORDER_DESC,
            'primaryFilter' => 'customers',
            'boolFilterList' => ['onlyMy'],
            'where' => [
                [
                    'type' => 'equals',
                    'attribute' => 'name',
                    'value' => 'test',
                ],
                [
                    'type' => 'before',
                    'attribute' => 'createdAt',
                    'value' => '2020-12-12 10:00',
                    'dateTime' => true,
                ],
            ],
        ]);

        $query = $builder
            ->from('Account')
            ->withStrictAccessControl()
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Account',
            'orderBy' => [
                [
                    'name',
                    'DESC',
                ],
                [
                    'id',
                    'DESC',
                ],
            ],
            'joins' => [],
            'leftJoins' => [
                [
                    'teams',
                    'teamsAccess',
                ],
            ],
            'distinct' => true,
            'whereClause' => [
                'OR' => [
                    [
                        'assignedUserId' => $userId,
                    ],
                ],
                [
                    'name=' => 'test',
                ],
                [
                    'createdAt<' => '2020-12-12 10:00:00',
                ],
                [
                    'OR' => [
                        'teamsAccess.id' => [],
                        'assignedUserId' => $userId,
                    ],
                ],
                'type' => 'Customer',
            ],
        ];

        $this->assertEquals($expected['from'], $raw['from']);
        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['orderBy'], $raw['orderBy']);
        $this->assertEquals($expected['leftJoins'], $raw['leftJoins']);

        $this->assertTrue($raw['distinct']);
    }

    public function testBuild2()
    {
        $app = $this->initTest(
            [
                'Meeting' => [
                    'read' => 'team',
                ],
            ]
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->id;

        $builder = $this->factory->create();

        $query = $builder
            ->from('Meeting')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Meeting',
            'joins' => [],
            'leftJoins' => [
                [
                    'teams',
                    'teamsAccess',
                ],
                [
                    'users',
                    'usersAccess',
                ],
            ],
            'distinct' => true,
            'whereClause' => [
                'OR' => [
                    ['teamsAccessMiddle.teamId=' => []],
                    ['usersAccessMiddle.userId=' => $userId],
                    ['assignedUserId=' => $userId],
                ],
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['leftJoins'], $raw['leftJoins']);

        $this->assertTrue($raw['distinct']);
    }

    public function testEmailAccessFilterOnlyOwn()
    {
        $app = $this->initTest(
            [
                'Email' => [
                    'read' => 'own',
                ],
            ]
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->id;

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'joins' => [],
            'leftJoins' => [
                [
                    'EmailUser',
                    'emailUser',
                    [
                        'emailUser.emailId:' => 'id',
                        'emailUser.deleted' => false,
                        'emailUser.userId' => $this->user->id,
                    ]
                ],
            ],
            'whereClause' => [
                'emailUser.userId' => $userId,
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['leftJoins'], $raw['leftJoins']);
    }

    public function testEmailAccessFilterOnlyTeam()
    {
        $app = $this->initTest(
            [
                'Email' => [
                    'read' => 'team',
                ],
            ]
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->id;

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'joins' => [],
            'leftJoins' => [
                [
                    'EmailUser',
                    'emailUser',
                    [
                        'emailUser.emailId:' => 'id',
                        'emailUser.deleted' => false,
                        'emailUser.userId' => $this->user->id,
                    ]
                ],
                [
                    'teams',
                    'teamsAccess',
                ],
            ],
            'whereClause' => [
                'OR' => [
                   'teamsAccessMiddle.teamId' => [],
                   'emailUser.userId' => $userId,
               ],
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['leftJoins'], $raw['leftJoins']);
    }

    public function testEmailAccessFilterOnlyAccount()
    {
        $this->initTestPortal(
            [
                'Email' => [
                    'read' => 'account',
                ],
            ]
        );

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'joins' => [],
            'leftJoins' => [
                [
                    'EmailUser',
                    'emailUser',
                    [
                        'emailUser.emailId:' => 'id',
                        'emailUser.deleted' => false,
                        'emailUser.userId' => $this->user->id,
                    ]
                ],
            ],
            'whereClause' => [
                'OR' => [
                    'emailUser.userId' => $this->user->id,
                    'accountId' => [$this->account->id],
                    [
                        'parentType' => 'Contact',
                        'parentId' => $this->contact->id,
                    ]
                ],
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['leftJoins'], $raw['leftJoins']);
    }

    public function testEmailAccessFilterOnlyContact()
    {
        $this->initTestPortal(
            [
                'Email' => [
                    'read' => 'contact',
                ],
            ]
        );

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'joins' => [],
            'leftJoins' => [
                [
                    'EmailUser',
                    'emailUser',
                    [
                        'emailUser.emailId:' => 'id',
                        'emailUser.deleted' => false,
                        'emailUser.userId' => $this->user->id,
                    ]
                ],
            ],
            'whereClause' => [
                'OR' => [
                    'emailUser.userId' => $this->user->id,
                    [
                        'parentType' => 'Contact',
                        'parentId' => $this->contact->id,
                    ]
                ],
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['leftJoins'], $raw['leftJoins']);
    }

    public function testBuildDefaultOrder()
    {
        $this->initTest(
            []
        );

        $searchParams = SearchParams::fromRaw([]);

        $builder = $this->factory->create();

        $query = $builder
            ->from('Meeting')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $expectedOrderBy = [
            ['dateStart', 'DESC'],
            ['id', 'DESC'],
        ];

        $this->assertEquals($expectedOrderBy, $raw['orderBy']);
    }

    public function testBuildMeetingDateTime()
    {
        $this->initTest(
            []
        );

        $searchParams = SearchParams::fromRaw([
            'where' => [
                [
                    'type' => 'on',
                    'attribute' => 'dateStart',
                    'value' => '2020-12-12',
                    'dateTime' => true,
                ],
            ],
        ]);

        $builder = $this->factory->create();

        $query = $builder
            ->from('Meeting')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                [
                    'dateStartDate=' => '2020-12-12',
                ],
                [
                    'AND' => [
                        [
                            'AND' => [
                                'dateStart>=' => '2020-12-12 00:00:00',
                                'dateStart<=' => '2020-12-12 23:59:59',
                            ],
                        ],
                        [
                            'dateStartDate=' => null,
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
    }

    public function testEmailInbox()
    {
        $app = $this->initTest(
            []
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->id;

        $emailAddressId = $this->createUserEmailAddress($container);

        $searchParams = SearchParams::fromRaw([
            'where' => [
                [
                    'type' => 'inFolder',
                    'attribute' => 'folderId',
                    'value' => 'inbox',
                ],
            ],
        ]);

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->withAdditionalApplierClassNameList([
                EmailAdditionalApplier::class,
            ])
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'emailUser.inTrash' => false,
            'emailUser.folderId' => null,
            'emailUser.userId' => $userId,
            [
                'status' => ['Archived', 'Sent'],
            ],
            'fromEmailAddressId!=' => [$emailAddressId],
            [
                'OR' => [
                    'status' => 'Archived',
                    'createdById!=' => $userId,
                ],
            ],
        ];

        $expectedLeftJoins = [
            [
                'EmailUser',
                'emailUser',
                [
                    'emailUser.emailId:' => 'id',
                    'emailUser.deleted' => false,
                    'emailUser.userId' => $this->user->id,
                ],
            ],
        ];

        $expectedSelect = [
            '*',
            ['emailUser.isRead', 'isRead'],
            ['emailUser.isImportant', 'isImportant'],
            ['emailUser.inTrash', 'inTrash'],
            ['emailUser.folderId', 'folderId'],
        ];

        $expectedUseIndex = ['dateSent'];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
        $this->assertEquals($expectedLeftJoins, $raw['leftJoins']);
        $this->assertEquals($expectedSelect, $raw['select']);
        $this->assertEquals($expectedUseIndex, $raw['useIndex']);
    }

    public function testEmailSent()
    {
        $app = $this->initTest(
            []
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->id;

        $emailAddressId = $this->createUserEmailAddress($container);

        $searchParams = SearchParams::fromRaw([
            'where' => [
                [
                    'type' => 'inFolder',
                    'attribute' => 'folderId',
                    'value' => 'sent',
                ],
            ],
        ]);

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                'fromEmailAddressId' => [$emailAddressId],
                [
                    'status' => 'Sent',
                    'createdById' => $userId,
                ]
            ],
            [
                'status!=' => 'Draft',
            ],
            'emailUser.inTrash' => false,
        ];

        $expectedLeftJoins = [
            [
                'EmailUser',
                'emailUser',
                [
                    'emailUser.emailId:' => 'id',
                    'emailUser.deleted' => false,
                    'emailUser.userId' => $this->user->id,
                ],
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
        $this->assertEquals($expectedLeftJoins, $raw['leftJoins']);
    }

    public function testEmailEmailAddressEquals()
    {
        $app = $this->initTest(
            []
        );

        $em = $app->getContainer()->get('entityManager');

        $emailAddress = $em->createEntity('EmailAddress', [
           'name' => 'test@test.com',
        ]);

        $searchParams = SearchParams::fromRaw([
            'where' => [
                [
                    'type' => 'equals',
                    'attribute' => 'emailAddress',
                    'value' => 'test@test.com',
                ],
            ],
        ]);

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $this->assertEquals($emailAddress->id, $raw['whereClause']['OR']['fromEmailAddressId']);
    }

    public function testEmailFromEquals()
    {
        $app = $this->initTest();

        $em = $app->getContainer()->get('entityManager');

        $emailAddress = $em->createEntity('EmailAddress', [
           'name' => 'test@test.com',
        ]);

        $searchParams = SearchParams::fromRaw([
            'where' => [
                [
                    'type' => 'equals',
                    'attribute' => 'from',
                    'value' => 'test@test.com',
                ],
            ],
        ]);

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'fromEmailAddressId' => $emailAddress->id,
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
    }

    public function testEmailBoolOnlyMy()
    {
        $this->initTest();

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withBoolFilter('onlyMy')
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                [
                    'emailUser.userId' => $this->user->id,
                ],
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
    }

    public function testEmailTextSearch1()
    {
        $app = $this->initTest();

        $emailAddressId = $this->createUserEmailAddress($app->getContainer());

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withTextFilter('test@test.com')
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                ['fromEmailAddressId=' => $emailAddressId],
                ['emailEmailAddress.emailAddressId=' => $emailAddressId],
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
    }

    protected function createUserEmailAddress(Container $container) : string
    {
        $userId = $container->get('user')->id;

        $em = $container->get('entityManager');

        $user = $em->getEntity('User', $userId);

        $emailAddress = $em->createEntity('EmailAddress', [
            'name' => 'test@test.com',
        ]);

        $em
            ->getRepository('User')
            ->getRelation($user, 'emailAddresses')
            ->relate($emailAddress);

        return $emailAddress->id;
    }
}
