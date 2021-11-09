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
 * The interactive user interfaces in modified source and route code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\Module;
use Espo\Core\Utils\File\Manager as FileManager;

class ModuleTest extends \PHPUnit\Framework\TestCase
{
    /** @var Module */
    private $module;

    /** @var FileManager */
    private $fileManager;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->module = new Module($this->fileManager);
    }

    public function testOrder1(): void
    {
        $this->fileManager
            ->method('getDirList')
            ->withConsecutive(
                ['application/Espo/Modules'],
                ['custom/Espo/Modules'],
            )
            ->willReturnOnConsecutiveCalls(
                ['M01', 'M02', 'M1', 'M2'],
                ['M3', 'M4', 'M51', 'M52'],
            );

        $this->fileManager
            ->method('exists')
            ->withConsecutive(
                ['application/Espo/Modules/M01/Resources/module.json'],
                ['application/Espo/Modules/M02/Resources/module.json'],
                ['application/Espo/Modules/M1/Resources/module.json'],
                ['application/Espo/Modules/M2/Resources/module.json'],
                ['custom/Espo/Modules/M3/Resources/module.json'],
                ['custom/Espo/Modules/M4/Resources/module.json'],
                ['custom/Espo/Modules/M51/Resources/module.json'],
                ['custom/Espo/Modules/M52/Resources/module.json'],
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                true,
                true,
                true,
                true,
                true,
                true,
            );

        $this->fileManager
            ->method('getContents')
            ->withConsecutive(
                ['application/Espo/Modules/M01/Resources/module.json'],
                ['application/Espo/Modules/M02/Resources/module.json'],
                ['application/Espo/Modules/M1/Resources/module.json'],
                ['application/Espo/Modules/M2/Resources/module.json'],
                ['custom/Espo/Modules/M3/Resources/module.json'],
                ['custom/Espo/Modules/M4/Resources/module.json'],
                ['custom/Espo/Modules/M51/Resources/module.json'],
                ['custom/Espo/Modules/M52/Resources/module.json'],
            )
            ->willReturnOnConsecutiveCalls(
                '{"order": 11}',
                '{"order": 11}',
                '{"order": 4}',
                '{"order": 3}',
                '{"order": 2}',
                '{"order": 1}',
                '{"order": 12}',
                '{"order": 12}',
            );

        $this->assertEquals(
            ['M4', 'M3', 'M2', 'M1', 'M01', 'M02', 'M51', 'M52'],
            $this->module->getOrderedList()
        );
    }
}
