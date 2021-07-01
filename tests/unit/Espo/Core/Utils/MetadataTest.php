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

namespace tests\unit\Espo\Core\Utils;

use tests\unit\ReflectionHelper;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\UnifierObj;
use Espo\Core\Utils\File\Unifier;
use Espo\Core\Utils\Module;
use Espo\Core\Utils\Module\PathProvider as ModulePathProvider;
use Espo\Core\Utils\Resource\Reader;
use Espo\Core\Utils\Resource\PathProvider;

class MetadataTest extends \PHPUnit\Framework\TestCase
{
    private $object;

    private $reflection;

    protected function setUp(): void
    {
        $this->fileManager = new FileManager();

        $this->dataCache = $this->getMockBuilder(DataCache::class)->disableOriginalConstructor()->getMock();

        $this->log = $this->getMockBuilder(Log::class)->disableOriginalConstructor()->getMock();

        $GLOBALS['log'] = $this->log;

        $module = new Module($this->fileManager);

        $pathProvider = new PathProvider(new ModulePathProvider($module));

        $unifierObj = new UnifierObj($this->fileManager, $module, $pathProvider);
        $unifier = new Unifier($this->fileManager, $module, $pathProvider);

        $reader = new Reader($unifier, $unifierObj);

        $this->object = new Metadata($this->fileManager, $this->dataCache, $reader, $module, true);

        $this->reflection = new ReflectionHelper($this->object);

        $this->customPath = 'tests/unit/testData/cache/metadata/custom';

        $this->reflection->setProperty('customPath', $this->customPath);
    }

    protected function tearDown() : void
    {
        $this->object->clearChanges();
        $this->object = NULL;
    }

    public function testGet()
    {
        $this->assertEquals('System', $this->object->get('app.adminPanel.system.label'));

        $this->assertArrayHasKey('fields', $this->object->get('entityDefs.User'));
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
        $this->assertNull($this->object->get('entityDefs.Attachment.fields.name.maxLength'));
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
        $this->assertEquals([], $this->reflection->getProperty('deletedData'));
    }

    public function testUndelete()
    {
        $data = [
            'fields.name.type',
            'fields.name.required',
        ];

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

        unlink($this->customPath . '/entityDefs/Lead.json');
    }

    public function testSaveCustom1()
    {
        $data = (object) [
          'fields' => (object) [
            'status' => (object) [
              "type" => "enum",
              "options" => ["__APPEND__", "Test1", "Test2"],
            ],
          ],
        ];

        $this->object->saveCustom('entityDefs', 'Lead', $data);

        $savedFile = $this->customPath . '/entityDefs/Lead.json';
        $fileContent = $this->fileManager->getContents($savedFile);

        $savedData = \Espo\Core\Utils\Json::decode($fileContent);

        $this->assertEquals($data, $savedData);

        unlink($savedFile);
    }

    public function testSaveCustom2()
    {
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

        $savedFile = $this->customPath . '/entityDefs/Lead.json';

        $fileContent = $this->fileManager->getContents($savedFile);

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
        $this->assertEquals('System', $this->object->getObjects('app.adminPanel.system.label'));

        $this->assertObjectHasAttribute('fields', $this->object->getObjects('entityDefs.User'));

        $this->assertObjectHasAttribute('type', $this->object->getObjects('entityDefs.User.fields.name'));
    }
}
