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

namespace tests\Espo\Core\Utils;

use Espo\Core\Utils\DataUtil;

class DataUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testUnsetByKey1()
    {
        $data = json_decode('{
            "Test": {
                "fields": {
                    "fieldVarchar": {
                        "type": "varchar",
                        "default": "hello"
                    },
                    "fieldText": {
                        "type": "text"
                    }
                }
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "fields": {
                    "fieldText": {
                        "type": "text"
                    }
                }
            }
        }');

        $data1 = $data;

        $data1 = DataUtil::unsetByKey($data1, ['Test.fields.fieldVarchar']);

        $this->assertEquals($expectedResultData, $data1);

        $data2 = $data;

        $data2 = DataUtil::unsetByKey($data2, 'Test.fields.fieldVarchar');

        $this->assertEquals($expectedResultData, $data2);

        $data3 = $data;

        $data3 = DataUtil::unsetByKey($data3, [['Test', 'fields', 'fieldVarchar']]);

        $this->assertEquals($expectedResultData, $data3);
    }


    public function testUnsetByKey2()
    {
        $data = json_decode('{
            "Test": {
                "fields": {
                    "fieldVarchar": {
                        "type": "varchar",
                        "default": "hello"
                    },
                    "fieldText": {
                        "type": "text"
                    }
                },
                "links": {
                    "test": {
                        "type": "hasMany"
                    }
                },
                "indexes": {
                    "test": {
                        "name": "test"
                    }
                }
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "links": {
                    "test": {
                        "type": "hasMany"
                    }
                }
            }
        }');

        $data1 = DataUtil::unsetByKey(
            $data,
            [
                ['Test', 'fields', 'fieldVarchar'],
                ['Test', 'fields', 'fieldText'],
                ['Test', 'indexes', 'test', 'name'],
            ],
            true
        );

        $this->assertEquals($expectedResultData, $data1);
    }

    public function testUnsetByValue()
    {
        $data = json_decode('{
            "Test": {
                "fields": {
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["__APPEND__", "hello"]
                    }
                }
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "fields": {
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["hello"]
                    }
                }
            }
        }');

        $data1 = DataUtil::unsetByValue($data, '__APPEND__');

        $this->assertEquals($expectedResultData, $data1);
    }

    public function testMerge1()
    {
        $data1 = json_decode('{
            "Test": {
                "fields": {
                    "fieldVarchar": {
                        "type": "varchar",
                        "default": "hello"
                    }
                }
            }
        }');

        $data2 = json_decode('{
            "Test": {
                "fields": {
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["__APPEND__", "hello"]
                    }
                }
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "fields": {
                    "fieldVarchar": {
                        "type": "varchar",
                        "default": "hello"
                    },
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["hello"]
                    }
                }
            }
        }');

        $data = DataUtil::merge($data1, $data2);

        $this->assertEquals($expectedResultData, $data);
    }

    public function testMerge2()
    {
        $data1 = json_decode('{
            "Test": {
                "fields": {
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["test"]
                    }
                }
            }
        }');

        $data2 = json_decode('{
            "Test": {
                "fields": {
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["__APPEND__", "hello"]
                    }
                }
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "fields": {
                    "fieldEnum": {
                        "type": "enum",
                        "options": ["test", "hello"]
                    }
                }
            }
        }');

        $data = DataUtil::merge($data1, $data2);

        $this->assertEquals($expectedResultData, $data);
    }

    public function testMerge3()
    {
        $data1 = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_1"
                    }
                ]
            }
        }');

        $data2 = json_decode('{
            "Test": {
                "panelList": [
                    "__APPEND__",
                    {
                        "name": "test_2"
                    },
                    {
                        "name": "test_3"
                    }
                ]
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_1"
                    },
                    {
                        "name": "test_2"
                    },
                    {
                        "name": "test_3"
                    }
                ]
            }
        }');

        $data = DataUtil::merge($data1, $data2);

        $this->assertEquals($expectedResultData, $data);
    }

    public function testMerge4()
    {
        $data1 = json_decode('[
            {
                "name": "test_1"
            }
        ]');

        $data2 = json_decode('[
            "__APPEND__",
            {
                "name": "test_2"
            }
        ]');

        $expectedResultData = json_decode('[
            {
                "name": "test_1"
            },
            {
                "name": "test_2"
            }
        ]');

        $data = DataUtil::merge($data1, $data2);

        $this->assertEquals($expectedResultData, $data);
    }

    public function testMerge5()
    {
        $data1 = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_1"
                    }
                ]
            }
        }');

        $data2 = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_2"
                    },
                    {
                        "name": "test_3"
                    }
                ]
            }
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_2"
                    },
                    {
                        "name": "test_3"
                    }
                ]
            }
        }');

        $data = DataUtil::merge($data1, $data2);

        $this->assertEquals($expectedResultData, $data);
    }


    public function testMerge6()
    {
        $data1 = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_1"
                    }
                ]
            },
            "Hello": {
                "name": "hello"
            }
        }');

        $data2 = json_decode('{
            "Test": {
                "panelList": [
                    "__APPEND__",
                    {
                        "name": "test_2"
                    }
                ]
            },
            "Hello": false
        }');

        $expectedResultData = json_decode('{
            "Test": {
                "panelList": [
                    {
                        "name": "test_1"
                    },
                    {
                        "name": "test_2"
                    }
                ]
            },
            "Hello": false
        }');

        $data = DataUtil::merge($data1, $data2);

        $this->assertEquals($expectedResultData, $data);
    }

    public function testMerge7()
    {
        $obj1 = (object) [
            'defaultPermissions',
            'logger',
            'devMode'
        ];
        $obj2Main = (object) [
            //45 => '125',
            'sub' => (object) [
                'subV' => '125',
            ],
        ];
        $expectedResultData = (object) [
            'defaultPermissions',
            'logger',
            'devMode',
            //45 => '125',
            'sub' => (object) [
                'subV' => '125',
            ],
        ];
        $this->assertEquals($expectedResultData, DataUtil::merge($obj1, $obj2Main));

        $obj1 = (object) [
            'datetime' => (object) [
                'dateFormat' => 'Y-m-d',
                'timeFormat' => 'H:i:s',
            ],
        ];
        $obj2Main = (object) [
            'datetime' => (object) [
                'dateFormat' => 'MyDateFormat',
            ],
        ];
        $expectedResultData = (object) [
            'datetime' => (object) [
                'dateFormat' => 'MyDateFormat',
                'timeFormat' => 'H:i:s',
            ],
        ];
        $this->assertEquals($expectedResultData, DataUtil::merge($obj1, $obj2Main));

        $obj1 = (object) [
            'database' => (object) [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'espocrm',
                'user' => 'root',
                'password' => '',
            ],
        ];
        $obj2Main = (object) [
            'database' => (object) [
                'password' => 'MyPass',
            ],
        ];
        $expectedResultData = (object) [
            'database' => (object) [
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'espocrm',
                'user' => 'root',
                'password' => 'MyPass',
            ],
        ];
        $this->assertEquals($expectedResultData, DataUtil::merge($obj1, $obj2Main));
    }

    public function testMerge8()
    {
        $d1 = (object) [
          'hello' => 'world',
          'man' => (object) [
            'test' => [
              0 => ['name' => 'test 1'],
              1 => ['name' => 'test 2']
            ]
          ]
        ];
        $d2 = (object) [
          'test' => []
        ];
        $d3 = (object) [
          'man' => (object) [
            'test' => [
              0 => '__APPEND__',
              1 => ['name' => 'test 3']
            ]
          ]
        ];
        $expected = (object) [
            'test' => [],
            'hello' => 'world',
            'man' => (object) [
              'test' => [
                0 => ['name' => 'test 1'],
                1 => ['name' => 'test 2'],
                2 => ['name' => 'test 3']
              ]
            ]
        ];

        $expectedResultData = DataUtil::merge(DataUtil::merge($d2, $d1), $d3);
        $this->assertEquals($expected, $expectedResultData);
    }

    public function testMergeWithAppend()
    {
        $data1 = (object) [
            'entityDefs' => (object) [
                'Attachment' => (object) [
                  'fields' => (object) [
                    'name' => (object) [
                      'type' => 'varchar',
                      'required' => true,
                    ],
                    'type' => (object) [
                      'type' => 'varchar',
                      'maxLength' => 36,
                    ],
                    'size' => (object) [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                    'sizeInt' => (object) [
                      'type' => 'enum',
                      'value' => [0, 1, 2],
                    ],
                    'merged' => (object) [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                    'mergedInt' => (object) [
                      'type' => 'enum',
                      'value' => [0, 1, 2],
                    ],
                  ],
                ],
                'Contact' => (object) [
                  'fields' => (object) [
                    'name' => (object) [
                      'type' => 'varchar',
                      'required' => true,
                    ],
                    'type' => (object) [
                      'type' => 'varchar',
                      'maxLength' => 36,
                    ],
                    'size' => (object) [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                    'merged' => (object) [
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ],
                  ],
                ],
            ],
        'MyCustom' => (object) [
              'fields' => (object) [
                'name' => (object) [
                  'type' => 'varchar',
                  'required' => true,
                ],
              ],
            ],
        ];

        $data2 = (object) [
            'entityDefs' => (object) [
                'Attachment' => (object) [
                  'fields' => (object) [
                    'name' => (object) [
                      'type' => 'varchar',
                      'required' => false,
                      'NEW' => 'NEWVAL',
                    ],
                    'type' => (object) [
                      'type' => 'NETYPE',
                    ],
                    'size' => (object) [
                      'type' => 'enum',
                      'value' => ["B1", "B2", "B3"],
                    ],
                    'sizeInt' => (object) [
                      'type' => 'enum',
                      'value' => [5, 8, 9],
                    ],
                    'merged' => (object) [
                      'type' => 'enum',
                      'value' => ["__APPEND__", "B1", "B2", "B3"],
                    ],
                    'mergedInt' => (object) [
                      'type' => 'enum',
                      'value' => ['__APPEND__', 5, 8, 9],
                    ],
                  ],
                  'list' => (object) [
                    'test' => 'Here',
                  ],
                ],
                'Contact' => (object) [
                  'fields' => (object) [
                    'name' => (object) [
                      'type' => 'varchar',
                      'required' => false,
                      'NEW' => 'NEWVAL',
                    ],
                    'type' => (object) [
                      'type' => 'NEW',
                      'maxLength' => 1000000,
                    ],
                    'size' => (object) [
                      'type' => 'enum',
                      'value' => ["B1", "B2", "B3"],
                    ],
                    'merged' => (object) [
                      'type' => 'enum',
                      'value' => ["__APPEND__", "B1", "B2", "B3"],
                    ],
                  ],
                ],
            ],
        ];

        $expectedResultData = (object) [
          'entityDefs' => (object) [
            'Attachment' => (object) [
              'fields' => (object) [
                'name' => (object) [
                  'type' => 'varchar',
                  'required' => false,
                  'NEW' => 'NEWVAL',
                ],
                'type' => (object) [
                  'type' => 'NETYPE',
                  'maxLength' => 36,
                ],
                'size' => (object) [
                  'type' => 'enum',
                  'value' => ['B1', 'B2', 'B3'],
                ],
                'sizeInt' => (object) [
                  'type' => 'enum',
                  'value' => [5, 8, 9],
                ],
                'merged' => (object) [
                  'type' => 'enum',
                  'value' => ['v1', 'v2', 'v3', 'B1', 'B2', 'B3'],
                ],
                'mergedInt' => (object) [
                  'type' => 'enum',
                  'value' => [0, 1, 2, 5, 8, 9],
                ],
              ],
              'list' => (object) [
                'test' => 'Here',
              ],
            ],
            'Contact' => (object) [
              'fields' => (object) [
                'name' => (object) [
                  'type' => 'varchar',
                  'required' => false,
                  'NEW' => 'NEWVAL',
                ],
                'type' => (object) [
                  'type' => 'NEW',
                  'maxLength' => 1000000,
                ],
                'size' => (object) [
                  'type' => 'enum',
                  'value' => ['B1', 'B2', 'B3'],
                ],
                'merged' => (object) [
                  'type' => 'enum',
                  'value' => ['v1', 'v2', 'v3', 'B1', 'B2', 'B3'],
                ],
              ],
            ],
          ],
          'MyCustom' => (object) [
            'fields' => (object) [
              'name' => (object) [
                'type' => 'varchar',
                'required' => true,
              ],
            ],
          ],
        ];

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeWithAppend2()
    {
        $data1 = json_decode('{
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
        }');

        $data2 = json_decode('{
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
        }');

        $expectedResultData = json_decode('{
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
        }');

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeWithBool()
    {
        $data1 = (object) [
          'fields' => (object) [
            'accountId' => (object) [
              'type' => 'varchar',
              'where' => (object) [
                '=' => 'contact.id IN ({value})',
              ],
              'len' => 255,
            ],
            'deleted' => (object) [
              'type' => 'bool',
              'default' => false,
              'trueValue' => true,
            ],
          ],
          'relations' =>
          (object) [
          ],
        ];

        $data2 = (object) [
          'fields' => (object) [
            'accountName' => (object) [
              'type' => 'foreign',
              'relation' => 'account',
              'foreign' => 'name',
            ],
            'accountId' => (object) [
              'type' => 'foreignId',
              'index' => true,
            ],
          ],
          'relations' => (object) [
            'createdBy' => (object) [
              'type' => 'belongsTo',
              'entity' => 'User',
              'key' => 'createdById',
              'foreignKey' => 'id',
            ],
          ],
        ];

        $expectedResultData = (object) [
          'fields' => (object) [
            'accountName' => (object) [
              'type' => 'foreign',
              'relation' => 'account',
              'foreign' => 'name',
            ],
            'accountId' => (object) [
              'type' => 'foreignId',
              'index' => true,
              'where' => (object) [
                '=' => 'contact.id IN ({value})',
              ],
              'len' => 255,
            ],
            'deleted' => (object) [
              'type' => 'bool',
              'default' => false,
              'trueValue' => true,
            ],
          ],
          'relations' => (object) [
            'createdBy' => (object) [
              'type' => 'belongsTo',
              'entity' => 'User',
              'key' => 'createdById',
              'foreignKey' => 'id',
            ],
          ],
        ];

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeWithFieldsDefs()
    {
        $data1 = (object) [
          'fields' => (object) [
            'aaa1' => (object) [
              'type' => 'enum',
              'required' => false,
              'options' => [
                0 => 'a1',
                1 => 'a3',
                2 => 'a3',
              ],
              'isCustom' => true,
            ],
            'hfghgfh' => (object) [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'hfghfgh',
            ],
            'jghjghj' => (object) [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'jghjghjhg',
            ],
            'gdfgdfg' => (object) [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'gdfgdfg',
              'maxLength' => 70,
            ],
          ],
        ];

        $data2 = (object) [
          'fields' => (object) [
            'aaa1' => (object) [
              'type' => 'enum',
              'required' => false,
              'options' => [
                0 => 'a1',
              ],
              'isCustom' => true,
            ],
          ],
        ];

        $expectedResultData = (object) [
          'fields' => (object) [
            'aaa1' => (object) [
              'type' => 'enum',
              'required' => false,
              'options' => [
                0 => 'a1',
              ],
              'isCustom' => true,
            ],
            'hfghgfh' => (object) [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'hfghfgh',
            ],
            'jghjghj' => (object) [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'jghjghjhg',
            ],
            'gdfgdfg' => (object) [
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'gdfgdfg',
              'maxLength' => 70,
            ],
          ],
        ];

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeEmptyArray()
    {
        $data1 = (object) [
          'Call' => (object) [
            'fields' => (object) [
              'accountId' => (object) [
                'type' => 'varchar',
                'where' => (object) [
                  '=' => 'contact.id IN ({value})',
                ],
                'len' => 255,
              ],
              'deleted' => (object) [
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ],
            ],
          ],
        ];

        $data2 = (object) [
          'Call' => (object) [
            'fields' => (object) [
            ],
          ],
        ];

        $expectedResultData = $data1;

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeEmptyArray2()
    {
        $data1 = (object) [
          'Call' => (object) [
            'fields' => (object) [
              'accountId' => (object) [
                'type' => 'varchar',
                'where' => (object) [
                  '=' => 'contact.id IN ({value})',
                ],
                'len' => 255,
              ],
              'deleted' => (object) [
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ],
            ],
          ],
        ];

        $data2 = (object) [
          'Call' => (object) [],
        ];

        $expectedResultData = $data1;

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeEmptyArray3()
    {
        $data1 = (object) [
          'Call' => (object) [
            'fields' => (object) [
              'accountId' => (object) [
                'type' => 'varchar',
                'where' => (object) [
                  '=' => 'contact.id IN ({value})',
                ],
                'len' => 255,
              ],
              'deleted' => (object) [
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ],
            ],
          ],
        ];

        $data2 = (object) [
        ];

        $expectedResultData = $data1;

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }

    public function testMergeCompleteTest()
    {
        $data1 = (object) [
            'fields' => (object) [
                'aaa1' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        0 => 'a1',
                        1 => 'a3',
                        2 => 'a3',
                    ],
                    'isCustom' => true,
                ],
                'append' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        'b1',
                        'b3',
                        'b3',
                    ],
                ],
                't1111' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '11111',
                ],
                't2222' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '2222',
                ],
                't3333' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '3333',
                    'maxLength' => 70,
                ],
            ],
        ];

        $data2 = (object) [
            'fields' => (object) [
                'aaa1' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        'a1',
                    ],
                    'isCustom' => false,
                    'newValue' => 'NNNNN',
                ],
                'new111' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                ],
                'append' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        '__APPEND__',
                        'b4',
                        'b5',
                    ],
                ],
                'aloneAppend' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        '__APPEND__',
                        'c1',
                        'c2',
                    ],
                ],
            ],
        ];

        $expectedResultData = (object) [
            'fields' => (object) [
                'aaa1' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        0 => 'a1',
                    ],
                    'isCustom' => false,
                    'newValue' => 'NNNNN',
                ],
                'append' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        'b1',
                        'b3',
                        'b3',
                        'b4',
                        'b5',
                    ],
                ],
                't1111' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '11111',
                ],
                't2222' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '2222',
                ],
                't3333' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '3333',
                    'maxLength' => 70,
                ],
                'new111' => (object) [
                    'type' => 'varchar',
                    'required' => false,
                ],
                'aloneAppend' => (object) [
                    'type' => 'enum',
                    'required' => false,
                    'options' => [
                        'c1',
                        'c2',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedResultData, DataUtil::merge($data1, $data2));
    }
}

