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

namespace tests\integration\Espo\Extension;

use Espo\Core\Upgrades\ExtensionManager;

class GeneralTest extends \tests\integration\Core\BaseTestCase
{
    protected ?string $dataFile = 'InitData.php';

    protected ?string $userName = 'admin';
    protected ?string $password = '1';

    protected $packagePath = 'Extension/General.zip';

    protected function beforeSetUp(): void
    {
        //$this->fullReset();
    }

    /**
     * If this test fails, an instance may become broken for consecutive test runs.
     */
    public function testExtensionUploadInstallUninstallDelete(): void
    {
        $extensionId = $this->testUninstall();

        $extensionManager = new ExtensionManager($this->getContainer());
        $extensionManager->delete(['id' => $extensionId]);

        $this->assertFileDoesNotExist('data/.backup/extensions/' . $extensionId);
        $this->assertFileDoesNotExist('data/upload/extensions/' . $extensionId);
        $this->assertFileDoesNotExist('data/upload/extensions/' . $extensionId . 'z');

        $this->assertFileDoesNotExist('application/Espo/Modules/Test');
        $this->assertFileDoesNotExist('application/Espo/Modules/Test/Resources/metadata/scopes/TestEntity.json');
        $this->assertFileDoesNotExist('client/modules/test');
        $this->assertFileDoesNotExist('client/modules/test/src/views/test-entity/fields/custom-type.js');

        $this->assertFileExists('vendor/symfony');
        $this->assertFileExists('extension.php');
        $this->assertFileExists('upgrade.php');
    }

    private function testUpload(): string
    {
        $fileData = file_get_contents($this->normalizePath($this->packagePath));
        $fileData = 'data:application/zip;base64,' . base64_encode($fileData);

        $extensionManager = new ExtensionManager($this->getContainer());
        $extensionId = $extensionManager->upload($fileData);

        $this->assertStringMatchesFormat('%x', $extensionId);
        $this->assertFileExists('data/upload/extensions/' . $extensionId . 'z');
        $this->assertFileExists('data/upload/extensions/' . $extensionId);

        return $extensionId;
    }

    private function testInstall(): string
    {
        $extensionId = $this->testUpload();

        $extensionManager = new ExtensionManager($this->getContainer());
        $extensionManager->install(['id' => $extensionId]);

        $this->assertFileExists('data/upload/extensions/' . $extensionId . 'z');
        $this->assertFileDoesNotExist('data/upload/extensions/' . $extensionId);
        $this->assertFileExists('data/.backup/extensions/' . $extensionId);

        $this->assertFileExists('application/Espo/Modules/Test');
        $this->assertFileExists('application/Espo/Modules/Test/Resources/metadata/scopes/TestEntity.json');
        $this->assertFileExists('client/modules/test');
        $this->assertFileExists('client/modules/test/src/views/test-entity/fields/custom-type.js');

        $this->assertFileDoesNotExist('vendor/symfony');
        $this->assertFileDoesNotExist('extension.php');
        $this->assertFileDoesNotExist('upgrade.php');

        return $extensionId;
    }

    private function testUninstall(): string
    {
        $extensionId = $this->testInstall();

        $extensionManager = new ExtensionManager($this->getContainer());
        $extensionManager->uninstall(['id' => $extensionId]);

        $this->assertFileDoesNotExist('data/.backup/extensions/' . $extensionId);
        $this->assertFileDoesNotExist('data/upload/extensions/' . $extensionId);
        $this->assertFileExists('data/upload/extensions/' . $extensionId . 'z');

        $this->assertFileDoesNotExist('application/Espo/Modules/Test');
        $this->assertFileDoesNotExist('application/Espo/Modules/Test/Resources/metadata/scopes/TestEntity.json');
        $this->assertFileDoesNotExist('client/modules/test');
        $this->assertFileDoesNotExist('client/modules/test/src/views/test-entity/fields/custom-type.js');

        $this->assertFileExists('vendor/symfony');
        $this->assertFileExists('extension.php');
        $this->assertFileExists('upgrade.php');

        return $extensionId;
    }
}
