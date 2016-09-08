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

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected $defaultCacheFile = 'tests/testData/Utils/Metadata/metadata.php';

    protected $cacheFile = 'tests/testData/cache/metadata.php';
    protected $ormCacheFile = 'tests/testData/Utils/Metadata/ormMetadata.php';

    protected function setUp()
    {
        /*copy defaultCacheFile file to cache*/
        if (!file_exists($this->cacheFile)) {
            copy($this->defaultCacheFile, $this->cacheFile);
        }

        $this->objects['fileManager'] = new \Espo\Core\Utils\File\Manager();

        $this->objects['log'] = $this->getMockBuilder('\\Espo\\Core\\Utils\\Log')->disableOriginalConstructor()->getMock();
        $GLOBALS['log'] = $this->objects['log'];

        $this->object = new \Espo\Core\Utils\Metadata($this->objects['fileManager'], true);

        $this->reflection = new ReflectionHelper($this->object);
        $this->reflection->setProperty('cacheFile', $this->cacheFile);
    }

    protected function tearDown()
    {
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

}