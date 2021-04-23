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

namespace tests\unit\Espo\Core\Htmlizer;

use Espo\Core\Htmlizer\Htmlizer;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\DateTime;

use Espo\Core\ORM\Entity;

use StdClass;

class HtmlizerTest extends \PHPUnit\Framework\TestCase
{
    protected $htmlizer;

    protected $fileManager;

    protected $config;

    protected $dateTime;

    protected $number;

    private $entityAttributes = [
        'id' => array(
            'type' => Entity::ID,
        ),
        'name' => array(
            'type' => Entity::VARCHAR,
            'len' => 255,
        ),
        'date' => array(
            'type' => Entity::DATE
        ),
        'dateTime' => array(
            'type' => Entity::DATETIME
        ),
        'int' => array(
            'type' => Entity::INT
        ),
        'float' => array(
            'type' => Entity::FLOAT
        ),
        'list' => array(
            'type' => Entity::JSON_ARRAY
        ),
        'object' => array(
            'type' => Entity::JSON_OBJECT
        ),
        'deleted' => array(
            'type' => Entity::BOOL,
            'default' => 0,
        )
    ];

    protected function setUp(): void
    {
        date_default_timezone_set('UTC');

        $this->entityManager =
            $this->getMockBuilder('Espo\\Core\\ORM\\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $obj = new \StdClass();

        $this->fileManager = $this->getMockBuilder('Espo\\Core\\Utils\\File\\Manager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileManager
            ->expects($this->any())
            ->method('putContents')
            ->will($this->returnCallback(function($fileName, $contents) use ($obj) {
                $obj->contents = $contents;
            }));

        $this->fileManager
            ->expects($this->any())
            ->method('getPhpContents')
            ->will(
                $this->returnCallback(function($fileName) use ($obj) {
                    $obj->contents = str_replace('<?php ', '', $obj->contents);
                    $obj->contents = str_replace('?>', '', $obj->contents);

                    $data = eval($obj->contents . ';');

                    return $data;
                })
            );


        $this->fileManager
                    ->expects($this->any())
                    ->method('unlink');

        $this->dateTime = new DateTime('MM/DD/YYYY', 'hh:mm A', 'Europe/Kiev');

        $this->number = new NumberUtil('.', ',');

        $this->htmlizer = new Htmlizer($this->fileManager, $this->dateTime, $this->number);
    }

    protected function tearDown(): void
    {
        unset($this->htmlizer);
        unset($this->fileManager);
        unset($this->dateTime);
        unset($this->number);
    }

    public function testRender()
    {
        $entity = new Entity(
            'Test',
            [
                'attributes' => $this->entityAttributes,
            ],
            $this->entityManager
        );

        $entity->set('name', 'test');
        $entity->set('date', '2015-09-15');
        $entity->set('dateTime', '2015-09-15 10:00:00');
        $entity->set('int', 3);
        $entity->set('float', 3.5);

        $item1 = new StdClass();
        $item1->value = 1;

        $item2 = new StdClass();
        $item2->value = 2000.5;

        $list = [$item1, $item2];

        $entity->set('list', $list);

        $template = "{{name}} test {{date}} {{dateTime}} {{#each list}}{{value}} {{/each}}{{int}} {{float}}";
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('test test 09/15/2015 09/15/2015 01:00 PM 1 2,000.50 3 3.50', $html);

        $template = "{{float}}";
        $entity->set('float', 3);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('3.00', $html);


        $template = "{{float}}";
        $entity->set('float', 3);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('3.00', $html);

        $template = "{{float}}";
        $entity->set('float', 10000.50);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('10,000.50', $html);

        $template = "{{int}}";
        $entity->set('int', 3000);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('3,000', $html);

        $template = "{{float_RAW}}";
        $entity->set('float', 10000.50);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('10000.5', $html);

        $template = "{{numberFormat float_RAW}}";
        $entity->set('float', 10000.60);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('10,001', $html);

        $template = "{{numberFormat float_RAW decimals=2}}";
        $entity->set('float', 10000.601);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('10,000.60', $html);

        $template = "{{numberFormat float_RAW decimals=0}}";
        $entity->set('float', 10000.1);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('10,000', $html);

        $template = "{{numberFormat float_RAW decimals=2 decimalPoint='.' thousandsSeparator=' '}}";
        $entity->set('float', 10000.60);
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('10 000.60', $html);

        $template = "{{file name}}";
        $entity->set('name', '1');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('?entryPoint=attachment&id=1', $html);

        $template = "{{#ifEqual name '1'}}hello{{/ifEqual}}";
        $entity->set('name', '1');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('hello', $html);

        $template = "{{#ifNotEqual name '1'}}hello{{else}}test{{/ifNotEqual}}";
        $entity->set('name', '1');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('test', $html);
    }
}
