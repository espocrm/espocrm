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

namespace tests\unit\Espo\Core\Upgrades\Migration;

use Espo\Core\Upgrades\Migration\StepsProvider;
use Espo\Core\Utils\File\Manager;
use PHPUnit\Framework\TestCase;

class StepsProviderTest extends TestCase
{
    public function testGet1(): void
    {
        $fileManager = $this->createMock(Manager::class);

        $fileManager
            ->expects($this->once())
            ->method('getDirList')
            ->willReturn(['V7_5_1', 'V8_0', 'V8_1', 'V8_2', 'V8_2_2']);

        $fileManager
            ->expects($this->any())
            ->method('isFile')
            ->willReturn(true);

        $provider = new StepsProvider($fileManager);

        $this->assertEquals([
            '7.5.1',
            '8.0',
            '8.1',
            '8.2',
            '8.2.2',
        ], $provider->getAfterUpgrade());
    }
}
