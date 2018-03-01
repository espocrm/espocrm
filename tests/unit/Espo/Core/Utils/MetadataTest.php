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

namespace tests\unit\Espo\Core\Utils;

use tests\unit\ReflectionHelper;

class MetadataTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected $defaultCacheFile = 'tests/unit/testData/Utils/Metadata/metadata.php';
    protected $defaultObjCacheFile = 'tests/unit/testData/Utils/Metadata/metadata.php';

    protected $cacheFile = 'tests/unit/testData/cache/metadata.php';
    protected $objCacheFile = 'tests/unit/testData/cache/objMetadata.php';

    protected function setUp()
    {
        /*copy defaultCacheFile file to cache*/
        if (!file_exists($this->cacheFile)) {
            copy($this->defaultCacheFile, $this->cacheFile);
        }

        if (!file_exists($this->objCacheFile)) {
            copy($this->defaultObjCacheFile, $this->objCacheFile);
        }

        $this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();

        $this->objects['log'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Log')->disableOriginalConstructor()->getMock();
        $GLOBALS['log'] = $this->objects['log'];

        $this->object = new \Espo\Core\Utils\Metadata($this->objects['fileManager'], true);

        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('cacheFile', $this->cacheFile);
        $this->reflection->setProperty('objCacheFile', $this->objCacheFile);
    }

    protected function tearDown()
    {
        $this->object->clearChanges();
        $this->object = NULL;
    }

    public function testGet()
    {
        $result = 'System';
        $this->assertEquals($result, $this->object->get('app.adminPanel.system.label'));

        $result = 'fields';
        $this->assertArrayHasKey($result, $this->object->get('entityDefs.User'));
    }

    public function testSet()
    {
        $data = array (
          'fields' =>
          array (
            'name' =>
            array (
              'required' => false,
              'maxLength' => 150,
              'view' => 'Views.Test.Custom',
            ),
          ),
        );
        $this->object->set('entityDefs', 'Attachment', $data);
        $this->assertEquals('Views.Test.Custom', $this->object->get('entityDefs.Attachment.fields.name.view'));
        $this->assertEquals(150, $this->object->get('entityDefs.Attachment.fields.name.maxLength'));

        $result = array(
            'entityDefs' => array(
                'Attachment' => $data
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('changedData'));

        $data = array (
          'fields' =>
          array (
            'name' =>
            array (
              'maxLength' => 200,
            ),
          ),
        );
        $this->object->set('entityDefs', 'Attachment', $data);
        $this->assertEquals(200, $this->object->get('entityDefs.Attachment.fields.name.maxLength'));
        $this->assertEquals('Views.Test.Custom', $this->object->get('entityDefs.Attachment.fields.name.view'));

        $result = array(
            'entityDefs' => array(
                'Attachment' => array (
                  'fields' =>
                  array (
                    'name' =>
                    array (
                      'required' => false,
                      'maxLength' => 200,
                      'view' => 'Views.Test.Custom',
                    ),
                  ),
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('changedData'));

        $this->object->clearChanges();

        $this->assertEquals(array(), $this->reflection->getProperty('changedData'));
        $this->assertNull($this->object->get('entityDefs.Attachment.fields.name.view'));
    }

    public function testDelete()
    {
        $data = array (
            'fields.name.type',
        );
        $this->object->delete('entityDefs', 'Attachment', $data);
        $this->assertNull($this->object->get('entityDefs.Attachment.fields.name.type'));

        $result = array(
            'entityDefs' => array(
                'Attachment' => array(
                    'fields.name.type',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));

        $data = array (
            'fields.name.required',
        );
        $this->object->delete('entityDefs', 'Attachment', $data);
        $this->assertNull($this->object->get('entityDefs.Attachment.fields.name.required'));

        $result = array(
            'entityDefs' => array(
                'Attachment' => array(
                    'fields.name.type',
                    'fields.name.required',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));

        $this->object->init(false);

        $this->assertNotNull($this->object->get('entityDefs.Attachment.fields.name.type'));
        $this->assertNotNull($this->object->get('entityDefs.Attachment.fields.name.required'));

        $this->object->clearChanges();
        $this->assertEquals(array(), $this->reflection->getProperty('deletedData'));
    }

    public function testUndelete()
    {
        $data = array (
            'fields.name.type',
            'fields.name.required',
        );
        $this->object->delete('entityDefs', 'Attachment', $data);
        $this->assertNull($this->object->get('entityDefs.Attachment.fields.name.type'));

        $data = array (
          'fields' =>
          array (
            'name' =>
            array (
              'type' => 'enum',
            ),
          ),
        );
        $this->object->set('entityDefs', 'Attachment', $data);
        $this->assertEquals('enum', $this->object->get('entityDefs.Attachment.fields.name.type'));

        $result = array(
            'entityDefs' => array(
                'Attachment' => array(
                    1 => 'fields.name.required',
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));

        $data = array (
          'fields' =>
          array (
            'name' =>
            array (
              'required' => true,
            ),
          ),
        );
        $this->object->set('entityDefs', 'Attachment', $data);
        $this->assertEquals(true, $this->object->get('entityDefs.Attachment.fields.name.required'));

        $result = array(
            'entityDefs' => array(
                'Attachment' => array(
                ),
            ),
        );
        $this->assertEquals($result, $this->reflection->getProperty('deletedData'));
    }

    public function testGetCustom()
    {
        $customPath = 'tests/unit/testData/cache/metadata/custom';

        $paths = $this->reflection->getProperty('paths');
        $paths['customPath'] = $customPath;
        $this->reflection->setProperty('paths', $paths);

        $this->assertNull($this->object->getCustom('entityDefs', 'Lead'));

        $customData = $this->object->getCustom('entityDefs', 'Lead', (object) []);
        $this->assertTrue(is_object($customData));

        $data = (object) [
          'fields' => (object) [
            'status' => (object) [
              "type" => "enum",
              "options" => ["__APPEND__", "Test1", "Test2"],
            ],
          ],
        ];
        $this->object->saveCustom('entityDefs', 'Lead', $data);

        $this->assertEquals($data, $this->object->getCustom('entityDefs', 'Lead'));

        unlink($customPath . '/entityDefs/Lead.json');
    }

    public function testSaveCustom()
    {
        $initStatusOptions = $this->object->get('entityDefs.Lead.fields.status.options');

        $customPath = 'tests/unit/testData/cache/metadata/custom';

        $paths = $this->reflection->getProperty('paths');
        $paths['customPath'] = $customPath;
        $this->reflection->setProperty('paths', $paths);

        $data = (object) [
          'fields' => (object) [
            'status' => (object) [
              "type" => "enum",
              "options" => ["__APPEND__", "Test1", "Test2"],
            ],
          ],
        ];

        $this->object->saveCustom('entityDefs', 'Lead', $data);

        $savedFile = $customPath . '/entityDefs/Lead.json';
        $fileContent = $this->objects['fileManager']->getContents($savedFile);
        $savedData = \Espo\Core\Utils\Json::decode($fileContent);

        $this->assertEquals($data, $savedData);

        $initStatusOptions[] = 'Test1';
        $initStatusOptions[] = 'Test2';
        $this->assertEquals($initStatusOptions, $this->object->get('entityDefs.Lead.fields.status.options'));

        unlink($savedFile);
    }

    public function testSaveCustom2()
    {
        $customPath = 'tests/unit/testData/cache/metadata/custom';

        $paths = $this->reflection->getProperty('paths');
        $paths['customPath'] = $customPath;
        $this->reflection->setProperty('paths', $paths);

        $initData = (object) [
          'fields' => (object) [
            'status' => (object) [
              "type" => "enum",
              "options" => ["__APPEND__", "Test1", "Test2"],
            ],
          ],
        ];

        $this->object->saveCustom('entityDefs', 'Lead', $initData);

        $customData = $this->object->getCustom('entityDefs', 'Lead');

        unset($customData->fields->status->type);
        $customData->fields->status->options = ["__APPEND__", "Test1"];
        $this->object->saveCustom('entityDefs', 'Lead', $customData);

        $savedFile = $customPath . '/entityDefs/Lead.json';
        $fileContent = $this->objects['fileManager']->getContents($savedFile);
        $savedData = \Espo\Core\Utils\Json::decode($fileContent);

        $expectedData = (object) [
          'fields' => (object) [
            'status' => (object) [
              "options" => ["__APPEND__", "Test1"],
            ],
          ],
        ];

        $this->assertEquals($expectedData, $savedData);

        unlink($savedFile);
    }

    public function testGetObjects()
    {
        $result = 'System';
        $this->assertEquals($result, $this->object->getObjects('app.adminPanel.system.label'));

        $result = 'fields';
        $this->assertObjectHasAttribute($result, $this->object->getObjects('entityDefs.User'));

        $result = (object) [
            'type' => 'bool',
            'tooltip' => true
        ];
        $this->assertEquals($result, $this->object->getObjects('entityDefs.User.fields.isAdmin'));
    }
}