<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\ORM\DB\MysqlMapper;
use Espo\ORM\DB\Query\Mysql as Query;
use Espo\ORM\EntityFactory;


require_once 'tests/unit/testData/DB/Entities.php';

class EntityTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }


    public function testIsAttributeChanged()
    {
        $job = new \Espo\Entities\Job();
        $job->setFetched('string', 'test');
        $this->assertFalse($job->isAttributeChanged('string'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('string', 'test');
        $job->set('string', 'hello');
        $this->assertTrue($job->isAttributeChanged('string'));

        $job = new \Espo\Entities\Job();
        $job->set('string', 'hello');
        $this->assertTrue($job->isAttributeChanged('string'));

        $job = new \Espo\Entities\Job();
        $job->set('string', null);
        $this->assertTrue($job->isAttributeChanged('string'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['2', '1']);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['1', '2']);
        $this->assertFalse($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['1', 2]);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', [
            (object) ['1' => 'v1']
        ]);
        $job->set('array', [
            (object) ['1' => 'v1']
        ]);
        $this->assertFalse($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', [
            (object) ['k1' => 'v1']
        ]);
        $job->set('array', [
            (object) ['k1' => 'v2']
        ]);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', [
            (object) ['k1' => 'v1']
        ]);
        $job->set('array', [
            (object) ['k1' => 'v1', 'k2' => 'v2'],
        ]);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', ['1', '2']);
        $job->set('array', ['1', '2', '3']);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->set('array', ['1', '2', '3']);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->set('array', null);
        $this->assertTrue($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('array', null);
        $this->assertFalse($job->isAttributeChanged('array'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', ['2', '1']);
        $this->assertFalse($job->isAttributeChanged('arrayUnordered'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', ['1', '2']);
        $this->assertFalse($job->isAttributeChanged('arrayUnordered'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', ['1', '2', '3']);
        $this->assertTrue($job->isAttributeChanged('arrayUnordered'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('arrayUnordered', ['1', '2']);
        $job->set('arrayUnordered', null);
        $this->assertTrue($job->isAttributeChanged('arrayUnordered'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('object', (object) ['1' => 'value-1']);
        $job->set('object', (object) ['1' => 'value-1']);
        $this->assertFalse($job->isAttributeChanged('object'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('object', (object) ['1' => 'value-1']);
        $job->set('object', ['1' => 'value-1']);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('object', (object) ['1' => '1']);
        $job->set('object', (object) ['1' => 1]);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('object', (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ]);
        $job->set('object', (object) [
            'k1' => (object) [
                'k11' => 'v2'
            ]
        ]);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('object', (object) [
            'k1' => [
                'k11' => 'v1'
            ]
        ]);
        $job->set('object', (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ]);
        $this->assertTrue($job->isAttributeChanged('object'));

        $job = new \Espo\Entities\Job();
        $job->setFetched('object', [
            'k1' => [
                'k11' => 'v1'
            ]
        ]);
        $job->set('object', (object) [
            'k1' => (object) [
                'k11' => 'v1'
            ]
        ]);
        $this->assertTrue($job->isAttributeChanged('object'));
    }

}
