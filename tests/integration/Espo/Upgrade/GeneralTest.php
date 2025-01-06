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

namespace tests\integration\Espo\Upgrade;

use Espo\Core\Upgrades\UpgradeManager;

class GeneralTest extends \tests\integration\Core\BaseTestCase
{
    protected ?string $dataFile = 'InitData.php';

    protected ?string $userName = 'admin';
    protected ?string $password = '1';

    protected $packagePath = 'Upgrade/General.zip';

    public function testUpload()
    {
        $fileData = file_get_contents($this->normalizePath($this->packagePath));
        $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

        $upgradeManager = new UpgradeManager($this->getContainer());
        $upgradeId = $upgradeManager->upload($fileData);

        $this->assertStringMatchesFormat('%x', $upgradeId);
        $this->assertFileExists('data/upload/upgrades/' . $upgradeId . 'z');
        $this->assertFileExists('data/upload/upgrades/' . $upgradeId);
        //$this->assertDirectoryExists('data/upload/upgrades/' . $upgradeId);

        return $upgradeId;
    }

    public function testInstall()
    {
        $upgradeId = $this->testUpload();

        $upgradeManager = new UpgradeManager($this->getContainer());

        $upgradeManager->install(['id' => $upgradeId]);

        $this->assertFileDoesNotExist('data/upload/upgrades/' . $upgradeId . 'z');
        $this->assertFileDoesNotExist('data/upload/upgrades/' . $upgradeId);
        $this->assertFileExists('data/.backup/upgrades/' . $upgradeId);

        $this->assertFileExists('custom/Espo/Custom/test.php');
        $this->assertFileDoesNotExist('vendor/zendframework');
        $this->assertFileDoesNotExist('extension.php');
        $this->assertFileDoesNotExist('upgrade.php');

        return $upgradeId;
    }

    public function testUninstall()
    {
        $this->expectException('Espo\\Core\\Exceptions\\Error');

        $upgradeId = $this->testInstall();

        $upgradeManager = new UpgradeManager($this->getContainer());
        $upgradeManager->uninstall(array('id' => $upgradeId));
    }

    public function testDelete()
    {
        $this->expectException('Espo\\Core\\Exceptions\\Error');

        $upgradeId = $this->testInstall();

        $upgradeManager = new UpgradeManager($this->getContainer());
        $upgradeManager->delete(['id' => $upgradeId]);
    }
}
