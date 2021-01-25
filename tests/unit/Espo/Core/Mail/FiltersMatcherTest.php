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

namespace tests\unit\Espo\Core\Mail;

use tests\unit\ReflectionHelper;

use Espo\Entities\Email;
use Espo\Entities\EmailFilter;

class FiltersMatcherTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected function setUp() : void
    {
        $this->object = new \Espo\Core\Mail\FiltersMatcher();

        $this->entityManager =
            $this->getMockBuilder('Espo\\Core\\ORM\\EntityManager')->disableOriginalConstructor()->getMock();

        $this->emailDefs = array(
            'fields' => array(
                'from' => array(
                    'type' => 'varchar'
                ),
                'to' => array(
                    'type' => 'varchar'
                ),
                'name' => array(
                    'type' => 'varchar'
                ),
                'subject' => array(
                    'type' => 'varchar'
                ),
                'body' => array(
                    'type' => 'text'
                ),
                'bodyPlain' => array(
                    'type' => 'text'
                )
            )
        );

        $this->filterDefs = array(
            'fields' => array(
                'from' => array(
                    'type' => 'varchar'
                ),
                'to' => array(
                    'type' => 'varchar'
                ),
                'subject' => array(
                    'type' => 'varchar'
                ),
                'bodyContains' => array(
                    'type' => 'jsonArray'
                )
            )
        );
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }

    protected function createEntity(string $entityType, string $className, array $defs)
    {
        return new $className($entityType, $defs, $this->entityManager);
    }

    function testMatch()
    {
        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('from', 'test@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);

        $filter->set(array(
            'from' => 'test@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('from', 'test@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'from' => '*@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('from', 'test@tester');
        $email->set('to', 'test@tester;baraka@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'to' => 'baraka@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('from', 'test@tester');
        $email->set('to', 'test@tester;baraka@man');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'to' => '*@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('subject', 'test hello man');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => '*hello*'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('name', 'test hello man');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => 'hello'
        ));
        $filterList = [$filter];
        $this->assertFalse($this->object->match($email, $filterList));


        $email->set('name', 'test hello man');
        $email->set('from', 'test@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => '*hello*',
            'from' => 'test@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('name', 'test hello man');
        $email->set('from', 'hello@tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => '*hello*',
            'from' => 'test@tester'
        ));
        $filterList = [$filter];
        $this->assertFalse($this->object->match($email, $filterList));


        $email->set('name', 'test hello man');
        $email->set('body', 'one hello three');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => 'test hello man',
            'bodyContains' => ['hello']
        ));
        $filterList = [$filter];
        $this->assertFalse($this->object->match($email, $filterList, true));

        $email->set('name', 'test hello man');
        $email->set('body', 'one hello three');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => 'test hello man',
            'bodyContains' => ['hello']
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));


        $email->set('name', 'Access information to the EspoCRM cloud');
        $email->set('from', 'no-reply@test.com');
        $email->set('to', 'info@test.com');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'subject' => 'Access information to the EspoCRM cloud',
            'from' => 'no-reply@test.com',
            'to' => 'info@test.com'
        ));
        $this->assertTrue($this->object->match($email, $filter));
    }

    function testMatchBody()
    {
        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('body', 'hello Man tester');
        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'bodyContains' => ['man', 'red']
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email = $this->createEntity('Email', Email::class, $this->emailDefs);
        $email->set('body', 'hello Man tester');
        $email->set('from', 'hello@test');

        $filter = $this->createEntity('EmailFilter', EmailFilter::class, $this->filterDefs);
        $filter->set(array(
            'bodyContains' => ['man', 'red'],
            'from' => 'test@tester'
        ));
        $filterList = [$filter];
        $this->assertFalse($this->object->match($email, $filterList));
    }

}
