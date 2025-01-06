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

use Espo\Core\Console\IO;
use Espo\Core\DataManager;
use Espo\Core\Upgrades\Migration\ExtractedStepsProvider;
use Espo\Core\Upgrades\Migration\Runner;
use Espo\Core\Upgrades\Migration\StepRunner;
use Espo\Core\Upgrades\Migration\VersionDataProvider;
use Espo\Core\Utils\Config\ConfigWriter;
use PHPUnit\Framework\TestCase;

class RunnerTest extends TestCase
{
    protected ?IO $io = null;

    protected ?ExtractedStepsProvider $stepsProvider = null;
    protected ?VersionDataProvider $versionDataProvider;
    protected ?StepRunner $stepsRunner = null;
    protected ?DataManager $dataManager = null;
    protected ?ConfigWriter $configWriter = null;

    protected function setUp(): void
    {
        $this->io = $this->createMock(IO::class);

        $this->stepsProvider = $this->createMock(ExtractedStepsProvider::class);
        $this->versionDataProvider = $this->createMock(VersionDataProvider::class);
        $this->stepsRunner = $this->createMock(StepRunner::class);
        $this->dataManager = $this->createMock(DataManager::class);
        $this->configWriter = $this->createMock(ConfigWriter::class);
    }

    public function testRun(): void
    {
        $this->versionDataProvider
            ->expects($this->once())
            ->method('getTargetVersion')
            ->willReturn('8.3.5');

        $this->versionDataProvider
            ->expects($this->once())
            ->method('getPreviousVersion')
            ->willReturn('8.0.4');

        $this->stepsProvider
            ->expects($this->once())
            ->method('getPrepare')
            ->willReturn([
                '8.2',
                '8.3',
            ]);

        $this->stepsProvider
            ->expects($this->once())
            ->method('getAfterUpgrade')
            ->willReturn([
                '8.1',
                '8.3',
            ]);

        $this->stepsRunner
            ->expects($this->any())
            ->method('runPrepare')
            ->willReturnMap([
                ['8.2', true],
                ['8.3', true],
            ]);

        $this->stepsRunner
            ->expects($this->any())
            ->method('runAfterUpgrade')
            ->willReturnMap([
                ['8.1', true],
                ['8.3', true],
            ]);

        $runner = new Runner(
            $this->stepsProvider,
            $this->versionDataProvider,
            $this->stepsRunner,
            $this->dataManager,
            $this->configWriter
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $runner->run($this->io);
    }
}
