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

namespace tests\unit\Espo\Core\Mail;

use Espo\Entities\Email;
use Espo\Entities\EmailFilter;
use Espo\Core\Mail\FiltersMatcher;
use Espo\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class FiltersMatcherTest extends TestCase
{
    private $object;
    private $entityManager;
    private $emailDefs;
    private $filterDefs;

    protected function setUp() : void
    {
        $this->object = new FiltersMatcher();
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->emailDefs = [
            'attributes' => [
                'from' => [
                    'type' => 'varchar'
                ],
                'to' => [
                    'type' => 'varchar'
                ],
                'name' => [
                    'type' => 'varchar'
                ],
                'subject' => [
                    'type' => 'varchar'
                ],
                'body' => [
                    'type' => 'text'
                ],
                'bodyPlain' => [
                    'type' => 'text'
                ]
            ]
        ];

        $this->filterDefs = [
            'attributes' => [
                'from' => [
                    'type' => 'varchar'
                ],
                'to' => [
                    'type' => 'varchar'
                ],
                'subject' => [
                    'type' => 'varchar'
                ],
                'bodyContains' => [
                    'type' => 'jsonArray'
                ],
                'bodyContainsAll' => [
                    'type' => 'jsonArray'
                ],
            ]
        ];
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }

    protected function createEntity(string $entityType, string $className, array $defs)
    {
        return new $className($entityType, $defs, $this->entityManager);
    }

    public function testMatch(): void
    {
        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('from', 'test@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $filter->set([
            'from' => 'test@tester'
        ]);

        $filterList = [$filter];

        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('from', 'test@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $filter->set([
            'from' => '*@tester'
        ]);

        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('from', 'test@tester');
        $email->set('to', 'test@tester;baraka@tester');

        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $filter->set([
            'to' => 'baraka@tester'
        ]);

        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('from', 'test@tester');
        $email->set('to', 'test@tester;baraka@man');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'to' => '*@tester'
        ]);
        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('subject', 'test hello man');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $filter->set([
            'subject' => '*hello*'
        ]);

        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('name', 'test hello man');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'subject' => 'hello'
        ]);
        $filterList = [$filter];
        $this->assertNull($this->object->findMatch($email, $filterList));


        $email->set('name', 'test hello man');
        $email->set('from', 'test@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'subject' => '*hello*',
            'from' => 'test@tester'
        ]);
        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('name', 'test hello man');
        $email->set('from', 'hello@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'subject' => '*hello*',
            'from' => 'test@tester'
        ]);
        $filterList = [$filter];
        $this->assertNull($this->object->findMatch($email, $filterList));


        $email->set('name', 'test hello man');
        $email->set('body', 'one hello three');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'subject' => 'test hello man',
            'bodyContains' => ['hello']
        ]);
        $filterList = [$filter];
        $this->assertNull($this->object->findMatch($email, $filterList, true));

        $email->set('name', 'test hello man');
        $email->set('body', 'one hello three');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'subject' => 'test hello man',
            'bodyContains' => ['hello']
        ]);
        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('name', 'Access information to the EspoCRM cloud');
        $email->set('from', 'no-reply@test.com');
        $email->set('to', 'info@test.com');

        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $filter->set([
            'subject' => 'Access information to the EspoCRM cloud',
            'from' => 'no-reply@test.com',
            'to' => 'info@test.com'
        ]);

        $this->assertTrue($this->object->match($email, $filter));

        $email->set('body', 'test hello');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'bodyContainsAll' => ['test', 'hello'],
        ]);
        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email->set('body', 'test');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'bodyContainsAll' => ['test', 'hello'],
        ]);
        $filterList = [$filter];
        $this->assertNull($this->object->findMatch($email, $filterList));

        $email->set('body', 'test hello one');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'bodyContains' => ['test', 'one'],
            'bodyContainsAll' => ['test', 'hello'],
        ]);
        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));
    }

    public function testMatchBody()
    {
        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('body', 'hello Man tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'bodyContains' => ['man', 'red']
        ]);
        $filterList = [$filter];
        $this->assertNotNull($this->object->findMatch($email, $filterList));

        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('body', 'hello Man tester');
        $email->set('from', 'hello@test');

        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set([
            'bodyContains' => ['man', 'red'],
            'from' => 'test@tester'
        ]);
        $filterList = [$filter];
        $this->assertNull($this->object->findMatch($email, $filterList));
    }

    public function testMatchEmpty(): void
    {
        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $email->set([
            'name' => 'Test',
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'body' => 'Test',
        ]);

        $filter->set([
            'subject' => '',
            'bodyContains' => [''],
            'from' => '',
            'to' => '',
        ]);

        $this->assertNull($this->object->findMatch($email, [$filter]));
    }
}
