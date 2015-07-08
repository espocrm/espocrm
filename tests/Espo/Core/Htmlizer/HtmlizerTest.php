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
 ************************************************************************/

namespace tests\Espo\Core\Htmlizer;


class HtmlizerTest extends \PHPUnit_Framework_TestCase
{
    protected $htmlizer;

    protected $fileManager;


    protected function setUp()
    {
        date_default_timezone_set('UTC');

        $this->fileManager = $this->getMockBuilder('\\Espo\\Core\\Utils\\File\\Manager')->disableOriginalConstructor()->getMock();
        $this->fileManager
                    ->expects($this->any())
                    ->method('putContents')
                    ->will($this->returnCallback(function($fileName, $contents) {
                        file_put_contents($fileName, $contents);
                    }));

        $this->fileManager
                    ->expects($this->any())
                    ->method('unlink')
                    ->will($this->returnCallback(function($fileName, $contents) {
                        unlink($fileName);
                    }));

        $this->dateTime = new \Espo\Core\Utils\DateTime('MM/DD/YYYY', 'hh:mm A', 'Europe/Kiev');

        $this->htmlizer = new \Espo\Core\Htmlizer\Htmlizer($this->fileManager, $this->dateTime);

    }

    protected function tearDown()
    {
        unset($this->htmlizer);
        unset($this->fileManager);
    }

    public function testRender()
    {
        $entity = new \tests\testData\Entities\Test();
        $entity->set('name', 'test');
        $entity->set('date', '2015-09-15');
        $entity->set('dateTime', '2015-09-15 10:00:00');

        $item1 = new \StdClass();
        $item1->value = '1';

        $item2 = new \StdClass();
        $item2->value = '2';

        $list = [$item1, $item2];
        $entity->set('list', $list);

        $template = "{{name}} test {{date}} {{dateTime}} {{#each list}}{{value}}{{/each}}";

        $html = $this->htmlizer->render($entity, $template);

        $this->assertEquals('test test 09/15/2015 09/15/2015 01:00 PM 12', $html);
    }
}

