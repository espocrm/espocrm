<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\unit\Espo\Core\Upgrades\Migration;

use Espo\Core\Upgrades\Migration\StepsExtractor;
use PHPUnit\Framework\TestCase;

class StepsExtractorTest extends TestCase
{
    public function testGet1(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.0.0', '8.3.0', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.1',
        ]);

        $this->assertEquals([
            '8.1',
            '8.3',
        ], $list);
    }

    public function testGet2(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.0.0', '8.3.1', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.1',
        ]);

        $this->assertEquals([
            '8.1',
            '8.3',
        ], $list);
    }

    public function testGet3(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.0.0', '8.3.5', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.2',
        ]);

        $this->assertEquals([
            '8.1',
            '8.3',
        ], $list);
    }

    public function testGet4(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.2.0', '8.3.5', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.2',
        ]);

        $this->assertEquals([
            '8.3',
        ], $list);
    }

    public function testGet5(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.0.4', '8.3.5', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.2',
        ]);

        $this->assertEquals([
            '8.1',
            '8.3',
        ], $list);
    }

    public function testGetMajor1(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('7.5.4', '8.3.5', array_reverse([
            '7.0',
            '7.5',
            '7.5.1',
            '7.6',
            '7.6.1',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.2',
        ]));

        $this->assertEquals([
            '7.6',
            '8.0',
            '8.1',
            '8.3',
        ], $list);
    }

    public function testGetPatch1(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.3.0', '8.3.5', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.2',
            '8.3.4',
        ]);

        $this->assertEquals([
            '8.3.2',
            '8.3.4',
        ], $list);
    }

    public function testGetPatch2(): void
    {
        $extractor = new StepsExtractor();

        $list = $extractor->extract('8.3.0', '8.3.5', [
            '7.0',
            '7.5',
            '8.0',
            '8.1',
            '8.1.4',
            '8.3',
            '8.3.2',
            '8.3.4',
            '8.3.5',
            '8.6.1',
        ]);

        $this->assertEquals([
            '8.3.2',
            '8.3.4',
            '8.3.5',
        ], $list);
    }
}
