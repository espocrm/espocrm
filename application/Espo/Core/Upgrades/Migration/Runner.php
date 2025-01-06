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

namespace Espo\Core\Upgrades\Migration;

use Espo\Core\Console\IO;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Config\ConfigWriter;
use Exception;
use RuntimeException;

class Runner
{
    public function __construct(
        private ExtractedStepsProvider $stepsProvider,
        private VersionDataProvider $versionDataProvider,
        private StepRunner $stepRunner,
        private DataManager $dataManager,
        private ConfigWriter $configWriter
    ) {}

    /**
     * @throws Error
     */
    public function run(IO $io): void
    {
        $this->dataManager->clearCache();

        $version = $this->versionDataProvider->getPreviousVersion();
        $targetVersion = $this->versionDataProvider->getTargetVersion();

        $prepareSteps = $this->stepsProvider->getPrepare($version, $targetVersion);

        if ($prepareSteps !== []) {
            $io->writeLine("Running prepare migrations...");

            foreach ($prepareSteps as $step) {
                $this->runPrepareStep($io, $step);
            }
        }

        $afterSteps = $this->stepsProvider->getAfterUpgrade($version, $targetVersion);

        if ($afterSteps === []) {
            $io->writeLine("No migrations to run.");

            return;
        }

        $io->writeLine("Running after-upgrade migrations...");

        foreach ($afterSteps as $step) {
            $this->runAfterUpgradeStep($io, $step);
            $this->updateVersion(VersionUtil::stepToVersion($step));
        }

        $this->updateVersion($targetVersion);
        $this->dataManager->updateAppTimestamp();

        $io->writeLine("Completed.");
    }

    private function runAfterUpgradeStep(IO $io, string $step): void
    {
        $io->write("  $step...");

        $isSuccessful = $this->stepRunner->runAfterUpgrade($step);

        if ($isSuccessful) {
            $io->writeLine(" DONE");

            return;
        }

        $io->writeLine(" FAIL");

        throw new RuntimeException("Step process failed.");
    }

    private function runPrepareStep(IO $io, string $step): void
    {
        $io->write("    $step...");

        try {
            $this->stepRunner->runPrepare($step);
        } catch (Exception $e) {
            $io->writeLine(" FAIL");

            throw new RuntimeException($e->getMessage());
        }

        $io->writeLine(" DONE");
    }

    private function updateVersion(string $targetVersion): void
    {
        $this->configWriter->set('version', $targetVersion);
        $this->configWriter->save();
    }
}
