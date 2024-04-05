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

namespace Espo\Core\Upgrades\Migration;

use Espo\Core\Console\IO;
use Exception;
use RuntimeException;

class Runner
{
    public function __construct(
        private StepsProvider $stepsProvider,
        private VersionDataProvider $versionDataProvider,
        private StepRunner $stepRunner
    ) {}

    public function run(IO $io): void
    {
        $version = $this->versionDataProvider->getPreviousVersion();
        $targetVersion = $this->versionDataProvider->getTargetVersion();

        $fullPrepareSteps = $this->stepsProvider->getPrepare();
        $prepareSteps = VersionUtil::extractSteps($version, $targetVersion, $fullPrepareSteps);

        if ($prepareSteps !== []) {
            $io->write(" Running prepare migrations...");

            foreach ($prepareSteps as $step) {
                $this->runPrepareStep($io, $step);
            }
        }

        $fullAfterSteps = $this->stepsProvider->getAfterUpgrade();
        $afterSteps = VersionUtil::extractSteps($version, $targetVersion, $fullAfterSteps);

        if ($afterSteps === []) {
            $io->writeLine(" No migrations to run.");

            return;
        }

        $io->write(" Running after-upgrade migrations...");

        foreach ($afterSteps as $step) {
            $this->runAfterUpgradeStep($io, $step);
        }
    }

    private function runAfterUpgradeStep(IO $io, string $step): void
    {
        $io->write("    $step...");

        $isSuccessful = $this->stepRunner->runAfterUpgrade($step);

        if ($isSuccessful) {
            $io->writeLine(" DONE");

            return;
        }

        $io->writeLine(" FAIL");

        throw new RuntimeException();
    }

    private function runPrepareStep(IO $io, string $step): void
    {
        $io->write("    $step...");

        try {
            $this->stepRunner->runPrepare($step);
        }
        catch (Exception $e) {
            $io->writeLine(" FAIL");

            throw new RuntimeException($e->getMessage());
        }

        $io->writeLine(" DONE");
    }
}
