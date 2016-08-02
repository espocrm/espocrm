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

namespace tests\Espo\Core\Utils;

use tests\ReflectionHelper;

class FieldManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;


    protected function setUp()
    {
        $this->objects['metadata'] = $this->getMockBuilder('\Espo\Core\Utils\Metadata')->disableOriginalConstructor()->getMock();
        $this->objects['language'] = $this->getMockBuilder('\Espo\Core\Utils\Language')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\FieldManager($this->objects['metadata'], $this->objects['language']);

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    public function testCreateExistingField()
    {
        $this->setExpectedException('\Espo\Core\Exceptions\Conflict');

        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
        );

        $this->objects['metadata']
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValue($data));

        $this->object->create('varName', $data, 'CustomEntity');
    }

    public function testUpdateCoreField()
    {
        //$this->setExpectedException('\Espo\Core\Exceptions\Error');
        $this->objects['metadata']
            ->expects($this->once())
            ->method('set')
            ->will($this->returnValue(true));

        $this->objects['language']
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
            "label" => "Name",
        );

        $map = array(
            ['entityDefs.Account.fields.name', null, $data],
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->objects['metadata']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->object->update('name', $data, 'Account');
    }

    public function testUpdateCustomFieldIsNotChanged()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        );

        $map = array(
            ['entityDefs.CustomEntity.fields.varName', null, $data],
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->objects['metadata']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->objects['metadata']
            ->expects($this->never())
            ->method('set')
            ->will($this->returnValue(true));

        $this->assertTrue($this->object->update('varName', $data, 'CustomEntity'));
    }

    public function testUpdateCustomField()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        );

        $map = array(
            ['entityDefs.CustomEntity.fields.varName', null, $data],
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->objects['metadata']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->objects['metadata']
            ->expects($this->once())
            ->method('set')
            ->will($this->returnValue(true));

        $data = array(
            "type" => "varchar",
            "maxLength" => "150",
            "required" => true,
            "isCustom" => true,
        );

        $this->object->update('varName', $data, 'CustomEntity');
    }

    public function testRead()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
            "label" => 'Var Name',
        );

        $this->objects['metadata']
            ->expects($this->at(0))
            ->method('get')
            ->will($this->returnValue($data));

        $this->objects['language']
            ->expects($this->once())
            ->method('translate')
            ->will($this->returnValue('Var Name'));

        $this->assertEquals($data, $this->object->read('varName', 'Account'));
    }

    public function testNormalizeDefs()
    {
        $input1 = 'fielName';
        $input2 = array(
            "type" => "varchar",
            "maxLength" => "50",
        );
        $result = array(
            'fields' => array(
                'fielName' => array(
                    "type" => "varchar",
                    "maxLength" => "50",
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->invokeMethod('normalizeDefs', array($input1, $input2, 'CustomEntity')));
    }

    public function testDeleteTestFile()
    {
        $file = 'custom/Espo/Custom/Resources/metadata/entityDefs/CustomEntity.json';
        if (file_exists($file)) {
            @unlink($file);
        }
    }





}

?>
