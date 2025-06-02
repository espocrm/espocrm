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

namespace tests\unit\Espo\Core\Htmlizer;

use Espo\Core\Htmlizer\Htmlizer;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\NumberUtil;
use Espo\Core\Utils\DateTime;

use Espo\Core\ORM\Entity;

use Espo\Entities\User;
use Espo\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use stdClass;

class HtmlizerTest extends TestCase
{
    private ?Htmlizer $htmlizer;
    private ?DateTime $dateTime;
    private ?NumberUtil $number;
    private ?EntityManager $entityManager;

    private $entityAttributes = [
        'id' => [
            'type' => Entity::ID,
        ],
        'name' => [
            'type' => Entity::VARCHAR,
            'len' => 255,
        ],
        'date' => [
            'type' => Entity::DATE
        ],
        'dateTime' => [
            'type' => Entity::DATETIME
        ],
        'int' => [
            'type' => Entity::INT
        ],
        'float' => [
            'type' => Entity::FLOAT
        ],
        'list' => [
            'type' => Entity::JSON_ARRAY
        ],
        'object' => [
            'type' => Entity::JSON_OBJECT
        ],
        'deleted' => [
            'type' => Entity::BOOL,
            'default' => 0,
        ]
    ];

    protected function setUp(): void
    {
        date_default_timezone_set('UTC');

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->dateTime = new DateTime('MM/DD/YYYY', 'hh:mm A', 'Europe/Kiev');
        $this->number = new NumberUtil('.', ',');
        $this->htmlizer = new Htmlizer(
            $this->dateTime,
            $this->number,
            $this->createMock(SelectBuilderFactory::class),
            $this->createMock(User::class),
            $this->entityManager,
            $this->createMock(Metadata::class),
            $this->createMock(Language::class),
            $this->createMock(Config::class),
            $this->createMock(Log::class),
            $this->createMock(InjectableFactory::class)
        );
    }

    protected function tearDown(): void
    {}

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
        $html = $this->htmlizer->render($entity, $template, skipInlineAttachmentHandling: true);
        $this->assertEquals('?entryPoint=attachment&id=1', $html);

        $template = "{{#ifEqual name '1'}}hello{{/ifEqual}}";
        $entity->set('name', '1');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('hello', $html);

        $template = "{{#ifNotEqual name '1'}}hello{{else}}test{{/ifNotEqual}}";
        $entity->set('name', '1');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('test', $html);

        $template = "{{#if (and (equal name 'test') true 1)}}test{{/if}}";
        $entity->set('name', 'test');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('test', $html);

        $template = "{{#if (or (equal name 'test') false 0)}}test{{/if}}";
        $entity->set('name', 'test');
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('test', $html);

        $template = "{{#if (not false)}}test{{/if}}";
        $html = $this->htmlizer->render($entity, $template);
        $this->assertEquals('test', $html);
    }

    public function testIterate(): void
    {
        /** @noinspection HtmlUnknownAttribute */
        $template = "<ul><li iterate=\"{{items}}\">{{name}}</li></ul><ul><li iterate=\"{{items}}\">{{name}}</li></ul>";

        $html = $this->htmlizer->render(null, $template, null, [
            'items' => [
                ['name' => '1'],
                ['name' => '2'],
            ],
        ]);

        /** @noinspection HtmlUnknownAttribute */
        $expected = "<ul><li>1</li><li>2</li></ul><ul><li>1</li><li>2</li></ul>";

        $this->assertEquals($expected, $html);
    }

    public function testIf(): void
    {
        /** @noinspection HtmlUnknownAttribute */
        $template = "<ul><li x-if=\"{{and (equal 1 1) true}}\">1</li><li x-if=\"{{false}}\">2</li>".
            "<li x-if=\"{{1}}\">true</li></ul>";

        $html = $this->htmlizer->render(null, $template, null, []);

        /** @noinspection HtmlUnknownAttribute */
        $expected = "<ul><li>1</li><li>true</li></ul>";

        $this->assertEquals($expected, $html);
    }
}
