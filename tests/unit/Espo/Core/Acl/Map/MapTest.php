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

namespace tests\unit\Espo\Core\Acl;

use Espo\Entities\User;

use Espo\Core\{
    Acl\Map\Map,
    Acl\Map\DataBuilder,
    Acl\Map\MetadataProvider,
    Acl\Map\CacheKeyProvider,
    Acl\Table,
    Acl\ScopeData,
    Acl\FieldData,
    Utils\Config,
    Utils\FieldUtil,
    Utils\DataCache,
};

use StdClass;

class MapTest extends \PHPUnit\Framework\TestCase
{
    private $fieldUtil;

    private $config;

    private $table;

    private $user;

    private $metadataProvider;

    private $cacheKeyProvider;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->fieldUtil = $this->createMock(FieldUtil::class);
        $this->table = $this->createMock(Table::class);
        $this->user = $this->createMock(User::class);
        $this->dataCache = $this->createMock(DataCache::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->cacheKeyProvider = $this->createMock(CacheKeyProvider::class);

        $this->config
            ->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['useCache', false]
            ]);

        $this->user
            ->expects($this->any())
            ->method('getId')
            ->willReturn('user-id');
    }

    private function mockTableData(array $scopeData, array $fieldData, array $permissionData): void
    {
        $returnMap1 = [];

        foreach ($scopeData as $scope => $item) {
            $returnMap1[] = [$scope, ScopeData::fromRaw($item)];
        }

        $this->table
            ->expects($this->any())
            ->method('getScopeData')
            ->willReturnMap($returnMap1);

        $returnMap2 = [];

        foreach ($fieldData as $scope => $item1) {
            foreach ($item1 as $field => $item2) {
                $returnMap2[] = [$scope, $field, FieldData::fromRaw($item2)];
            }
        }

        $this->table
            ->expects($this->any())
            ->method('getFieldData')
            ->willReturnMap($returnMap2);

        $returnMap3 = [];

        foreach ($permissionData as $permission => $level) {
            $returnMap3[] = [$permission, $level];
        }

        $this->table
            ->expects($this->any())
            ->method('getPermissionLevel')
            ->willReturnMap($returnMap3);
    }

    public function testMap1(): void
    {
        $dataBuilder = new DataBuilder($this->metadataProvider, $this->fieldUtil);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getScopeList')
            ->willReturn(['Test1', 'Test2', 'Test3', 'Test4', 'Test5']);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getPermissionList')
            ->willReturn(['assignment', 'portal']);

        $this->metadataProvider
            ->expects($this->any())
            ->method('isScopeEntity')
            ->willReturnMap([
                ['Test1', true],
                ['Test2', true],
                ['Test3', true],
                ['Test4', true],
                ['Test5', false],
            ]);

        $this->metadataProvider
            ->expects($this->any())
            ->method('getScopeFieldList')
            ->willReturnMap([
                ['Test1', ['field1', 'field2', 'field3', 'field4']],
                ['Test2', ['field1']],
                ['Test3', []],
                ['Test4', []],
                ['Test5', []],
            ]);

        $this->fieldUtil
            ->expects($this->any())
            ->method('getAttributeList')
            ->willReturnMap([
                ['Test1', 'field1', ['attr1a', 'attr1b']],
                ['Test1', 'field2', ['field2']],
                ['Test1', 'field3', ['field3']],
                ['Test1', 'field4', ['field4']],
                ['Test2', 'field1', ['field1']],
            ]);

        $this->mockTableData(
            [
                'Test1' => (object) [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_TEAM,
                ],
                'Test2' => (object) [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_TEAM,
                    Table::ACTION_EDIT => Table::LEVEL_OWN,
                ],
                'Test3' => false,
                'Test4' => true,
                'Test5' => true,
            ],
            [
                'Test1' => [
                    'field1' => (object) [
                        Table::ACTION_READ => Table::LEVEL_YES,
                        Table::ACTION_EDIT => Table::LEVEL_NO,
                    ],
                    'field2' => (object) [
                        Table::ACTION_READ => Table::LEVEL_NO,
                        Table::ACTION_EDIT => Table::LEVEL_YES,
                    ],
                    'field3' => (object) [
                        Table::ACTION_READ => Table::LEVEL_NO,
                        Table::ACTION_EDIT => Table::LEVEL_NO,
                    ],
                    'field4' => (object) [
                        Table::ACTION_READ => Table::LEVEL_YES,
                        Table::ACTION_EDIT => Table::LEVEL_YES,
                    ],
                ],
                'Test2' => [
                    'field1' => (object) [
                        Table::ACTION_READ => Table::LEVEL_NO,
                        Table::ACTION_EDIT => Table::LEVEL_NO,
                    ],
                ],
            ],
            [
                'assignment' => Table::LEVEL_YES,
                'portal' => Table::LEVEL_NO,
            ],
        );

        $expectedData = $this->getExpectedRawData();

        $map = new Map(
            $this->user,
            $this->table,
            $dataBuilder,
            $this->config,
            $this->dataCache,
            $this->cacheKeyProvider
        );

        $this->assertEquals($expectedData, $map->getData());

        $this->assertEquals(
            ['field2', 'field3'],
            $map->getScopeForbiddenFieldList('Test1', Table::ACTION_READ)
        );

        $this->assertEquals(
            ['field1', 'field3'],
            $map->getScopeForbiddenFieldList('Test1', Table::ACTION_EDIT)
        );

        $this->assertEquals(
            ['field2', 'field3'],
            $map->getScopeForbiddenAttributeList('Test1', Table::ACTION_READ)
        );

        $this->assertEquals(
            ['attr1a', 'attr1b', 'field3'],
            $map->getScopeForbiddenAttributeList('Test1', Table::ACTION_EDIT)
        );

        $this->assertEquals(
            ['attr1a', 'attr1b', 'field3'],
            $map->getScopeForbiddenAttributeList('Test1', Table::ACTION_EDIT)
        );

        $this->assertEquals(
            ['field1'],
            $map->getScopeForbiddenFieldList('Test2', Table::ACTION_READ)
        );

        $this->assertEquals(
            ['field1'],
            $map->getScopeForbiddenFieldList('Test2', Table::ACTION_READ)
        );

        $this->assertEquals(
            [],
            $map->getScopeForbiddenFieldList('Test3', Table::ACTION_READ)
        );
    }

    private function getExpectedRawData(): StdClass
    {
        return (object) [
          'table' => (object) [
            'Test1' => (object) [
              'read' => 'team',
              'stream' => 'no',
              'edit' => 'no',
              'delete' => 'no',
              'create' => 'yes'
            ],
            'Test2' => (object) [
              'read' => 'team',
              'stream' => 'no',
              'edit' => 'own',
              'delete' => 'no',
              'create' => 'yes'
            ],
            'Test3' => false,
            'Test4' => true,
            'Test5' => true
          ],
          'fieldTable' => (object) [
            'Test1' => (object) [
              'field1' => (object) [
                'read' => 'yes',
                'edit' => 'no'
              ],
              'field2' => (object) [
                'read' => 'no',
                'edit' => 'yes'
              ],
              'field3' => (object) [
                'read' => 'no',
                'edit' => 'no'
              ]
            ],
            'Test2' => (object) [
              'field1' => (object) [
                'read' => 'no',
                'edit' => 'no'
              ]
            ],
            'Test3' => (object) [],
            'Test4' => (object) []
          ],
          'assignmentPermission' => 'yes',
          'portalPermission' => 'no',
          'fieldTableQuickAccess' => (object) [
            'Test1' => (object) [
              'attributes' => (object) [
                'read' => (object) [
                  'yes' => [
                    'attr1a',
                    'attr1b'
                  ],
                  'no' => [
                    'field2',
                    'field3'
                  ]
                ],
                'edit' => (object) [
                  'yes' => [
                    'field2'
                  ],
                  'no' => [
                    'attr1a',
                    'attr1b',
                    'field3'
                  ]
                ]
              ],
              'fields' => (object) [
                'read' => (object) [
                  'yes' => [
                    'field1'
                  ],
                  'no' => [
                    'field2',
                    'field3'
                  ]
                ],
                'edit' => (object) [
                  'yes' => [
                   'field2'
                  ],
                  'no' => [
                    'field1',
                    'field3'
                  ]
                ]
              ]
            ],
            'Test2' => (object) [
              'attributes' => (object) [
                'read' => (object) [
                  'yes' => [],
                  'no' => [
                    'field1'
                  ]
                ],
                'edit' => (object) [
                  'yes' => [],
                  'no' => [
                    'field1'
                  ]
                ]
              ],
              'fields' => (object) [
                'read' => (object) [
                  'yes' => [],
                  'no' => [
                    'field1'
                  ]
                ],
                'edit' => (object) [
                  'yes' => [],
                  'no' => [
                    'field1'
                  ]
                ]
              ]
            ],
            'Test3' => (object) [
              'attributes' => (object) [
                'read' => (object) [
                  'yes' => [],
                  'no' => []
                ],
                'edit' => (object) [
                  'yes' => [],
                  'no' => []
                ]
              ],
              'fields' => (object) [
                'read' => (object) [
                  'yes' => [],
                  'no' => []
                ],
                'edit' => (object) [
                  'yes' => [],
                  'no' => []
                ]
              ]
            ],
            'Test4' => (object) [
              'attributes' => (object) [
                'read' => (object) [
                  'yes' => [],
                  'no' => []
                ],
                'edit' => (object) [
                  'yes' => [],
                  'no' => []
                ]
              ],
              'fields' => (object) [
                'read' => (object) [
                  'yes' => [],
                  'no' => []
                ],
                'edit' => (object) [
                  'yes' => [],
                  'no' => []
                ]
              ]
            ]
          ]
        ];
    }
}
