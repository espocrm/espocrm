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
 ************************************************************************/

namespace tests\Espo\Core;


class SelectManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $selectManager;
    
    protected function setUp()
    {
        $entityManager = $this->getMockBuilder('\\Espo\\Core\\ORM\\EntityManager')->disableOriginalConstructor()->getMock();
        $user = $this->getMockBuilder('\\Espo\\Entities\\User')->disableOriginalConstructor()->getMock();
        $acl = $this->getMockBuilder('\\Espo\\Core\\Acl')->disableOriginalConstructor()->getMock();
        $metadata = $this->getMockBuilder('\\Espo\\Core\\Utils\\Metadata')->disableOriginalConstructor()->getMock();
        
        $this->selectManager = new \Espo\Core\SelectManagerFactory($entityManager, $user, $acl, $metadata);
    }
    
    protected function tearDown()
    {
        unset($this->selectManager);
    }
    
    public function testWhere()
    {
        /*$params = array(
            'where' => array(
                array(
                    'type' => 'or',
                    'value' => array(
                        array(
                            'type' => 'like',
                            'field' => 'name',
                            'value' => 'Brom',
                        ),
                        array(
                            'type' => 'like',
                            'field' => 'city',
                            'value' => 'Brom',
                        ),
                    ),
                ),
            )
        );
        
        $result = $this->selectManager->getSelectParams($params);

        $this->assertEquals($result['whereClause'][0]['OR']['name*'], 'Brom');*/
    }
}

