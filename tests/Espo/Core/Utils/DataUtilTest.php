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

use Espo\Core\Utils\DataUtil;

class DataUtilTest extends \PHPUnit_Framework_TestCase
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
        DataUtil::unsetByKey($data1, ['Test.fields.fieldVarchar']);
        $this->assertEquals($expectedResultData, $data1);

        $data2 = $data;
        DataUtil::unsetByKey($data2, 'Test.fields.fieldVarchar');
        $this->assertEquals($expectedResultData, $data2);

        $data3 = $data;
        DataUtil::unsetByKey($data3, [['Test', 'fields', 'fieldVarchar']]);
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

        DataUtil::unsetByKey($data, [['Test', 'fields', 'fieldVarchar'], ['Test', 'fields', 'fieldText'], ['Test', 'indexes', 'test', 'name']], true);
        $this->assertEquals($expectedResultData, $data);
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

        DataUtil::unsetByValue($data, '__APPEND__');
        $this->assertEquals($expectedResultData, $data);
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

}

