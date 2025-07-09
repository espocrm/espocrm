<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Tools;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Metadata;
use Espo\Tools\EntityManager\NameUtil;
use Espo\Tools\FieldManager\FieldManager;
use Espo\Core\InjectableFactory;
use PHPUnit\Framework\TestCase;

class FieldManagerTest extends TestCase
{
    private ?FieldManager $fieldManager = null;
    private ?NameUtil $nameUtil = null;

    private $metadataHelper;
    private $language;
    protected $defaultLanguage;
    protected $metadata;

    protected function setUp() : void
    {
        $this->metadata = $this->createMock(Metadata::class);
        $this->language = $this->createMock(Language::class);
        $baseLanguage = $this->createMock(Language::class);
        $this->defaultLanguage = $this->createMock(Language::class);
        $this->metadataHelper = $this->createMock(Metadata\Helper::class);
        $this->nameUtil = $this->createMock(NameUtil::class);

        $this->nameUtil->expects($this->any())
            ->method('addCustomPrefix')
            ->willReturnCallback(fn ($it) => $it);

        $this->fieldManager = new FieldManager(
            $this->createMock(InjectableFactory::class),
            $this->metadata,
            $this->language,
            $baseLanguage,
            $this->metadataHelper,
            $this->nameUtil
        );
    }

    public function testCreateExistingField(): void
    {
        $this->expectException(Conflict::class);

        $data = [
            "type" => "varchar",
            "maxLength" => "50",
        ];

        $this->nameUtil
            ->expects($this->once())
            ->method('fieldExists')
            ->willReturn(true);

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ["scopes.CustomEntity.customizable", null, true],
            ]);

        $this->fieldManager->create('CustomEntity', 'varName', $data);
    }

    public function testUpdateCoreField(): void
    {
        $data = [
            "type" => "varchar",
            "maxLength" => 100,
            "label" => "Modified Name",
        ];

        $existingData = (object) [
            "type" => "varchar",
            "maxLength" => 50,
            "label" => "Name",
        ];

        $map = [
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
            ["scopes.Account.customizable", null, true],
        ];

        $this->language
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap($map);

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->willReturn($existingData);

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->willReturn(json_decode('{
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
            }', true));

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getCustom')
            ->willReturn((object) []);

        $this->fieldManager->update('Account', 'name', $data);
    }

    public function testUpdateCoreFieldWithNoChanges(): void
    {
        $data = [
            "type" => "varchar",
            "maxLength" => 50,
            "label" => "Name",
        ];

        $map = [
            [['entityDefs', 'Account', 'fields', 'name', 'type'], null, $data['type']],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
            ["scopes.Account.customizable", null, true],
        ];

        $this->metadata
            ->expects($this->never())
            ->method('set');

        $this->language
            ->expects($this->once())
            ->method('save');

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->willReturn(json_decode('{
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
            }', true));

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap($map);

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->willReturn((object) $data);

        $this->metadata
            ->expects($this->exactly(1))
            ->method('getCustom')
            ->willReturn((object) []);

        $this->metadata
            ->expects($this->never())
            ->method('saveCustom');

        $this->fieldManager->update('Account', 'name', $data);
    }

    public function testUpdateCustomField(): void
    {
        $data = [
            "type" => "varchar",
            "maxLength" => "50",
            "isCustom" => true,
        ];

        $map = [
            ['entityDefs.CustomEntity.fields.varName.type', null, $data['type']],
            [['entityDefs', 'CustomEntity', 'fields', 'varName'], null, $data],
            ['fields.varchar', null, null],
            [['fields', 'varchar', 'hookClassName'], null, null],
            ["scopes.CustomEntity.customizable", null, true],
        ];

        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnMap($map);

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getObjects')
            ->willReturn((object) $data);

        $this->metadata
            ->expects($this->once())
            ->method('saveCustom');

        $this->metadataHelper
            ->expects($this->once())
            ->method('getFieldDefsByType')
            ->willReturn(json_decode('{
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
            }', true));

        $data = [
            "type" => "varchar",
            "maxLength" => "150",
            "required" => true,
            "isCustom" => true,
        ];

        $this->metadata
            ->expects($this->exactly(2))
            ->method('getCustom')
            ->willReturn((object) []);

        $this->fieldManager->update('CustomEntity', 'varName', $data);
    }

    public function testRead(): void
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
            ->willReturn((object) $data);

        $this->language
            ->expects($this->once())
            ->method('translate')
            ->willReturn('Var Name');

        $this->assertEquals($data, $this->fieldManager->read('Account', 'varName'));
    }
}
