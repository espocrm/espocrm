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

namespace Espo\Core\Upgrades\Actions\Upgrade;

use Espo\Core\Exceptions\Error;
use Espo\Core\Upgrades\Migration\Script;
use Espo\Core\Upgrades\Migration\VersionUtil;
use RuntimeException;

class Install extends \Espo\Core\Upgrades\Actions\Base\Install
{
    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepBeforeUpgradeScript(array $data): void
    {
        $this->stepBeforeInstallScript($data);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function stepAfterUpgradeScript(array $data): void
    {
        $this->stepAfterInstallScript($data);
        $this->runMigrationAfterInstallScript();
    }

    /**
     * @throws Error
     */
    protected function finalize(): void
    {
        $configWriter = $this->createConfigWriter();
        $configWriter->set('version', $this->getTargetVersion());
        $configWriter->save();
    }

    /**
     * Delete temporary package files.
     *
     * @throws Error
     */
    protected function deletePackageFiles(): bool
    {
        $res = parent::deletePackageFiles();
        $res &= $this->deletePackageArchive();

        return (bool) $res;
    }

    /**
     * @throws Error
     */
    private function getTargetVersion(): string
    {
        $version = $this->getManifest()['version'];

        if (!$version) {
            throw new RuntimeException("No 'version' in manifest.");
        }

        return $version;
    }

    /**
     * @throws Error
     */
    private function runMigrationAfterInstallScript(): void
    {
        $targetVersion = $this->getTargetVersion();
        $version = $this->getConfig()->get('version');

        if (!$version || !is_string($version)) {
            throw new RuntimeException("No or bad 'version' in config.");
        }

        $script = $this->getMigrationAfterInstallScript($version, $targetVersion);

        if (!$script) {
            return;
        }

        $script->run();
    }

    private function getMigrationAfterInstallScript(string $version, string $targetVersion): ?Script
    {
        $isPatch = VersionUtil::isPatch($version, $targetVersion);
        $a = VersionUtil::split($targetVersion);

        $dir = $isPatch ?
            'V' . $a[0] . '_' . $a[1] . '_' . $a[2] :
            'V' . $a[0] . '_' . $a[1];

        $className = "Espo\\Core\\Upgrades\\Migrations\\$dir\\AfterUpgrade";

        if (!class_exists($className)) {
            return null;
        }

        $script = $this->getInjectableFactory()->createWith($className, ['isUpgrade' => true]);

        if (!$script instanceof Script) {
            throw new RuntimeException("$className does not implement Script interface.");
        }

        return $script;
    }
}
