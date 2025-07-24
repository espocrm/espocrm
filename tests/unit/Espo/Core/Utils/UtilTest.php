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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\Util;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testGetSeparator()
    {
        $this->assertEquals(DIRECTORY_SEPARATOR, Util::getSeparator());
    }

    public function testToCamelCase()
    {
        $this->assertEquals('detail', Util::toCamelCase('detail'));
        $this->assertEquals('detailView', Util::toCamelCase('detail-view', '-'));
        $this->assertEquals('myDetailView', Util::toCamelCase('my_detail_view'));
        $this->assertEquals('AdvancedPack', Util::toCamelCase('Advanced Pack', ' ', true));
        $this->assertEquals('advancedPack', Util::toCamelCase('Advanced Pack', ' '));

        $input = [
            'detail',
            'my_detail_view',
        ];
        $result = [
            'detail',
            'myDetailView',
        ];
        $this->assertEquals($result, Util::toCamelCase($input));
    }

    public function testFromCamelCase()
    {
        $this->assertEquals('detail', Util::fromCamelCase('detail'));
        $this->assertEquals('detail-view', Util::fromCamelCase('detailView', '-'));
        $this->assertEquals('my_detail_view', Util::fromCamelCase('myDetailView'));
        $this->assertEquals('my_f_f', Util::fromCamelCase('myFF'));

        $input = [
            'detail',
            'myDetailView',
            'myFF',
        ];
        $result = [
            'detail',
            'my_detail_view',
            'my_f_f',
        ];
        $this->assertEquals($result, Util::fromCamelCase($input));
    }

    public function testToUnderScore()
    {
        $this->assertEquals('detail', Util::toUnderScore('detail'));
        $this->assertEquals('detail_view', Util::toUnderScore('detailView'));
        $this->assertEquals('my_detail_view', Util::toUnderScore('myDetailView'));
        $this->assertEquals('my_f_f', Util::toUnderScore('myFF'));

        $input = [
            'detail',
            'detailView',
            'myDetailView',
            'myFF',
        ];
        $result = [
            'detail',
            'detail_view',
            'my_detail_view',
            'my_f_f',
        ];
        $this->assertEquals($result, Util::toUnderScore($input));
    }

    public function testMerge2()
    {
        $d1 = [
          'hello' => 'world',
          'man' => [
            'test' => [
              0 => ['name' => 'test 1'],
              1 => ['name' => 'test 2']
            ]
          ]
        ];
        $d2 = [
          'test' => []
        ];
        $d3 = [
          'man' => [
            'test' => [
              0 => '__APPEND__',
              1 => ['name' => 'test 3']
            ]
          ]
        ];
        $expected = [
            'test' => [],
            'hello' => 'world',
            'man' => [
              'test' => [
                0 => ['name' => 'test 1'],
                1 => ['name' => 'test 2'],
                2 => ['name' => 'test 3']
              ]
            ]
        ];

        $result = Util::merge(Util::merge($d2, $d1), $d3);
        $this->assertEquals($expected, $result);
    }

    public function testMerge()
    {
        $array1= [
            'defaultPermissions',
            'logger',
            'devMode'
        ];
        $array2Main= [
            45 => '125',
            'sub' =>  [
                'subV' => '125',
            ],
        ];
        $result= [
            'defaultPermissions',
            'logger',
            'devMode',
            45 => '125',
            'sub' =>  [
                'subV' => '125',
            ],
        ];
        $this->assertEquals($result, Util::merge($array1, $array2Main));


        $array1= [
            'datetime' =>
              [
                'dateFormat' => 'Y-m-d',
                'timeFormat' => 'H:i:s',
              ],
        ];
        $array2Main= [
            'datetime' =>
              [
                'dateFormat' => 'MyDateFormat',
              ],
        ];
        $result= [
            'datetime' =>
              [
                'dateFormat' => 'MyDateFormat',
                'timeFormat' => 'H:i:s',
              ],
        ];
        $this->assertEquals($result, Util::merge($array1, $array2Main));


        $array1= [
            'database' =>
              [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'espocrm',
                'user' => 'root',
                'password' => '',
              ],
        ];
        $array2Main= [
            'database' =>
              [
                'password' => 'MyPass',
              ],
        ];
        $result= [
            'database' =>
              [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'espocrm',
                'user' => 'root',
                'password' => 'MyPass',
              ],
        ];
        $this->assertEquals($result, Util::merge($array1, $array2Main));
    }

    public function testMergeWithAppend()
    {
        $currentArray = [
            'entityDefs' =>
              [
                'Attachment' =>
                [
                  'fields' =>
                  [
                    'name' =>
                    [
                      'type' => 'varchar',
                      'required' => true,
                    ],
                    'type' =>
                    [
                      'type' => 'varchar',
                      'maxLength' => 36,
                    ],
                    'size' =>
                    [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                    'sizeInt' =>
                    [
                      'type' => 'enum',
                      'value' => [0, 1, 2],
                    ],
                    'merged' =>
                    [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                    'mergedInt' =>
                    [
                      'type' => 'enum',
                      'value' => [0, 1, 2],
                    ],
                  ],
                ],
                'Contact' =>
                [
                  'fields' =>
                  [
                    'name' =>
                    [
                      'type' => 'varchar',
                      'required' => true,
                    ],
                    'type' =>
                    [
                      'type' => 'varchar',
                      'maxLength' => 36,
                    ],
                    'size' =>
                    [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                    'merged' =>
                    [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                  ],
                ],
              ],
        'MyCustom' =>
            [
              'fields' =>
              [
                'name' =>
                [
                  'type' => 'varchar',
                  'required' => true,
                ],
              ],
            ],
        ];

        $newArray = [
            'entityDefs' =>
              [
                'Attachment' =>
                [
                  'fields' =>
                  [
                    'name' =>
                    [
                      'type' => 'varchar',
                      'required' => false,
                      'NEW' => 'NEWVAL',
                    ],
                    'type' =>
                    [
                      'type' => 'NETYPE',
                    ],
                    'size' =>
                    [
                      'type' => 'enum',
                      'value' => ["B1", "B2", "B3"],
                    ],
                    'sizeInt' =>
                    [
                      'type' => 'enum',
                      'value' => [5, 8, 9],
                    ],
                    'merged' =>
                    [
                      'type' => 'enum',
                      'value' => ["__APPEND__", "B1", "B2", "B3"],
                    ],
                    'mergedInt' =>
                    [
                      'type' => 'enum',
                      'value' => ['__APPEND__', 5, 8, 9],
                    ],
                  ],
                  'list' =>
                  [
                    'test' => 'Here',
                  ],
                ],
                'Contact' =>
                [
                  'fields' =>
                  [
                    'name' =>
                    [
                      'type' => 'varchar',
                      'required' => false,
                      'NEW' => 'NEWVAL',
                    ],
                    'type' =>
                    [
                      'type' => 'NEW',
                      'maxLength' => 1000000,
                    ],
                    'size' =>
                    [
                      'type' => 'enum',
                      'value' => ["B1", "B2", "B3"],
                    ],
                    'merged' =>
                    [
                      'type' => 'enum',
                      'value' => ["__APPEND__", "B1", "B2", "B3"],
                    ],
                  ],
                ],
              ],
        ];


        $result = [
          'entityDefs' =>
          [
            'Attachment' =>
            [
              'fields' =>
              [
                'name' =>
                [
                  'type' => 'varchar',
                  'required' => false,
                  'NEW' => 'NEWVAL',
                ],
                'type' =>
                [
                  'type' => 'NETYPE',
                  'maxLength' => 36,
                ],
                'size' =>
                [
                  'type' => 'enum',
                  'value' =>
                  [
                    0 => 'B1',
                    1 => 'B2',
                    2 => 'B3',
                  ],
                ],
                'sizeInt' =>
                [
                  'type' => 'enum',
                  'value' =>
                  [
                    0 => 5,
                    1 => 8,
                    2 => 9,
                  ],
                ],
                'merged' =>
                [
                  'type' => 'enum',
                  'value' =>
                  [
                    0 => 'v1',
                    1 => 'v2',
                    2 => 'v3',
                    3 => 'B1',
                    4 => 'B2',
                    5 => 'B3',
                  ],
                ],
                'mergedInt' =>
                [
                  'type' => 'enum',
                  'value' =>
                  [
                    0 => 0,
                    1 => 1,
                    2 => 2,
                    3 => 5,
                    4 => 8,
                    5 => 9,
                  ],
                ],
              ],
              'list' =>
              [
                'test' => 'Here',
              ],
            ],
            'Contact' =>
            [
              'fields' =>
              [
                'name' =>
                [
                  'type' => 'varchar',
                  'required' => false,
                  'NEW' => 'NEWVAL',
                ],
                'type' =>
                [
                  'type' => 'NEW',
                  'maxLength' => 1000000,
                ],
                'size' =>
                [
                  'type' => 'enum',
                  'value' =>
                  [
                    0 => 'B1',
                    1 => 'B2',
                    2 => 'B3',
                  ],
                ],
                'merged' =>
                [
                  'type' => 'enum',
                  'value' =>
                  [
                    0 => 'v1',
                    1 => 'v2',
                    2 => 'v3',
                    3 => 'B1',
                    4 => 'B2',
                    5 => 'B3',
                  ],
                ],
              ],
            ],
          ],
          'MyCustom' =>
          [
            'fields' =>
            [
              'name' =>
              [
                'type' => 'varchar',
                'required' => true,
              ],
            ],
          ],
        ];

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeWithAppend2()
    {
        $currentArray = json_decode('{
         "controller": "Controllers.Record",
         "boolFilterList": ["onlyMy"],
         "sidePanels":{
            "detail":[
               {
                  "name":"optedOut",
                  "label":"Opted Out",
                  "view":"Crm:TargetList.Record.Panels.OptedOut"
               }
            ]
         }
        }', true);

        $newArray = json_decode('{
          "views":{
               "detail":"Advanced:TargetList.Detail"
           },
          "recordViews": {
            "detail": "Advanced:TargetList.Record.Detail"
          },
          "sidePanels": {
            "detail": [
              "__APPEND__",
                {
                   "name":"populating",
                   "label":"Populating",
                   "view":"Advanced:TargetList.Record.Panels.Populating"
                }
            ],
            "edit": [
              "__APPEND__",
                {
                   "name":"populating",
                   "label":"Populating",
                   "view":"Advanced:TargetList.Record.Panels.Populating"
                }
            ]
          }
        }', true);

        $result = json_decode('{
          "controller": "Controllers.Record",
          "boolFilterList": [
            "onlyMy"
          ],
          "sidePanels": {
            "detail": [
              {
                "name": "optedOut",
                "label": "Opted Out",
                "view": "Crm:TargetList.Record.Panels.OptedOut"
              },
              {
                "name": "populating",
                "label": "Populating",
                "view": "Advanced:TargetList.Record.Panels.Populating"
              }
            ],
            "edit": [
              {
                "name": "populating",
                "label": "Populating",
                "view": "Advanced:TargetList.Record.Panels.Populating"
              }
            ]
          },
          "views": {
            "detail": "Advanced:TargetList.Detail"
          },
          "recordViews": {
            "detail": "Advanced:TargetList.Record.Detail"
          }
        }', true);

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeWithBool()
    {
        $currentArray = [
          'fields' =>
          [
            'accountId' =>
            [
              'type' => 'varchar',
              'where' =>
              [
                '=' => 'contact.id IN ({value})',
              ],
              'len' => 255,
            ],
            'deleted' =>
            [
              'type' => 'bool',
              'default' => false,
              'trueValue' => true,
            ],
          ],
          'relations' =>
          [
          ],
        ];

        $newArray = [
          'fields' =>
          [
            'accountName' =>
            [
              'type' => 'foreign',
              'relation' => 'account',
              'foreign' => 'name',
            ],
            'accountId' =>
            [
              'type' => 'foreignId',
              'index' => true,
            ],
          ],
          'relations' =>
          [
            'createdBy' =>
            [
              'type' => 'belongsTo',
              'entity' => 'User',
              'key' => 'createdById',
              'foreignKey' => 'id',
            ],
          ],
        ];

        $result = [
          'fields' =>
          [
            'accountName' =>
            [
              'type' => 'foreign',
              'relation' => 'account',
              'foreign' => 'name',
            ],
            'accountId' =>
            [
              'type' => 'foreignId',
              'index' => true,
              'where' =>
              [
                '=' => 'contact.id IN ({value})',
              ],
              'len' => 255,
            ],
            'deleted' =>
            [
              'type' => 'bool',
              'default' => false,
              'trueValue' => true,
            ],
          ],
          'relations' =>
          [
            'createdBy' =>
            [
              'type' => 'belongsTo',
              'entity' => 'User',
              'key' => 'createdById',
              'foreignKey' => 'id',
            ],
          ],
        ];

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeWithFieldsDefs()
    {
        $currentArray = [
          'fields' =>
          [
            'aaa1' =>
            [
              'type' => 'enum',
              'required' => false,
              'options' =>
              [
                0 => 'a1',
                1 => 'a3',
                2 => 'a3',
              ],
              'isCustom' => true,
            ],
            'hfghgfh' =>
            [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'hfghfgh',
            ],
            'jghjghj' =>
            [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'jghjghjhg',
            ],
            'gdfgdfg' =>
            [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'gdfgdfg',
              'maxLength' => 70,
            ],
          ],
        ];

        $newArray = [
          'fields' =>
          [
            'aaa1' =>
            [
              'type' => 'enum',
              'required' => false,
              'options' =>
              [
                0 => 'a1',
              ],
              'isCustom' => true,
            ],
          ],
        ];

        $result = [
          'fields' =>
          [
            'aaa1' =>
            [
              'type' => 'enum',
              'required' => false,
              'options' =>
              [
                0 => 'a1',
              ],
              'isCustom' => true,
            ],
            'hfghgfh' =>
            [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'hfghfgh',
            ],
            'jghjghj' =>
            [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'jghjghjhg',
            ],
            'gdfgdfg' =>
            [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'gdfgdfg',
              'maxLength' => 70,
            ],
          ],
        ];

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeEmptyArray()
    {
        $currentArray = [
          'Call' => [
            'fields' =>
            [
              'accountId' =>
              [
                'type' => 'varchar',
                'where' =>
                [
                  '=' => 'contact.id IN ({value})',
                ],
                'len' => 255,
              ],
              'deleted' =>
              [
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ],
            ],
          ],
        ];

        $newArray = [
          'Call' => [
            'fields' =>
            [
            ],
          ],
        ];

        $result = $currentArray;

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeEmptyArray2()
    {
        $currentArray = [
          'Call' => [
            'fields' =>
            [
              'accountId' =>
              [
                'type' => 'varchar',
                'where' =>
                [
                  '=' => 'contact.id IN ({value})',
                ],
                'len' => 255,
              ],
              'deleted' =>
              [
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ],
            ],
          ],
        ];

        $newArray = [
          'Call' => [],
        ];

        $result = $currentArray;

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeEmptyArray3()
    {
        $currentArray = [
          'Call' => [
            'fields' =>
            [
              'accountId' =>
              [
                'type' => 'varchar',
                'where' =>
                [
                  '=' => 'contact.id IN ({value})',
                ],
                'len' => 255,
              ],
              'deleted' =>
              [
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ],
            ],
          ],
        ];

        $newArray = [
        ];

        $result = $currentArray;

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeCompleteTest()
    {
        $currentArray = [
            'fields' =>
            [
                'aaa1' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        0 => 'a1',
                        1 => 'a3',
                        2 => 'a3',
                    ],
                    'isCustom' => true,
                ],
                'append' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        'b1',
                        'b3',
                        'b3',
                    ],
                ],
                't1111' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '11111',
                ],
                't2222' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '2222',
                ],
                't3333' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '3333',
                    'maxLength' => 70,
                ],
            ],
        ];

        $newArray = [
            'fields' =>
            [
                'aaa1' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        'a1',
                    ],
                    'isCustom' => false,
                    'newValue' => 'NNNNN',
                ],
                'new111' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                ],
                'append' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        '__APPEND__',
                        'b4',
                        'b5',
                    ],
                ],
                'aloneAppend' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        '__APPEND__',
                        'c1',
                        'c2',
                    ],
                ],
            ],
        ];

        $result = [
            'fields' =>
            [
                'aaa1' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        0 => 'a1',
                    ],
                    'isCustom' => false,
                    'newValue' => 'NNNNN',
                ],
                'append' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        'b1',
                        'b3',
                        'b3',
                        'b4',
                        'b5',
                    ],
                ],
                't1111' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '11111',
                ],
                't2222' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '2222',
                ],
                't3333' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '3333',
                    'maxLength' => 70,
                ],
                'new111' =>
                [
                    'type' => 'varchar',
                    'required' => false,
                ],
                'aloneAppend' =>
                [
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    [
                        'c1',
                        'c2',
                    ],
                ],
            ],
        ];

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testToFormat()
    {
       $this->assertEquals('/Espo/Core/Utils', Util::toFormat('/Espo/Core/Utils', '/'));
       $this->assertEquals('\Espo\Core\Utils', Util::toFormat('/Espo/Core/Utils', '\\'));

       $this->assertEquals('/Espo/Core/Utils', Util::toFormat('\Espo\Core\Utils', '/'));
       $this->assertEquals('Espo\Core\Utils', Util::toFormat('Espo\Core\Utils', '\\'));
    }

    public function testConcatPath()
    {
        $result= Util::fixPath('dir1/dir2/file1.json');
        $this->assertEquals($result, Util::concatPath(Util::fixPath('dir1/dir2'), 'file1.json'));

        $result= Util::fixPath('dir1/dir2/file1.json');
        $this->assertEquals($result, Util::concatPath(Util::fixPath('dir1/dir2/'), 'file1.json'));

        $result= Util::fixPath('dir1/dir2/file1.json');
        $this->assertEquals($result, Util::concatPath(Util::fixPath('dir1/dir2/file1.json')));

        $input = array('dir1/dir2', 'file1.json');
        $input = array_map('\Espo\Core\Utils\Util::fixPath', $input);
        $result = Util::fixPath('dir1/dir2/file1.json');
        $this->assertEquals($result, Util::concatPath($input));

        $input = array('dir1/', 'dir2', 'file1.json');
        $input = array_map('\Espo\Core\Utils\Util::fixPath', $input);
        $result = Util::fixPath('dir1/dir2/file1.json');
        $this->assertEquals($result, Util::concatPath($input));
    }

    public function testArrayToObject(): void
    {
        $testArr= [
            'useCache' => true,
            'sub' =>  [
                'subV' => '125',
                'subO' => [
                    'subOV' => '125',
                ],
                'subList' => [
                    '0',
                    '1'
                ],
            ],
        ];

        $testResult= (object) [
            'useCache' => true,
        ];

        $testResult->sub = (object) [
            'subV' => '125',
        ];

        $testResult->sub->subO = (object) [
            'subOV' => '125',
        ];

        $testResult->sub->subList = ['0', '1'];

        $this->assertEquals($testResult, Util::arrayToObject($testArr));
    }

    public function testObjectToArray()
    {
        $testObj= (object) [
            'useCache' => true,
        ];
        $testObj->sub = (object) [
                'subV' => '125',
        ];
        $testObj->sub->subO = (object) [
                'subOV' => '125',
        ];

        $testResult= [
            'useCache' => true,
            'sub' =>  [
                'subV' => '125',
                'subO' => [
                    'subOV' => '125',
                ],
            ],
        ];

        $this->assertEquals($testResult, Util::objectToArray($testObj));
    }

    public function testGetNaming()
    {
        $this->assertEquals('myPrefixMyName', Util::getNaming('myName', 'myPrefix', 'prefix'));

        $this->assertEquals('myNameMyPostfix', Util::getNaming('myName', 'myPostfix', 'postfix'));
        $this->assertEquals('myNameMyPostfix', Util::getNaming('my_name', 'myPostfix', 'postfix', '_'));
        $this->assertEquals('myNameMyPostfix', Util::getNaming('my_name', 'my_postfix', 'postfix', '_'));
    }

    public function testReplaceInArray()
    {
        $testArray = [
            'option' => [
                'default' => '{0}',
                 'testKey' => [
                    '{0}' => 'testVal',
                 ],
            ],
        ];

        $testResult = [
            'option' => [
                'default' => 'DONE',
                 'testKey' => [
                    'DONE' => 'testVal',
                 ],
            ],
        ];

        $this->assertEquals($testResult, Util::replaceInArray('{0}', 'DONE', $testArray, true));
    }

    #[DataProvider('getClassNames')]
    public function testGetClassName1($path, $expectedClassName = 'Espo\EntryPoints\Download')
    {
        $this->assertEquals($expectedClassName, Util::getClassName($path));
    }


    public function testGetClassName2()
    {
        $this->assertEquals(
            'Espo\Modules\MyModule\EntryPoints\Test',
            Util::getClassName('custom/Espo/Modules/MyModule/EntryPoints/Test')
        );
    }

    static public function getClassNames(): array
    {
        return [
            'application/Espo/EntryPoints/Download.php' => ['application/Espo/EntryPoints/Download.php'],
            'custom/Espo/Modules/MyModule/EntryPoints/Download.php' => ['custom/Espo/EntryPoints/Download.php'],
            'Espo/EntryPoints/Download.php' => ['Espo/EntryPoints/Download.php'],
            'application/Espo/EntryPoints/Download' => ['application/Espo/EntryPoints/Download'],
            '\application\Espo\EntryPoints\Download' => ['application\Espo\EntryPoints\Download'],
        ];
    }

    public function testUnsetInArrayNotSingle()
    {
        $input = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subV' => '125',
                    'subO' => [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
        ];

        $unsets = [
            'Account' => [
                'sub.subO.subOV', 'sub.subV',
            ],
        ];

        $result = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subO' => [
                        'subOV2' => '125',
                    ],
                ],
            ],
        ];

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArraySingle()
    {
        $input = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subV' => '125',
                    'subO' => [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
        ];

        $unsets = [
            'Account.sub.subO.subOV', 'Account.sub.subV',
        ];

        $result = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subO' => [
                        'subOV2' => '125',
                    ],
                ],
            ],
        ];

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArrayTogether()
    {
        $input = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subV' => '125',
                    'subO' => [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
        ];

        $unsets = [
            'Account' => [
                'sub.subO.subOV',
            ],
            'Account.sub.subV',
        ];

        $result = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subO' => [
                        'subOV2' => '125',
                    ],
                ],
            ],
        ];

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArray()
    {
        $input = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subV' => '125',
                    'subO' => [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
            'Contact' => [
                'useCache' => true,
            ],
        ];

        $unsets = [
            'Account',
        ];

        $result = [
            'Contact' => [
                'useCache' => true,
            ],
        ];

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArrayByString()
    {
        $input = [
            'Account' => [
                'useCache' => true,
            ],
            'Contact' => [
                'useCache' => true,
            ],
        ];

        $unsets = 'Account.useCache';

        $result = [
            'Account' => [
            ],
            'Contact' => [
                'useCache' => true,
            ],
        ];

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArrayEmptyParent()
    {
        $input = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subV' => '125',
                    'subO' => [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
            'Contact' => [
                'useCache' => true,
            ],
            'Lead' => [
                'useCache' => true,
            ]
        ];

        $unsets = [
            'Account.useCache',
            'Account.sub',
            'Lead.useCache'
        ];

        $result = [
            'Contact' => [
                'useCache' => true,
            ]
        ];

        $this->assertEquals($result, Util::unsetInArray($input, $unsets, true));
    }

    public function testGetValueByKey1()
    {
        $inputArray = [
            'Account' => [
                'useCache' => true,
                'sub' =>  [
                    'subV' => '125',
                    'subO' => [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
            'Contact' => [
                'useCache' => true,
            ],
        ];


        $this->assertEquals($inputArray, Util::getValueByKey($inputArray));
        $this->assertEquals($inputArray, Util::getValueByKey($inputArray, ''));

        $this->assertEquals('125', Util::getValueByKey($inputArray, 'Account.sub.subV'));

        $result = ['useCache' => true];
        $this->assertEquals($result, Util::getValueByKey($inputArray, 'Contact'));

        $this->assertNull(Util::getValueByKey($inputArray, 'Contact.notExists'));

        $this->assertEquals('customReturns', Util::getValueByKey($inputArray, 'Contact.notExists', 'customReturns'));
        $this->assertTrue(Util::getValueByKey($inputArray, 'Contact.useCache', 'customReturns'));
    }

    public function testGetValueByKey2()
    {
        $inputArray = [
            'fields' => [
                'varchar' => [
                      'params' =>
                      [
                        [
                          'name' => 'required',
                          'type' => 'bool',
                          'default' => false,
                        ],
                        [
                          'name' => 'default',
                          'type' => 'varchar',
                        ],
                        [
                          'name' => 'maxLength',
                          'type' => 'int',
                        ],
                        [
                          'name' => 'trim',
                          'type' => 'bool',
                          'default' => true,
                        ],
                        [
                          'name' => 'audited',
                          'type' => 'bool',
                        ],
                      ],
                      'filter' => true,
                ],
            ]
        ];

        $this->assertNull(Util::getValueByKey($inputArray, 'fields.varchar.hookClassName'));
        $this->assertNull(Util::getValueByKey($inputArray, ['fields', 'varchar', 'hookClassName']));
        $this->assertEquals('customReturns', Util::getValueByKey($inputArray, 'Contact.notExists', 'customReturns'));
    }

    public function testGetValueByKeyWithObjects()
    {
        $inputObject = (object) [
            'Account' => (object) [
                'useCache' => true,
                'sub' =>  (object) [
                    'subV' => '125',
                    'subO' => (object) [
                        'subOV' => '125',
                        'subOV2' => '125',
                    ],
                ],
            ],
            'Contact' => (object) [
                'useCache' => true,
            ],
        ];

        $this->assertEquals($inputObject, Util::getValueByKey($inputObject));
        $this->assertEquals($inputObject, Util::getValueByKey($inputObject, ''));

        $this->assertEquals('125', Util::getValueByKey($inputObject, 'Account.sub.subV'));

        $result = (object) ['useCache' => true];
        $this->assertEquals($result, Util::getValueByKey($inputObject, 'Contact'));

        $this->assertNull(Util::getValueByKey($inputObject, 'Contact.notExists'));

        $this->assertEquals('customReturns', Util::getValueByKey($inputObject, 'Contact.notExists', 'customReturns'));
        $this->assertTrue(Util::getValueByKey($inputObject, 'Contact.useCache', 'customReturns'));
    }

    public function testUnsetInArrayByValue()
    {
        $newArray = json_decode('[
          "__APPEND__",
            {
               "name":"populating",
               "label":"Populating",
               "view":"Advanced:TargetList.Record.Panels.Populating"
            }
        ]', true);

        $result = json_decode('[
            {
               "name":"populating",
               "label":"Populating",
               "view":"Advanced:TargetList.Record.Panels.Populating"
            }
        ]', true);

        $this->assertEquals($result, Util::unsetInArrayByValue('__APPEND__', $newArray));
    }

    public function testUnsetInArrayByValueWithoutReindex()
    {
        $newArray = json_decode('[
          "__APPEND__",
            {
               "name":"populating",
               "label":"Populating",
               "view":"Advanced:TargetList.Record.Panels.Populating"
            }
        ]', true);

        $result = json_decode('{
          "1": {
            "name": "populating",
            "label": "Populating",
            "view": "Advanced:TargetList.Record.Panels.Populating"
          }
        }', true);

        $this->assertEquals($result, Util::unsetInArrayByValue('__APPEND__', $newArray, false));
    }

    public function testArrayDiff()
    {
        $array1 = array (
          'type' => 'enum',
          'options' =>
          array (
            0 => '',
            1 => 'Call',
            2 => 'Email',
            3 => 'Existing Customer',
            4 => 'Partner',
            5 => 'Public Relations',
            6 => 'Campaign',
            7 => 'Other',
          ),
          'default' => '',
          'required' => true,
          'isSorted' => false,
          'audited' => false,
          'readOnly' => false,
          'tooltip' => false,
          'newAttr1' => false,
        );

        $array2 = array (
          'type' => 'enum',
          'options' =>
          array (
            0 => '',
            1 => 'Call',
            2 => 'Email',
            3 => 'Existing Customer',
            4 => 'Partner',
            5 => 'Public Relations',
            6 => 'Web Site',
            7 => 'Campaign',
            8 => 'Other',
          ),
          'default' => '',
          'required' => false,
          'isSorted' => false,
          'audited' => false,
          'readOnly' => false,
          'tooltip' => false,
          'newAttr2' => false,
        );

        $result = array (
          'options' =>
          array (
            0 => '',
            1 => 'Call',
            2 => 'Email',
            3 => 'Existing Customer',
            4 => 'Partner',
            5 => 'Public Relations',
            6 => 'Web Site',
            7 => 'Campaign',
            8 => 'Other',
          ),
          'required' => false,
          'newAttr1' => false,
          'newAttr2' => false,
        );

        $this->assertEquals($result, Util::arrayDiff($array1, $array2));
    }

    static public function htmlList()
    {
        return [
            ['Test&lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;', 'Test<script>alert("test")</script>'],
            ['<p>Test</p>', '<p>Test</p>'],
            ['<p><b>Test</b></p>', '<p><b>Test</b></p>'],
            ['<pre>Test</pre>', '<pre>Test</pre>'],
            ['<p><b>Test</b> &lt;a href=&quot;#&quot;&gt;test link&lt;/a&gt;</p>', '<p><b>Test</b> <a href="#">test link</a></p>'],
            ['<strong>Test</strong>', '<strong>Test</strong>'],
        ];
    }

    #[DataProvider('htmlList')]
    public function testSanitizeHtml($expectedResult, $html)
    {
        $this->assertEquals($expectedResult, Util::sanitizeHtml($html));
    }

    static public function urlAddParamList()
    {
        return [
            ['https://test.link/?param1=1111', 'https://test.link', 'param1', '1111'],
            ['https://test.link/?param1=1111&param2=2222', 'https://test.link/?param1=1111', 'param2', '2222'],
            ['https://test.link/?param2=2222&param1=1111', 'https://test.link/?param2=2222', 'param1', '1111'],
            ['https://test.link/?param1=1111&param2=2222', 'https://test.link/?param1=1111&param2=2222', 'param1', '1111'],
            ['https://test.link/?param1=3333&param2=2222', 'https://test.link/?param1=1111&param2=2222', 'param1', '3333'],
            ['https://test.link/?param1=1111&param2=2222&new-param3=85%7BXjKbrNe%40%5D8', 'https://test.link/?param1=1111&param2=2222', 'new-param3', '85{XjKbrNe@]8'],
            ['/?param1=1111', '', 'param1', '1111'],
            ['/?param1=1111&param2=2222', '/?param1=1111', 'param2', '2222'],
        ];
    }

    #[DataProvider('urlAddParamList')]
    public function testUrlAddParam($expectedResult, $url, $paramName, $paramValue)
    {
        $this->assertEquals($expectedResult, Util::urlAddParam($url, $paramName, $paramValue));
    }

    static public function urlRemoveParamList()
    {
        return [
            ['https://test.link', 'https://test.link', 'param1'],
            ['https://test.link', 'https://test.link', 'param1', '/'],
            ['https://test.link/', 'https://test.link/', 'param1'],
            ['https://test.link/?param1=1111', 'https://test.link/?param1=1111', 'param2'],
            ['https://test.link', 'https://test.link/?param1=1111', 'param1'],
            ['https://test.link/', 'https://test.link/?param1=1111', 'param1', '/'],
            ['https://test.link/?param2=2222', 'https://test.link/?param1=1111&param2=2222', 'param1'],
            ['https://test.link/?param1=1111', 'https://test.link/?param1=1111&param2=2222', 'param2'],
            ['https://test.link/?param1=1111&param2=2222', 'https://test.link/?param1=1111&param2=2222&new-param3=85%7BXjKbrNe%40%5D8', 'new-param3'],
        ];
    }

    #[DataProvider('urlRemoveParamList')]
    public function testUrlRemoveParam($expectedResult, $url, $paramName, $suffix = '')
    {
        $this->assertEquals($expectedResult, Util::urlRemoveParam($url, $paramName, $suffix));
    }
}
