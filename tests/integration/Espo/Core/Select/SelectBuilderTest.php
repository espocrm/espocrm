<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Core\Select;

use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;

use Espo\Classes\Select\Email\AdditionalAppliers\Main as EmailAdditionalApplier;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Select;
use tests\integration\Core\BaseTestCase;

class SelectBuilderTest extends BaseTestCase
{
    /**
     * @var SelectBuilderFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $injectableFactory = $this->getContainer()->getByClass(InjectableFactory::class);

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

        $injectableFactory = $app->getContainer()->getByClass(InjectableFactory::class);

        $this->factory = $injectableFactory->create(SelectBuilderFactory::class);

        $this->user = $app->getContainer()->getByClass(User::class);

        return $app;
    }

    protected function initTestPortal(array $aclData = [], bool $skipLogin = false) : Application
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->getByClass(EntityManager::class);

        $this->contact = $em->createEntity('Contact', []);
        $this->account = $em->createEntity('Account', []);
        $this->portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser(
            [
                'userName' => 'tester',
                'portalsIds' => [$this->portal->getId()],
                'contactId' => $this->contact->getId(),
                'accountsIds' => [$this->account->getId()],
            ],
            [
                'data' => $aclData,
            ],
            true
        );

        if (!$skipLogin) {
            $this->auth('tester', null, $this->portal->getId());
        }

        $app = $this->createApplication();

        $injectableFactory = $app->getContainer()->getByClass(InjectableFactory::class);

        $this->factory = $injectableFactory->create(SelectBuilderFactory::class);

        $container = $app->getContainer();

        $this->user = $container->getByClass(User::class);

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

        $userId = $container->getByClass(User::class)->getId();

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
            'whereClause' => [
                'id=s' => Select::fromRaw([
                    'select' => [
                        'id',
                    ],
                    'from' => 'Account',
                    'leftJoins' => [
                        [
                            'EntityTeam',
                            'entityTeam',
                            [
                                'entityTeam.entityId:' => 'id',
                                'entityTeam.entityType' => 'Account',
                                'entityTeam.deleted' => false,
                            ],
                        ],
                    ],
                    'whereClause' => [
                        'OR' =>
                            [
                                'entityTeam.teamId' => [],
                                'assignedUserId' => $userId,
                            ],
                    ],
                ]),
                'OR' => [
                    'assignedUserId=' => $userId,
                ],
                'type' => 'Customer',
                [
                    'name=' => 'test',
                ],
                [
                    'createdAt<' => '2020-12-12 10:00:00',
                ],
            ],
        ];

        $this->assertEquals($expected, $raw);
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

        $userId = $container->getByClass(User::class)->getId();

        $builder = $this->factory->create();

        $query = $builder
            ->from('Meeting')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Meeting',
            'whereClause' =>
                [
                    'id=s' => Select::fromRaw([
                        'select' =>
                            [
                                0 => 'id',
                            ],
                        'from' => 'Meeting',
                        'leftJoins' =>
                            [
                                0 =>
                                    [
                                        0 => 'EntityTeam',
                                        1 => 'entityTeam',
                                        2 =>
                                            [
                                                'entityTeam.entityId:' => 'id',
                                                'entityTeam.entityType' => 'Meeting',
                                                'entityTeam.deleted' => false,
                                            ],
                                    ],
                                1 =>
                                    [
                                        0 => 'MeetingUser',
                                        1 => 'usersMiddle',
                                        2 =>
                                            [
                                                'usersMiddle.meetingId:' => 'id',
                                                'usersMiddle.deleted' => false,
                                            ],
                                    ],
                            ],
                        'whereClause' =>
                            [
                                'OR' =>
                                    [
                                        0 =>
                                            [
                                                'entityTeam.teamId=' => [],
                                            ],
                                        1 =>
                                            [
                                                'usersMiddle.userId=' => $userId,
                                            ],
                                        2 =>
                                            [
                                                'assignedUserId=' => $userId,
                                            ],
                                    ],
                            ],
                    ]),
                ],
        ];

        $this->assertEquals($expected, $raw);
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

        $userId = $container->getByClass(User::class)->getId();

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'leftJoins' =>
                [
                    0 =>
                        [
                            0 => 'EmailUser',
                            1 => 'emailUser',
                            2 =>
                                [
                                    'emailUser.emailId:' => 'id',
                                    'emailUser.deleted' => false,
                                    'emailUser.userId' => $userId,
                                ],
                        ],
                ],
            'whereClause' =>
                [
                    'emailUser.userId' => $userId,
                ]
        ];

        $this->assertEquals($expected, $raw);
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

        $userId = $container->getByClass(User::class)->getId();

        $builder = $this->factory->create();

        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'whereClause' =>
                [
                    'id=s' =>
                        Select::fromRaw([
                            'select' =>
                                [
                                    0 => 'id',
                                ],
                            'from' => 'Email',
                            'leftJoins' =>
                                [
                                    0 =>
                                        [
                                            0 => 'EntityTeam',
                                            1 => 'entityTeam',
                                            2 =>
                                                [
                                                    'entityTeam.entityId:' => 'id',
                                                    'entityTeam.entityType' => 'Email',
                                                    'entityTeam.deleted' => false,
                                                ],
                                        ],
                                    1 =>
                                        [
                                            0 => 'EmailUser',
                                            1 => 'emailUser',
                                            2 =>
                                                [
                                                    'emailUser.emailId:' => 'id',
                                                    'emailUser.deleted' => false,
                                                    'emailUser.userId' => $userId,
                                                ],
                                        ],
                                ],
                            'whereClause' =>
                                [
                                    'OR' =>
                                        [
                                            'entityTeam.teamId' => [],
                                            'emailUser.userId' => $userId,
                                        ],
                                ],
                        ]),
                ],
        ];

        $this->assertEquals($expected, $raw);
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
                        'emailUser.userId' => $this->user->getId(),
                    ]
                ],
            ],
            'whereClause' => [
                'OR' => [
                    'emailUser.userId' => $this->user->getId(),
                    'accountId' => [$this->account->getId()],
                    [
                        'parentType' => 'Contact',
                        'parentId' => $this->contact->getId(),
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
                        'emailUser.userId' => $this->user->getId(),
                    ]
                ],
            ],
            'whereClause' => [
                'OR' => [
                    'emailUser.userId' => $this->user->getId(),
                    [
                        'parentType' => 'Contact',
                        'parentId' => $this->contact->getId(),
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

        $userId = $container->getByClass(User::class)->getId();

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
                'groupFolderId' => null,
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
                    'emailUser.userId' => $this->user->getId(),
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

        $userId = $container->getByClass(User::class)->getId();

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
                    'emailUser.userId' => $this->user->getId(),
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

        $em = $app->getContainer()->getByClass(EntityManager::class);

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

        $this->assertEquals($emailAddress->getId(), $raw['whereClause']['OR']['fromEmailAddressId']);
    }

    public function testEmailFromEquals()
    {
        $app = $this->initTest();

        $em = $app->getContainer()->getByClass(EntityManager::class);

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
            'fromEmailAddressId' => $emailAddress->getId(),
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
                    'emailUser.userId' => $this->user->getId(),
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
                ['NOT_EQUAL:(MATCH_NATURAL_LANGUAGE:(name, bodyPlain, body, \'test@test.com\'), 0):' => null],
                ['fromEmailAddressId=' => $emailAddressId],
                ['emailEmailAddress.emailAddressId=' => $emailAddressId],
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
    }

    protected function createUserEmailAddress(Container $container) : string
    {
        $userId = $container->getByClass(User::class)->getId();

        $em = $container->getByClass(EntityManager::class);

        $user = $em->getEntity('User', $userId);

        $emailAddress = $em->createEntity('EmailAddress', [
            'name' => 'test@test.com',
        ]);

        $em
            ->getRDBRepository('User')
            ->getRelation($user, 'emailAddresses')
            ->relate($emailAddress);

        return $emailAddress->getId();
    }
}
