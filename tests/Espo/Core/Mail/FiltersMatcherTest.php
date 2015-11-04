<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\Espo\Core\Mail;

use tests\ReflectionHelper;

class FiltersMatcherTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected function setUp()
    {
        $this->object = new \Espo\Core\Mail\FiltersMatcher();

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

    protected function tearDown()
    {
        $this->object = NULL;
    }

    function testMatch()
    {
        $email = new \Espo\Entities\Email($this->emailDefs);
        $email->set('from', 'test@tester');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'from' => 'test@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email = new \Espo\Entities\Email($this->emailDefs);
        $email->set('from', 'test@tester');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'from' => '*@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('from', 'test@tester');
        $email->set('to', 'test@tester;baraka@tester');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'to' => 'baraka@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('from', 'test@tester');
        $email->set('to', 'test@tester;baraka@man');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'to' => '*@tester'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('subject', 'test hello man');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'subject' => '*hello*'
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->match($email, $filterList));

        $email->set('name', 'test hello man');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'subject' => 'hello'
        ));
        $filterList = [$filter];
        $this->assertFalse($this->object->match($email, $filterList));

    }

    function testMatchBody()
    {
        $email = new \Espo\Entities\Email($this->emailDefs);
        $email->set('body', 'hello Man tester');
        $filter = new \Espo\Entities\EmailFilter($this->filterDefs);
        $filter->set(array(
            'bodyContains' => ['man', 'red']
        ));
        $filterList = [$filter];
        $this->assertTrue($this->object->matchBody($email, $filterList));
    }

}
