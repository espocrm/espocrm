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

namespace tests\unit\Espo\Core\Utils\Config;

use Espo\Core\{
    Utils\Config,
    Utils\Config\ConfigWriter,
    Utils\Config\ConfigWriterFileManager,
    Utils\Config\ConfigWriterHelper,
    Utils\Config\InternalConfigHelper,
};

class ConfigWriterTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(ConfigWriterFileManager::class);

        $this->config = $this->createMock(Config::class);

        $this->helper = $this->createMock(ConfigWriterHelper::class);

        $this->internalConfigHelper = $this->createMock(InternalConfigHelper::class);

        $this->configWriter = new ConfigWriter(
            $this->config,
            $this->fileManager,
            $this->helper,
            $this->internalConfigHelper
        );

        $this->configPath = 'somepath';
        $this->internalConfigPath = 'internalSomepath';

        $this->config
            ->expects($this->any())
            ->method('getConfigPath')
            ->willReturn($this->configPath);

        $this->config
            ->expects($this->any())
            ->method('getInternalConfigPath')
            ->willReturn($this->internalConfigPath);
    }

    public function testSave1(): void
    {
        $this->configWriter->set('k1', 'v1');

        $this->configWriter->setMultiple([
            'k2' => 'v2',
            'k3' => 'v3',
        ]);

        $this->configWriter->remove('k4');

        $previousData = [
            'k3' => 'e3',
            'k4' => 'e4',
            'microtime' => 0.0,
            'cacheTimestamp' => 0,
        ];

        $newData = [
            'k1' => 'v1',
            'k2' => 'v2',
            'k3' => 'v3',
            'microtime' => 1.0,
            'cacheTimestamp' => 1,
        ];

        $this->helper
            ->expects($this->once())
            ->method('generateMicrotime')
            ->willReturn(1.0);

        $this->helper
            ->expects($this->once())
            ->method('generateCacheTimestamp')
            ->willReturn(1);

        $this->config
            ->expects($this->once())
            ->method('update');

        $this->fileManager
            ->method('isFile')
            ->withConsecutive(
                [$this->configPath],
                [$this->internalConfigPath],
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->fileManager
            ->expects($this->once())
            ->method('putPhpContents')
            ->with($this->configPath, $newData)
            ->willReturn($previousData);

        $this->fileManager
            ->expects($this->exactly(2))
            ->method('getPhpContents')
            ->withConsecutive(
                [$this->configPath],
                [$this->configPath],
            )
            ->willReturnOnConsecutiveCalls($previousData);

        $this->configWriter->save();
    }

    public function testSave2(): void
    {
        $this->configWriter->set('k1', 'v1');
        $this->configWriter->set('k2', 'v2');

        $this->internalConfigHelper
            ->method('isParamForInternalConfig')
            ->will(
                $this->returnValueMap([
                    ['k1', false],
                    ['k2', true],
                    ['cacheTimestamp', false],
                ])
            );

        $this->helper
            ->expects($this->exactly(2))
            ->method('generateMicrotime')
            ->willReturn(1.0);

        $this->helper
            ->expects($this->once())
            ->method('generateCacheTimestamp')
            ->willReturn(1);

        $this->fileManager
            ->method('isFile')
            ->withConsecutive(
                [$this->configPath],
                [$this->internalConfigPath],
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->fileManager
            ->expects($this->exactly(4))
            ->method('getPhpContents')
            ->withConsecutive(
                [$this->configPath],
                [$this->internalConfigPath],
                [$this->internalConfigPath],
                [$this->configPath],
            )
            ->willReturnOnConsecutiveCalls(
                [],
                [],
            );

        $this->fileManager
            ->method('putPhpcontents')
            ->withConsecutive(
                [$this->internalConfigPath, ['k2' => 'v2', 'microtimeInternal' => 1.0]],
                [$this->configPath, ['k1' => 'v1', 'cacheTimestamp' => 1, 'microtime' => 1.0]],
            );

        $this->configWriter->save();
    }
}
