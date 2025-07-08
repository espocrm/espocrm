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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\Module;
use Espo\Core\Utils\File\Manager as FileManager;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
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
            ->expects(self::any())
            ->method('getDirList')
            ->willReturnMap([
                [
                    'application/Espo/Modules',
                    ['M01', 'M02', 'M1', 'M2'],
                ],
                [
                    'custom/Espo/Modules',
                    ['M3', 'M4', 'M51', 'M52'],
                ]
            ]);

        $this->fileManager
            ->expects(self::any())
            ->method('exists')
            ->willReturnMap([
                ['application/Espo/Modules/M01/Resources/module.json', true],
                ['application/Espo/Modules/M02/Resources/module.json', true],
                ['application/Espo/Modules/M1/Resources/module.json', true],
                ['application/Espo/Modules/M2/Resources/module.json', true],
                ['custom/Espo/Modules/M3/Resources/module.json', true],
                ['custom/Espo/Modules/M4/Resources/module.json', true],
                ['custom/Espo/Modules/M51/Resources/module.json', true],
                ['custom/Espo/Modules/M52/Resources/module.json', true],
            ]);

        $this->fileManager
            ->expects(self::any())
            ->method('getContents')
            ->willReturnMap([
                ['application/Espo/Modules/M01/Resources/module.json', '{"order": 11}'],
                ['application/Espo/Modules/M02/Resources/module.json', '{"order": 11}'],
                ['application/Espo/Modules/M1/Resources/module.json', '{"order": 4}'],
                ['application/Espo/Modules/M2/Resources/module.json', '{"order": 3}'],
                ['custom/Espo/Modules/M3/Resources/module.json', '{"order": 2}'],
                ['custom/Espo/Modules/M4/Resources/module.json', '{"order": 1}'],
                ['custom/Espo/Modules/M51/Resources/module.json', '{"order": 12}'],
                ['custom/Espo/Modules/M52/Resources/module.json', '{"order": 12}'],
            ]);

        $this->assertEquals(
            ['M4', 'M3', 'M2', 'M1', 'M01', 'M02', 'M51', 'M52'],
            $this->module->getOrderedList()
        );
    }
}
