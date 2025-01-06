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

namespace tests\unit\Espo\Core\Console;

use Espo\Core\Console\Command\Params;

class ParamsTest extends \PHPUnit\Framework\TestCase
{
    public function testParams()
    {
        $raw = [
           'argumentList' => ['a1', 'a2'],
           'flagList' => ['f1', 'f2', 'f3'],
           'options' => [
               'optionOne' => 'test',
            ],
        ];

        $params = new Params(
            $raw['options'],
            $raw['flagList'],
            $raw['argumentList']
        );

        $this->assertEquals($raw['argumentList'], $params->getArgumentList());
        $this->assertEquals($raw['flagList'], $params->getFlagList());
        $this->assertEquals($raw['options'], $params->getOptions());

        $this->assertTrue($params->hasFlag('f1'));
        $this->assertFalse($params->hasFlag('f0'));

        $this->assertTrue($params->hasOption('optionOne'));
        $this->assertFalse($params->hasOption('optionZero'));

        $this->assertEquals('test', $params->getOption('optionOne'));
        $this->assertEquals(null, $params->getOption('optionTwo'));

        $this->assertEquals('a1', $params->getArgument(0));
        $this->assertEquals(null, $params->getArgument(4));
    }
}
