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

namespace tests\Espo\Core\Utils;

use Espo\Core\Utils\Util;

class UtilTest extends \PHPUnit\Framework\TestCase
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

        $input = array(
            'detail',
            'my_detail_view',
        );
        $result = array(
            'detail',
            'myDetailView',
        );
        $this->assertEquals($result, Util::toCamelCase($input));
    }

    public function testFromCamelCase()
    {
        $this->assertEquals('detail', Util::fromCamelCase('detail'));
        $this->assertEquals('detail-view', Util::fromCamelCase('detailView', '-'));
        $this->assertEquals('my_detail_view', Util::fromCamelCase('myDetailView'));
        $this->assertEquals('my_f_f', Util::fromCamelCase('myFF'));

        $input = array(
            'detail',
            'myDetailView',
            'myFF',
        );
        $result = array(
            'detail',
            'my_detail_view',
            'my_f_f',
        );
        $this->assertEquals($result, Util::fromCamelCase($input));
    }

    public function testToUnderScore()
    {
        $this->assertEquals('detail', Util::toUnderScore('detail'));
        $this->assertEquals('detail_view', Util::toUnderScore('detailView'));
        $this->assertEquals('my_detail_view', Util::toUnderScore('myDetailView'));
        $this->assertEquals('my_f_f', Util::toUnderScore('myFF'));

        $input = array(
            'detail',
            'detailView',
            'myDetailView',
            'myFF',
        );
        $result = array(
            'detail',
            'detail_view',
            'my_detail_view',
            'my_f_f',
        );
        $this->assertEquals($result, Util::toUnderScore($input));
    }

    public function testMerge2()
    {
        $d1 = array(
          'hello' => 'world',
          'man' => array(
            'test' => [
              0 => ['name' => 'test 1'],
              1 => ['name' => 'test 2']
            ]
          )
        );
        $d2 = array(
          'test' => []
        );
        $d3 = array(
          'man' => array(
            'test' => [
              0 => '__APPEND__',
              1 => ['name' => 'test 3']
            ]
          )
        );
        $expected = array(
            'test' => [],
            'hello' => 'world',
            'man' => array(
              'test' => [
                0 => ['name' => 'test 1'],
                1 => ['name' => 'test 2'],
                2 => ['name' => 'test 3']
              ]
            )
        );

        $result = Util::merge(Util::merge($d2, $d1), $d3);
        $this->assertEquals($expected, $result);
    }

    public function testMerge()
    {
        $array1= array(
            'defaultPermissions',
            'logger',
            'devMode'
        );
        $array2Main= array(
            45 => '125',
            'sub' =>  array (
                'subV' => '125',
            ),
        );
        $result= array(
            'defaultPermissions',
            'logger',
            'devMode',
            45 => '125',
            'sub' =>  array (
                'subV' => '125',
            ),
        );
        $this->assertEquals($result, Util::merge($array1, $array2Main));


        $array1= array(
            'datetime' =>
              array (
                'dateFormat' => 'Y-m-d',
                'timeFormat' => 'H:i:s',
              ),
        );
        $array2Main= array(
            'datetime' =>
              array (
                'dateFormat' => 'MyDateFormat',
              ),
        );
        $result= array(
            'datetime' =>
              array (
                'dateFormat' => 'MyDateFormat',
                'timeFormat' => 'H:i:s',
              ),
        );
        $this->assertEquals($result, Util::merge($array1, $array2Main));


        $array1= array(
            'database' =>
              array (
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'espocrm',
                'user' => 'root',
                'password' => '',
              ),
        );
        $array2Main= array(
            'database' =>
              array (
                'password' => 'MyPass',
              ),
        );
        $result= array(
            'database' =>
              array (
                'driver' => 'pdo_mysql',
                'host' => 'localhost',
                'dbname' => 'espocrm',
                'user' => 'root',
                'password' => 'MyPass',
              ),
        );
        $this->assertEquals($result, Util::merge($array1, $array2Main));
    }

    public function testMergeWithAppend()
    {
        $currentArray = array(
            'entityDefs' =>
              array (
                'Attachment' =>
                array (
                  'fields' =>
                  array (
                    'name' =>
                    array (
                      'type' => 'varchar',
                      'required' => true,
                    ),
                    'type' =>
                    array (
                      'type' => 'varchar',
                      'maxLength' => 36,
                    ),
                    'size' =>
                    array (
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ),
                    'sizeInt' =>
                    array (
                      'type' => 'enum',
                      'value' => [0, 1, 2],
                    ),
                    'merged' =>
                    array (
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ),
                    'mergedInt' =>
                    array (
                      'type' => 'enum',
                      'value' => [0, 1, 2],
                    ),
                  ),
                ),
                'Contact' =>
                array (
                  'fields' =>
                  array (
                    'name' =>
                    array (
                      'type' => 'varchar',
                      'required' => true,
                    ),
                    'type' =>
                    array (
                      'type' => 'varchar',
                      'maxLength' => 36,
                    ),
                    'size' =>
                    array (
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ),
                    'merged' =>
                    array (
                      'type' => 'enum',
                      'value' => ["v1", "v2", "v3"],
                    ),
                  ),
                ),
            ),
        'MyCustom' =>
            array (
              'fields' =>
              array (
                'name' =>
                array (
                  'type' => 'varchar',
                  'required' => true,
                ),
              ),
            ),
        );

        $newArray = array(
            'entityDefs' =>
              array (
                'Attachment' =>
                array (
                  'fields' =>
                  array (
                    'name' =>
                    array (
                      'type' => 'varchar',
                      'required' => false,
                      'NEW' => 'NEWVAL',
                    ),
                    'type' =>
                    array (
                      'type' => 'NETYPE',
                    ),
                    'size' =>
                    array (
                      'type' => 'enum',
                      'value' => ["B1", "B2", "B3"],
                    ),
                    'sizeInt' =>
                    array (
                      'type' => 'enum',
                      'value' => [5, 8, 9],
                    ),
                    'merged' =>
                    array (
                      'type' => 'enum',
                      'value' => ["__APPEND__", "B1", "B2", "B3"],
                    ),
                    'mergedInt' =>
                    array (
                      'type' => 'enum',
                      'value' => ['__APPEND__', 5, 8, 9],
                    ),
                  ),
                  'list' =>
                  array (
                    'test' => 'Here',
                  ),
                ),
                'Contact' =>
                array (
                  'fields' =>
                  array (
                    'name' =>
                    array (
                      'type' => 'varchar',
                      'required' => false,
                      'NEW' => 'NEWVAL',
                    ),
                    'type' =>
                    array (
                      'type' => 'NEW',
                      'maxLength' => 1000000,
                    ),
                    'size' =>
                    array (
                      'type' => 'enum',
                      'value' => ["B1", "B2", "B3"],
                    ),
                    'merged' =>
                    array (
                      'type' => 'enum',
                      'value' => ["__APPEND__", "B1", "B2", "B3"],
                    ),
                  ),
                ),
            ),
        );


        $result = array (
          'entityDefs' =>
          array (
            'Attachment' =>
            array (
              'fields' =>
              array (
                'name' =>
                array (
                  'type' => 'varchar',
                  'required' => false,
                  'NEW' => 'NEWVAL',
                ),
                'type' =>
                array (
                  'type' => 'NETYPE',
                  'maxLength' => 36,
                ),
                'size' =>
                array (
                  'type' => 'enum',
                  'value' =>
                  array (
                    0 => 'B1',
                    1 => 'B2',
                    2 => 'B3',
                  ),
                ),
                'sizeInt' =>
                array (
                  'type' => 'enum',
                  'value' =>
                  array (
                    0 => 5,
                    1 => 8,
                    2 => 9,
                  ),
                ),
                'merged' =>
                array (
                  'type' => 'enum',
                  'value' =>
                  array (
                    0 => 'v1',
                    1 => 'v2',
                    2 => 'v3',
                    3 => 'B1',
                    4 => 'B2',
                    5 => 'B3',
                  ),
                ),
                'mergedInt' =>
                array (
                  'type' => 'enum',
                  'value' =>
                  array (
                    0 => 0,
                    1 => 1,
                    2 => 2,
                    3 => 5,
                    4 => 8,
                    5 => 9,
                  ),
                ),
              ),
              'list' =>
              array (
                'test' => 'Here',
              ),
            ),
            'Contact' =>
            array (
              'fields' =>
              array (
                'name' =>
                array (
                  'type' => 'varchar',
                  'required' => false,
                  'NEW' => 'NEWVAL',
                ),
                'type' =>
                array (
                  'type' => 'NEW',
                  'maxLength' => 1000000,
                ),
                'size' =>
                array (
                  'type' => 'enum',
                  'value' =>
                  array (
                    0 => 'B1',
                    1 => 'B2',
                    2 => 'B3',
                  ),
                ),
                'merged' =>
                array (
                  'type' => 'enum',
                  'value' =>
                  array (
                    0 => 'v1',
                    1 => 'v2',
                    2 => 'v3',
                    3 => 'B1',
                    4 => 'B2',
                    5 => 'B3',
                  ),
                ),
              ),
            ),
          ),
          'MyCustom' =>
          array (
            'fields' =>
            array (
              'name' =>
              array (
                'type' => 'varchar',
                'required' => true,
              ),
            ),
          ),
        );

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
        $currentArray = array (
          'fields' =>
          array (
            'accountId' =>
            array (
              'type' => 'varchar',
              'where' =>
              array (
                '=' => 'contact.id IN ({value})',
              ),
              'len' => 255,
            ),
            'deleted' =>
            array (
              'type' => 'bool',
              'default' => false,
              'trueValue' => true,
            ),
          ),
          'relations' =>
          array (
          ),
        );

        $newArray = array (
          'fields' =>
          array (
            'accountName' =>
            array (
              'type' => 'foreign',
              'relation' => 'account',
              'foreign' => 'name',
            ),
            'accountId' =>
            array (
              'type' => 'foreignId',
              'index' => true,
            ),
          ),
          'relations' =>
          array (
            'createdBy' =>
            array (
              'type' => 'belongsTo',
              'entity' => 'User',
              'key' => 'createdById',
              'foreignKey' => 'id',
            ),
          ),
        );

        $result = array (
          'fields' =>
          array (
            'accountName' =>
            array (
              'type' => 'foreign',
              'relation' => 'account',
              'foreign' => 'name',
            ),
            'accountId' =>
            array (
              'type' => 'foreignId',
              'index' => true,
              'where' =>
              array (
                '=' => 'contact.id IN ({value})',
              ),
              'len' => 255,
            ),
            'deleted' =>
            array (
              'type' => 'bool',
              'default' => false,
              'trueValue' => true,
            ),
          ),
          'relations' =>
          array (
            'createdBy' =>
            array (
              'type' => 'belongsTo',
              'entity' => 'User',
              'key' => 'createdById',
              'foreignKey' => 'id',
            ),
          ),
        );

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeWithFieldsDefs()
    {
        $currentArray = array (
          'fields' =>
          array (
            'aaa1' =>
            array (
              'type' => 'enum',
              'required' => false,
              'options' =>
              array (
                0 => 'a1',
                1 => 'a3',
                2 => 'a3',
              ),
              'isCustom' => true,
            ),
            'hfghgfh' =>
            array (
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'hfghfgh',
            ),
            'jghjghj' =>
            array (
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'jghjghjhg',
            ),
            'gdfgdfg' =>
            array (
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'gdfgdfg',
              'maxLength' => 70,
            ),
          ),
        );

        $newArray = array (
          'fields' =>
          array (
            'aaa1' =>
            array (
              'type' => 'enum',
              'required' => false,
              'options' =>
              array (
                0 => 'a1',
              ),
              'isCustom' => true,
            ),
          ),
        );

        $result = array (
          'fields' =>
          array (
            'aaa1' =>
            array (
              'type' => 'enum',
              'required' => false,
              'options' =>
              array (
                0 => 'a1',
              ),
              'isCustom' => true,
            ),
            'hfghgfh' =>
            array (
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'hfghfgh',
            ),
            'jghjghj' =>
            array (
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'jghjghjhg',
            ),
            'gdfgdfg' =>
            array (
              'type' => 'varchar',
              'required' => false,
              'isCustom' => true,
              'default' => 'gdfgdfg',
              'maxLength' => 70,
            ),
          ),
        );

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeEmptyArray()
    {
        $currentArray = array(
          'Call' =>array (
            'fields' =>
            array (
              'accountId' =>
              array (
                'type' => 'varchar',
                'where' =>
                array (
                  '=' => 'contact.id IN ({value})',
                ),
                'len' => 255,
              ),
              'deleted' =>
              array (
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ),
            ),
          ),
        );

        $newArray = array(
          'Call' =>array (
            'fields' =>
            array (
            ),
          ),
        );

        $result = $currentArray;

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeEmptyArray2()
    {
        $currentArray = array(
          'Call' => array (
            'fields' =>
            array (
              'accountId' =>
              array (
                'type' => 'varchar',
                'where' =>
                array (
                  '=' => 'contact.id IN ({value})',
                ),
                'len' => 255,
              ),
              'deleted' =>
              array (
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ),
            ),
          ),
        );

        $newArray = array(
          'Call' => array (),
        );

        $result = $currentArray;

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeEmptyArray3()
    {
        $currentArray = array(
          'Call' =>array (
            'fields' =>
            array (
              'accountId' =>
              array (
                'type' => 'varchar',
                'where' =>
                array (
                  '=' => 'contact.id IN ({value})',
                ),
                'len' => 255,
              ),
              'deleted' =>
              array (
                'type' => 'bool',
                'default' => false,
                'trueValue' => true,
              ),
            ),
          ),
        );

        $newArray = array(
        );

        $result = $currentArray;

        $this->assertEquals($result, Util::merge($currentArray, $newArray));
    }

    public function testMergeCompleteTest()
    {
        $currentArray = array (
            'fields' =>
            array (
                'aaa1' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        0 => 'a1',
                        1 => 'a3',
                        2 => 'a3',
                    ),
                    'isCustom' => true,
                ),
                'append' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        'b1',
                        'b3',
                        'b3',
                    ),
                ),
                't1111' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '11111',
                ),
                't2222' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '2222',
                ),
                't3333' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '3333',
                    'maxLength' => 70,
                ),
            ),
        );

        $newArray = array (
            'fields' =>
            array (
                'aaa1' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        'a1',
                    ),
                    'isCustom' => false,
                    'newValue' => 'NNNNN',
                ),
                'new111' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                ),
                'append' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        '__APPEND__',
                        'b4',
                        'b5',
                    ),
                ),
                'aloneAppend' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        '__APPEND__',
                        'c1',
                        'c2',
                    ),
                ),
            ),
        );

        $result = array (
            'fields' =>
            array (
                'aaa1' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        0 => 'a1',
                    ),
                    'isCustom' => false,
                    'newValue' => 'NNNNN',
                ),
                'append' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        'b1',
                        'b3',
                        'b3',
                        'b4',
                        'b5',
                    ),
                ),
                't1111' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '11111',
                ),
                't2222' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '2222',
                ),
                't3333' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                    'isCustom' => true,
                    'default' => '3333',
                    'maxLength' => 70,
                ),
                'new111' =>
                array (
                    'type' => 'varchar',
                    'required' => false,
                ),
                'aloneAppend' =>
                array (
                    'type' => 'enum',
                    'required' => false,
                    'options' =>
                    array (
                        'c1',
                        'c2',
                    ),
                ),
            ),
        );

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

    public function testArrayToObject()
    {
        $testArr= array(
            'useCache' => true,
            'sub' =>  array (
                'subV' => '125',
                'subO' => array(
                    'subOV' => '125',
                ),
            ),
        );

        $testResult= (object) array(
            'useCache' => true,
        );
        $testResult->sub = (object) array (
                'subV' => '125',
        );
        $testResult->sub->subO = (object) array (
                'subOV' => '125',
        );

        $this->assertEquals($testResult, Util::arrayToObject($testArr));
    }

    public function testObjectToArray()
    {
        $testObj= (object) array(
            'useCache' => true,
        );
        $testObj->sub = (object) array (
                'subV' => '125',
        );
        $testObj->sub->subO = (object) array (
                'subOV' => '125',
        );

        $testResult= array(
            'useCache' => true,
            'sub' =>  array (
                'subV' => '125',
                'subO' => array(
                    'subOV' => '125',
                ),
            ),
        );

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
        $testArray = array(
            'option' => array(
                'default' => '{0}',
                 'testKey' => array(
                    '{0}' => 'testVal',
                 ),
            ),
        );

        $testResult = array(
            'option' => array(
                'default' => 'DONE',
                 'testKey' => array(
                    'DONE' => 'testVal',
                 ),
            ),
        );

        $this->assertEquals($testResult, Util::replaceInArray('{0}', 'DONE', $testArray, true));
    }

    /**
     * @dataProvider dp_classNames
     */
    public function testGetClassName($path, $expectedClassName = 'Espo\EntryPoints\Donwload')
    {
        $this->assertEquals($expectedClassName, Util::getClassName($path));
    }

    public function dp_classNames()
    {
        return [
            "application/Espo/EntryPoints/Donwload.php" => ['application/Espo/EntryPoints/Donwload.php'],
            "custom/Espo/EntryPoints/Donwload.php" => ['custom/Espo/EntryPoints/Donwload.php'],
            "Espo/EntryPoints/Donwload.php" => ['Espo/EntryPoints/Donwload.php'],
            "application/Espo/EntryPoints/Donwload" => ['application/Espo/EntryPoints/Donwload'],
            "\application\Espo\EntryPoints\Donwload" => ['application\Espo\EntryPoints\Donwload'],
        ];
    }

    public function testUnsetInArrayNotSingle()
    {
        $input = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subV' => '125',
                    'subO' => array(
                        'subOV' => '125',
                        'subOV2' => '125',
                    ),
                ),
            ),
        );

        $unsets = array(
            'Account' => array(
                'sub.subO.subOV', 'sub.subV',
            ),
        );

        $result = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subO' => array(
                        'subOV2' => '125',
                    ),
                ),
            ),
        );

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArraySingle()
    {
        $input = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subV' => '125',
                    'subO' => array(
                        'subOV' => '125',
                        'subOV2' => '125',
                    ),
                ),
            ),
        );

        $unsets = array(
            'Account.sub.subO.subOV', 'Account.sub.subV',
        );

        $result = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subO' => array(
                        'subOV2' => '125',
                    ),
                ),
            ),
        );

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArrayTogether()
    {
        $input = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subV' => '125',
                    'subO' => array(
                        'subOV' => '125',
                        'subOV2' => '125',
                    ),
                ),
            ),
        );

        $unsets = array(
            'Account' => array(
                'sub.subO.subOV',
            ),
            'Account.sub.subV',
        );

        $result = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subO' => array(
                        'subOV2' => '125',
                    ),
                ),
            ),
        );

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArray()
    {
        $input = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subV' => '125',
                    'subO' => array(
                        'subOV' => '125',
                        'subOV2' => '125',
                    ),
                ),
            ),
            'Contact' => array(
                'useCache' => true,
            ),
        );

        $unsets = array(
            'Account',
        );

        $result = array(
            'Contact' => array(
                'useCache' => true,
            ),
        );

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArrayByString()
    {
        $input = array(
            'Account' => array(
                'useCache' => true,
            ),
            'Contact' => array(
                'useCache' => true,
            ),
        );

        $unsets = 'Account.useCache';

        $result = array(
            'Account' => array(
            ),
            'Contact' => array(
                'useCache' => true,
            ),
        );

        $this->assertEquals($result, Util::unsetInArray($input, $unsets));
    }

    public function testUnsetInArrayEmptyParent()
    {
        $input = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subV' => '125',
                    'subO' => array(
                        'subOV' => '125',
                        'subOV2' => '125',
                    ),
                ),
            ),
            'Contact' => array(
                'useCache' => true,
            ),
            'Lead' => array(
                'useCache' => true,
            )
        );

        $unsets = array(
            'Account.useCache',
            'Account.sub',
            'Lead.useCache'
        );

        $result = array(
            'Contact' => array(
                'useCache' => true,
            )
        );

        $this->assertEquals($result, Util::unsetInArray($input, $unsets, true));
    }

    public function testGetValueByKey()
    {
        $inputArray = array(
            'Account' => array(
                'useCache' => true,
                'sub' =>  array (
                    'subV' => '125',
                    'subO' => array(
                        'subOV' => '125',
                        'subOV2' => '125',
                    ),
                ),
            ),
            'Contact' => array(
                'useCache' => true,
            ),
        );


        $this->assertEquals($inputArray, Util::getValueByKey($inputArray));
        $this->assertEquals($inputArray, Util::getValueByKey($inputArray, ''));

        $this->assertEquals('125', Util::getValueByKey($inputArray, 'Account.sub.subV'));

        $result = array('useCache' => true,    );
        $this->assertEquals($result, Util::getValueByKey($inputArray, 'Contact'));

        $this->assertNull(Util::getValueByKey($inputArray, 'Contact.notExists'));

        $this->assertEquals('customReturns', Util::getValueByKey($inputArray, 'Contact.notExists', 'customReturns'));
        $this->assertNotEquals('customReturns', Util::getValueByKey($inputArray, 'Contact.useCache', 'customReturns'));
    }

    public function testGetValueByKey2()
    {
        $inputArray = array(
            'fields' => array(
                'varchar' => array (
                      'params' =>
                      array (
                        array (
                          'name' => 'required',
                          'type' => 'bool',
                          'default' => false,
                        ),
                        array (
                          'name' => 'default',
                          'type' => 'varchar',
                        ),
                        array (
                          'name' => 'maxLength',
                          'type' => 'int',
                        ),
                        array (
                          'name' => 'trim',
                          'type' => 'bool',
                          'default' => true,
                        ),
                        array (
                          'name' => 'audited',
                          'type' => 'bool',
                        ),
                      ),
                      'filter' => true,
                ),
            )
        );

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
        $this->assertNotEquals('customReturns', Util::getValueByKey($inputObject, 'Contact.useCache', 'customReturns'));
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

        $this->assertEquals($result, \Espo\Core\Utils\Util::arrayDiff($array1, $array2));
    }

    public function htmlList()
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

    /**
     * @dataProvider htmlList
     */
    public function testSanitizeHtml($expectedResult, $html)
    {
        $this->assertEquals($expectedResult, Util::sanitizeHtml($html));
    }

    public function urlAddParamList()
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

    /**
     * @dataProvider urlAddParamList
     */
    public function testUrlAddParam($expectedResult, $url, $paramName, $paramValue)
    {
        $this->assertEquals($expectedResult, Util::urlAddParam($url, $paramName, $paramValue));
    }

    public function urlRemoveParamList()
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

    /**
     * @dataProvider urlRemoveParamList
     */
    public function testUrlRemoveParam($expectedResult, $url, $paramName, $suffix = '')
    {
        $this->assertEquals($expectedResult, Util::urlRemoveParam($url, $paramName, $suffix));
    }
}
