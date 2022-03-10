<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\unit\Espo\Tools;

use tests\unit\ReflectionHelper;

use Espo\Tools\FieldManager\FieldManager;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\FieldUtil;

class FieldManagerTest extends \PHPUnit\Framework\TestCase
{
    private FieldManager $fieldManager;

    private $reflection;

    protected function setUp() : void
    {
        $this->metadata = $this->createMock('Espo\\Core\\Utils\\Metadata');
        $this->language = $this->createMock('Espo\\Core\\Utils\\Language');
        $this->baseLanguage = $this->createMock('\\Espo\\Core\\Utils\\Language');
        $this->defaultLanguage = $this->createMock('\\Espo\\Core\\Utils\\Language');

        $this->metadataHelper = $this->createMock('Espo\\Core\\Utils\\Metadata\\Helper');

        $this->fieldManager = new FieldManager(
            $this->createMock(InjectableFactory::class),
            $this->metadata,
            $this->language,
            $this->baseLanguage,
            $this->defaultLanguage,
            $this->createMock(FieldUtil::class)
        );

        $this->reflection = new ReflectionHelper($this->fieldManager);
        $this->reflection->setProperty('metadataHelper', $this->metadataHelper);
    }

    public function testCreateExistingField()
    {
        $this->expectException('Espo\Core\Exceptions\Conflict');

        $data = [
            "type" => "varchar",
            "maxLength" => "50",
        ];

        $this->metadata
            ->expects($this->once())
            ->method('getObjects')
            ->will($this->returnValue($data));

        $this->fieldManager->create('CustomEntity', 'varName', $data);
    }

    public function testUpdateCoreField()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => 100,
            "label" => "Modified Name",
        );

        $existingData = (object) [
            "type" => "varchar",
            "maxLength" => 50,
            "label" => "Name",
        ];

        $map = array(
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->language
            ->expects($this->once())
            ->method('save')
            ->will($this->returnValue(true));

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->will($this->returnValue($existingData));

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->will($this->returnValue(json_decode('{
               "params":[
                  {
                     "name":"required",
                     "type":"bool",
                     "default":false
                  },
                  {
                     "name":"default",
                     "type":"varchar"
                  },
                  {
                     "name":"maxLength",
                     "type":"int"
                  },
                  {
                     "name":"trim",
                     "type":"bool",
                     "default": true
                  },
                  {
                     "name": "options",
                     "type": "multiEnum"
                  },
                  {
                     "name":"audited",
                     "type":"bool"
                  },
                  {
                     "name":"readOnly",
                     "type":"bool"
                  }
               ],
               "filter": true,
               "personalData": true,
               "textFilter": true,
               "fullTextSearch": true
            }', true)));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->fieldManager->update('Account', 'name', $data);
    }

    public function testUpdateCoreFieldWithNoChanges()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => 50,
            "label" => "Name",
        );

        $map = array(
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->metadata
            ->expects($this->never())
            ->method('set');

        $this->language
            ->expects($this->once())
            ->method('save');

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->will($this->returnValue(json_decode('{
               "params":[
                  {
                     "name":"required",
                     "type":"bool",
                     "default":false
                  },
                  {
                     "name":"default",
                     "type":"varchar"
                  },
                  {
                     "name":"maxLength",
                     "type":"int"
                  },
                  {
                     "name":"trim",
                     "type":"bool",
                     "default": true
                  },
                  {
                     "name": "options",
                     "type": "multiEnum"
                  },
                  {
                     "name":"audited",
                     "type":"bool"
                  },
                  {
                     "name":"readOnly",
                     "type":"bool"
                  }
               ],
               "filter": true,
               "personalData": true,
               "textFilter": true,
               "fullTextSearch": true
            }', true)));

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->will($this->returnValue((object) $data));

        $this->metadata
            ->expects($this->exactly(1))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->metadata
            ->expects($this->never())
            ->method('saveCustom');

        $this->fieldManager->update('Account', 'name', $data);
    }

    public function dddtestUpdateCustomFieldIsNotChanged()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        );

        $map = array(
            ['entityDefs.CustomEntity.fields.varName', [], $data],
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->never())
            ->method('set')
            ->will($this->returnValue(true));

        $this->metadata
            ->expects($this->exactly(1))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->assertTrue($this->fieldManager->update('CustomEntity', 'varName', $data));
    }

    public function testUpdateCustomField()
    {
        $data = array(
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        );

        $map = array(
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
        );

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->will($this->returnValue((object) $data));

        $this->metadata
            ->expects($this->once())
            ->method('saveCustom')
            ->will($this->returnValue(true));

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->will($this->returnValue(json_decode('{
               "params":[
                  {
                     "name":"required",
                     "type":"bool",
                     "default":false
                  },
                  {
                     "name":"default",
                     "type":"varchar"
                  },
                  {
                     "name":"maxLength",
                     "type":"int"
                  },
                  {
                     "name":"trim",
                     "type":"bool",
                     "default": true
                  },
                  {
                     "name": "options",
                     "type": "multiEnum"
                  },
                  {
                     "name":"audited",
                     "type":"bool"
                  },
                  {
                     "name":"readOnly",
                     "type":"bool"
                  }
               ],
               "filter": true,
               "personalData": true,
               "textFilter": true,
               "fullTextSearch": true
            }', true)));

        $data = array(
            "type" => "varchar",
            "maxLength" => "150",
            "required" => true,
            "isCustom" => true,
        );

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getCustom')
            ->will($this->returnValue((object) []));

        $this->fieldManager->update('CustomEntity', 'varName', $data);
    }

    public function testRead()
    {
        $data = [
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
            "label" => 'Var Name',
        ];

        $this->metadata
            ->expects($this->once())
            ->method('getObjects')
            ->will($this->returnValue((object) $data));

        $this->language
            ->expects($this->once())
            ->method('translate')
            ->will($this->returnValue('Var Name'));

        $this->assertEquals($data, $this->fieldManager->read('Account', 'varName'));
    }

    public function testNormalizeDefs()
    {
        $input1 = 'fielName';
        $input2 = array(
            "type" => "varchar",
            "maxLength" => "50",
        );

        $result = (object) array(
            'fields' => (object) array(
                'fielName' => (object) array(
                    "type" => "varchar",
                    "maxLength" => "50",
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->invokeMethod('normalizeDefs', array('CustomEntity', $input1, $input2)));
    }
}
