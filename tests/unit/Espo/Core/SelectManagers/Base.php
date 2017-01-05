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

namespace tests\unit\Espo\Core\SelectManagers;

use \tests\unit\testData\Entities\Test2;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $entity = $this->entity = new Test2();

        $this->config = $this->getMockBuilder('\\Espo\\Core\\Utils\\Config')->disableOriginalConstructor()->getMock();
        $this->acl = $this->getMockBuilder('\\Espo\\Core\\Acl')->disableOriginalConstructor()->getMock();
        $this->aclManager = $this->getMockBuilder('\\Espo\\Core\\AclManager')->disableOriginalConstructor()->getMock();
        $this->metadata = $this->getMockBuilder('\\Espo\\Core\\Utils\\Metadata')->disableOriginalConstructor()->getMock();
        $this->entityManager = $this->getMockBuilder('\\Espo\\Core\\ORM\\EntityManager')->disableOriginalConstructor()->getMock();
        $this->user = $this->getMockBuilder('\\Espo\\Entities\\User')->disableOriginalConstructor()->getMock();

        $this->entityManager
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('Test2'))
            ->will($this->returnValue($this->entity));

        $preferences = $this->preferences = $this->getMockBuilder('\\Espo\\Entities\\Preferences')->disableOriginalConstructor()->getMock();
        $preferences
            ->expects($this->any())
            ->method('get')
            ->with($this->equalTo('timeZone'))
            ->will($this->returnValue('UTC'));

        $this->entityManager
            ->expects($this->any())
            ->method('getEntity')
            ->will($this->returnCallback(function ($entityType, $id) use ($preferences, $entity) {
                if ($entityType === 'Preferences') {
                    return $preferences;
                }
                if ($entityType === 'Test2') {
                    return $entity;
                }
            }));

        $this->user->id = 'test_id';
    }

    protected function tearDown()
    {
        unset($this->entity);
        unset($this->acl);
        unset($this->aclManager);
        unset($this->metadata);
        unset($this->entityManager);
        unset($this->user);
        unset($this->preferences);
    }

    function testTestEmptySelectParams()
    {
        $selectManager = new \Espo\Core\SelectManagers\Base($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType('Test2');

        $selectParams = $selectManager->getEmptySelectParams();

        $this->assertArrayHasKey('joins', $selectParams);
        $this->assertArrayHasKey('leftJoins', $selectParams);
        $this->assertArrayHasKey('whereClause', $selectParams);
        $this->assertArrayHasKey('customJoin', $selectParams);
    }

    function testGetEmptySelectParams()
    {
        $selectManager = new \Espo\Core\SelectManagers\Base($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType('Test2');

        $selectParams = $selectManager->getEmptySelectParams();

        $this->assertArrayHasKey('joins', $selectParams);
        $this->assertArrayHasKey('leftJoins', $selectParams);
        $this->assertArrayHasKey('whereClause', $selectParams);
        $this->assertArrayHasKey('customJoin', $selectParams);
    }

    function testAccessOnlyOwn()
    {
        $selectManager = new \Espo\Core\SelectManagers\Base($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType('Test2');

        $this->user
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($name) {
                if ($name === 'portalId') {
                    return null;
                }
            }));

        $this->user
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $this->acl
            ->expects($this->any())
            ->method('checkReadOnlyOwn')
            ->will($this->returnValue(true));

        $selectParams = $selectManager->getEmptySelectParams();
        $selectManager->applyAccess($selectParams);

        $this->assertArrayHasKey('whereClause', $selectParams);

        $this->assertArrayHasKey(0, $selectParams['whereClause']);
        $this->assertArrayHasKey('assignedUserId', $selectParams['whereClause'][0]);
        $this->assertEquals('test_id', $selectParams['whereClause'][0]['assignedUserId']);
    }

    function testAccessOnlyTeam()
    {
        $selectManager = new \Espo\Core\SelectManagers\Base($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType('Test2');

        $this->user
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($name) {
                if ($name === 'portalId') {
                    return null;
                }
            }));

        $this->user
            ->expects($this->any())
            ->method('getLinkMultipleIdList')
            ->with('teams')
            ->will($this->returnValue(['test_team_id']));

        $this->user
            ->expects($this->any())
            ->method('isAdmin')
            ->will($this->returnValue(false));

        $this->acl
            ->expects($this->any())
            ->method('checkReadOnlyTeam')
            ->will($this->returnValue(true));

        $selectParams = $selectManager->getEmptySelectParams();
        $selectManager->applyAccess($selectParams);

        $this->assertArrayHasKey('whereClause', $selectParams);
        $this->assertTrue($selectManager->hasLeftJoin('teamsAccess', $selectParams));
    }

    function testTextFilter()
    {
        $selectManager = new \Espo\Core\SelectManagers\Base($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType('Test2');

        $this->metadata
            ->expects($this->once())
            ->method('get')
            ->with($this->equalTo('entityDefs.Test2.collection.textFilterFields'))
            ->will($this->returnValue(['name', 'text']));

        $selectParams = $selectManager->getEmptySelectParams();

        $selectManager->applyTextFilter('test', $selectParams);

        $this->assertEquals('test%', $selectParams['whereClause'][0]['OR']['name*']);
        $this->assertEquals('%test%', $selectParams['whereClause'][0]['OR']['text*']);
    }

    function testBuildSelectParams()
    {
        $selectManager = new \Espo\Core\SelectManagers\Base($this->entityManager, $this->user, $this->acl, $this->aclManager, $this->metadata, $this->config);
        $selectManager->setEntityType('Test2');

        $params = array(
            'where' => array(
                array(
                    'type' => 'equals',
                    'field'=> 'int',
                    'value' => 2
                ),
                array(
                    'type' => 'or',
                    'value' => array(
                        array(
                            'type' => 'equals',
                            'field'=> 'date',
                            'value' => '2016-10-10'
                        ),
                        array(
                            'type' => 'after',
                            'field'=> 'dateTime',
                            'value' => '2016-10-10 10:10:00'
                        )
                    )
                )
            ),
            'offset' => 5,
            'maxSize' => 10,
            'sortBy' => 'name',
            'asc' => true
        );

        $selectParams = $selectManager->buildSelectParams($params);

        $this->assertEquals(2, $selectParams['whereClause'][0]['int=']);
        $this->assertEquals(5, $selectParams['offset']);
        $this->assertEquals(10, $selectParams['limit']);
        $this->assertEquals('name', $selectParams['orderBy']);

        $this->assertEquals('2016-10-10', $selectParams['whereClause'][1]['OR'][0]['date=']);
        $this->assertEquals('2016-10-10 10:10:00', $selectParams['whereClause'][1]['OR'][1]['dateTime>']);
    }
}
