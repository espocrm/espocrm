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

namespace tests\integration\Espo\Core\Select;

use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Classes\Select\Email\AdditionalAppliers\Main as EmailAdditionalApplier;
use Espo\Core\Select\Where\Item\Type;
use Espo\Core\Select\Where\ItemBuilder;
use Espo\Entities\User;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Join\JoinType;
use Espo\ORM\Query\Select;
use tests\integration\Core\BaseTestCase;

class SelectBuilderTest extends BaseTestCase
{
    /**
     * @var SelectBuilderFactory
     */
    private $factory;

    private $user;
    private $contact;
    private $account;

    protected function setUp(): void
    {
        parent::setUp();

        $injectableFactory = $this->getContainer()->getByClass(InjectableFactory::class);

        $this->factory = $injectableFactory->create(SelectBuilderFactory::class);
    }

    protected function initTest(array $aclData = [], bool $skipLogin = false) : Application
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
        $portal = $em->createEntity('Portal', [
            'name' => 'Portal',
        ]);

        $this->createUser(
            [
                'userName' => 'tester',
                'portalsIds' => [$portal->getId()],
                'contactId' => $this->contact->getId(),
                'accountsIds' => [$this->account->getId()],
            ],
            [
                'data' => $aclData,
            ],
            true
        );

        if (!$skipLogin) {
            $this->auth('tester', null, $portal->getId());
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

        /** @noinspection PhpUnhandledExceptionInspection */
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
                    'joins' => [
                        [
                            'EntityTeam',
                            'entityTeam',
                            [
                                'entityTeam.entityId:' => 'id',
                                'entityTeam.entityType' => 'Account',
                                'entityTeam.deleted' => false,
                            ],
                            ['type' => JoinType::left]
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

        /** @noinspection PhpUnhandledExceptionInspection */
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
                        'joins' =>
                            [
                                0 =>
                                    [
                                        'EntityTeam',
                                        'entityTeam',
                                            [
                                                'entityTeam.entityId:' => 'id',
                                                'entityTeam.entityType' => 'Meeting',
                                                'entityTeam.deleted' => false,
                                            ],
                                        ['type' => JoinType::left]
                                    ],
                                1 =>
                                    [
                                        'MeetingUser',
                                        'usersMiddle',
                                        [
                                            'usersMiddle.meetingId:' => 'id',
                                            'usersMiddle.deleted' => false,
                                        ],
                                        ['type' => JoinType::left]
                                    ],
                            ],
                        'whereClause' =>
                            [
                                'OR' =>
                                    [

                                            [
                                                'entityTeam.teamId=' => [],
                                            ],

                                            [
                                                'usersMiddle.userId=' => $userId,
                                            ],

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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        $expected = [
            'from' => 'Email',
            'joins' =>
                [
                        [
                            'EmailUser',
                            Email::ALIAS_INBOX,
                            [
                                Email::ALIAS_INBOX . '.emailId:' => 'id',
                                Email::ALIAS_INBOX . '.deleted' => false,
                                Email::ALIAS_INBOX . '.userId' => $userId,
                            ],
                            ['type' => JoinType::left]
                        ],
                ],
            'whereClause' =>
                [
                    Email::ALIAS_INBOX . '.userId' => $userId,
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

        /** @noinspection PhpUnhandledExceptionInspection */
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
                            'joins' =>
                                [
                                    0 =>
                                        [
                                            'EntityTeam',
                                            'entityTeam',
                                                [
                                                    'entityTeam.entityId:' => 'id',
                                                    'entityTeam.entityType' => 'Email',
                                                    'entityTeam.deleted' => false,
                                                ],
                                            ['type' => JoinType::left]
                                        ],
                                    1 =>
                                        [
                                            'EmailUser',
                                            Email::ALIAS_INBOX,
                                                [
                                                    Email::ALIAS_INBOX . '.emailId:' => 'id',
                                                    Email::ALIAS_INBOX . '.deleted' => false,
                                                    Email::ALIAS_INBOX . '.userId' => $userId,
                                                ],
                                            ['type' => JoinType::left]
                                        ],
                                ],
                            'whereClause' =>
                                [
                                    'OR' =>
                                        [
                                            'entityTeam.teamId' => [],
                                            Email::ALIAS_INBOX . '.userId' => $userId,
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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        /** @noinspection PhpArrayWriteIsNotUsedInspection */
        $expected = [
            'from' => 'Email',
            'joins' => [
                [
                    'EmailUser',
                    Email::ALIAS_INBOX,
                    [
                        Email::ALIAS_INBOX . '.emailId:' => 'id',
                        Email::ALIAS_INBOX . '.deleted' => false,
                        Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                    ],
                    ['type' => JoinType::left]
                ],
            ],
            'whereClause' => [
                'OR' => [
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                    'accountId' => [$this->account->getId()],
                    [
                        'parentType' => 'Contact',
                        'parentId' => $this->contact->getId(),
                    ]
                ],
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['joins'], $raw['joins']);
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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withStrictAccessControl()
            ->build();

        $raw = $query->getRaw();

        /** @noinspection PhpArrayWriteIsNotUsedInspection */
        $expected = [
            'from' => 'Email',
            'joins' => [
                [
                    'EmailUser',
                    Email::ALIAS_INBOX,
                    [
                        Email::ALIAS_INBOX . '.emailId:' => 'id',
                        Email::ALIAS_INBOX . '.deleted' => false,
                        Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                    ],
                    ['type' => JoinType::left]
                ],
            ],
            'whereClause' => [
                'OR' => [
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                    [
                        'parentType' => 'Contact',
                        'parentId' => $this->contact->getId(),
                    ]
                ],
            ],
        ];

        $this->assertEquals($expected['whereClause'], $raw['whereClause']);
        $this->assertEquals($expected['joins'], $raw['joins']);
    }

    public function testBuildDefaultOrder()
    {
        $this->initTest();

        $searchParams = SearchParams::fromRaw([]);

        $builder = $this->factory->create();

        /** @noinspection PhpUnhandledExceptionInspection */
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
        $this->initTest();

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

        /** @noinspection PhpUnhandledExceptionInspection */
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
        $app = $this->initTest();

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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->withAdditionalApplierClassNameList([
                EmailAdditionalApplier::class,
            ])
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            Email::ALIAS_INBOX . '.inTrash' => false,
            Email::ALIAS_INBOX . '.inArchive' => false,
            Email::ALIAS_INBOX . '.folderId' => null,
            Email::ALIAS_INBOX . '.userId' => $userId,
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

        $joins = [
            [
                'EmailUser',
                Email::ALIAS_INBOX,
                [
                    Email::ALIAS_INBOX . '.emailId:' => 'id',
                    Email::ALIAS_INBOX . '.deleted' => false,
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                ],
                ['type' => JoinType::left]
            ],
        ];

        $expectedSelect = [
            '*',
            [Email::ALIAS_INBOX . '.isRead', 'isRead'],
            [Email::ALIAS_INBOX . '.isImportant', 'isImportant'],
            [Email::ALIAS_INBOX . '.inTrash', 'inTrash'],
            [Email::ALIAS_INBOX . '.inArchive', 'inArchive'],
            [Email::ALIAS_INBOX . '.folderId', 'folderId'],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
        $this->assertEquals($joins, $raw['joins']);
        $this->assertEquals($expectedSelect, $raw['select']);
    }

    public function testEmailSent()
    {
        $app = $this->initTest();

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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                'fromEmailAddressId' => [$emailAddressId],
                [
                    'status' => Email::STATUS_SENT,
                    'createdById' => $userId,
                ]
            ],
            [
                'status!=' => Email::STATUS_DRAFT,
            ],
            Email::ALIAS_INBOX . '.inTrash' => false,
            [
                'OR' => [
                    'groupFolderId' => null,
                    'groupFolderId!=' => Email::GROUP_STATUS_FOLDER_TRASH,
                ]
            ]
        ];

        $expectedJoins = [
            [
                'EmailUser',
                Email::ALIAS_INBOX,
                [
                    Email::ALIAS_INBOX . '.emailId:' => 'id',
                    Email::ALIAS_INBOX . '.deleted' => false,
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                ],
                ['type' => JoinType::left]
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
        $this->assertEquals($expectedJoins, $raw['joins']);
    }

    public function testEmailEmailAddressEquals()
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
                    'attribute' => 'emailAddress',
                    'value' => 'test@test.com',
                ],
            ],
        ]);

        $builder = $this->factory->create();

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withSearchParams($searchParams)
            ->build();

        $raw = $query->getRaw();

        $this->assertEquals($emailAddress->getId(), $raw['whereClause']['OR'][0]['fromEmailAddressId=']);
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

        /** @noinspection PhpUnhandledExceptionInspection */
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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withBoolFilter('onlyMy')
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                [
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
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

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from('Email')
            ->withTextFilter('test@test.com')
            ->build();

        $raw = $query->getRaw();

        $expectedWhereClause = [
            'OR' => [
                [
                    'fromEmailAddressId=' => $emailAddressId,
                ],
                [
                    'EXISTS' => Select::fromRaw([
                        'from' => 'EmailEmailAddress',
                        'fromAlias' => 'sq',
                        'whereClause' => [
                            'sq.emailId=:' => 'email.id',
                            'emailAddressId' => $emailAddressId,
                        ],
                    ]),
                ],
            ],
        ];

        $this->assertEquals($expectedWhereClause, $raw['whereClause']);
    }

    public function testWhereMany(): void
    {
        $em = $this->getEntityManager();

        $account1 = $em->createEntity(Account::ENTITY_TYPE);
        $account2 = $em->createEntity(Account::ENTITY_TYPE);

        $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account1->getId(),
            'stage' => Opportunity::STAGE_CLOSED_WON,
        ]);

        $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account1->getId(),
            'stage' => Opportunity::STAGE_CLOSED_WON,
        ]);

        $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account1->getId(),
            'stage' => Opportunity::STAGE_CLOSED_LOST,
        ]);

        $em->createEntity(Opportunity::ENTITY_TYPE, [
            'accountId' => $account2->getId(),
            'stage' => Opportunity::STAGE_CLOSED_LOST,
        ]);

        $factory = $this->getInjectableFactory()->create(SelectBuilderFactory::class);

        $builder = $factory->create();

        /** @noinspection PhpUnhandledExceptionInspection */
        $query = $builder
            ->from(Account::ENTITY_TYPE)
            ->withWhere(
                ItemBuilder::create()
                    ->setAttribute('opportunities.stage')
                    ->setType(Type::EQUALS)
                    ->setValue(Opportunity::STAGE_CLOSED_WON)
                    ->build()
            )
            ->build();

        $this->assertFalse($query->isDistinct());
        $this->assertFalse(in_array('opportunities', $query->getLeftJoins()));
        $this->assertArrayHasKey('id=s', $query->getWhere()->getRaw());

        $accounts = $em->getRDBRepositoryByClass(Account::class)
            ->clone($query)
            ->find();

        $this->assertCount(1, $accounts);
        $this->assertEquals($account1->getId(), $accounts[0]->getId());
    }

    protected function createUserEmailAddress(Container $container) : string
    {
        $userId = $container->getByClass(User::class)->getId();

        $em = $container->getByClass(EntityManager::class);

        $user = $em->getEntityById('User', $userId);

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
