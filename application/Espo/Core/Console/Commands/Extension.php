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

namespace Espo\Core\Console\Commands;

use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Exceptions\Error;
use Espo\Core\Name\Field;
use Espo\Entities\Extension as ExtensionEntity;
use Espo\ORM\EntityManager;
use Espo\Core\Upgrades\ExtensionManager;
use Espo\Core\Container;
use Espo\Core\Utils\File\Manager as FileManager;

use Espo\ORM\Name\Attribute;
use Throwable;

/**
 * @noinspection PhpUnused
 */
class Extension implements Command
{
    public function __construct(
        private Container $container,
        private EntityManager $entityManager,
        private FileManager $fileManager
    ) {}

    /**
     * @throws Error
     */
    public function run(Params $params, IO $io): void
    {
        if ($params->hasFlag('l') || $params->hasFlag('list')) {
            $this->printList($io);

            return;
        }

        if ($params->hasFlag('u')) {
            $name = $params->getOption('name');
            $id = $params->getOption('id');

            if (!$name && !$id) {
                $io->writeLine("Can't uninstall. Specify --name=\"Extension Name\".");
                $io->setExitStatus(1);

                return;
            }

            $this->runUninstall($params, $io);

            return;
        }

        $file = $params->getOption('file');

        if (!$file) {
            $io->writeLine("");
            $io->writeLine("Install extension:");
            $io->writeLine("");
            $io->writeLine(" bin/command extension --file=\"path/to/package.zip\"");
            $io->writeLine("");

            $io->writeLine("Uninstall extension:");
            $io->writeLine("");
            $io->writeLine(" bin/command extension -u --name=\"Extension Name\"");
            $io->writeLine("");

            $io->writeLine("List all extensions:");
            $io->writeLine("");
            $io->writeLine(" bin/command extension --list");
            $io->writeLine("");

            return;
        }

        $this->runInstall($file, $io);
    }

    /**
     * @throws Error
     */
    private function runInstall(string $file, IO $io): void
    {
        $manager = $this->createExtensionManager();

        if (!$this->fileManager->isFile($file)) {
            $io->writeLine("File does not exist.");
            $io->setExitStatus(1);

            return;
        }

        $fileData = $this->fileManager->getContents($file);

        $fileDataEncoded = 'data:application/zip;base64,' . base64_encode($fileData);

        try {
            $id = $manager->upload($fileDataEncoded);
        } catch (Throwable $e) {
            $io->writeLine($e->getMessage());
            $io->setExitStatus(1);

            return;
        }

        $manifest = $manager->getManifestById($id);

        $name = $manifest['name'] ?? null;
        $version = $manifest['version'] ?? null;

        if (!$name) {
            $io->writeLine("Can't install. Bad manifest.json file.");
            $io->setExitStatus(1);

            return;
        }

        $io->write("Installing... Do not close the terminal. This may take a while...");

        try {
            $manager->install(['id' => $id]);
        } catch (Throwable $e) {
            $io->writeLine("");
            $io->writeLine($e->getMessage());
            $io->setExitStatus(1);

            return;
        }

        $io->writeLine("");
        $io->writeLine("Extension '$name' v$version is installed.\nExtension ID: '$id'.");
    }

    protected function runUninstall(Params $params, IO $io): void
    {
        $id = $params->getOption('id');
        $name = $params->getOption('name');
        $toKeep = $params->hasFlag('k');

        if ($id) {
            $record = $this->entityManager
                ->getRDBRepository(ExtensionEntity::ENTITY_TYPE)
                ->where([
                    Attribute::ID => $id,
                    'isInstalled' => true,
                ])
                ->findOne();

            if (!$record) {
                $io->writeLine("Extension with ID '$id' is not installed.");
                $io->setExitStatus(1);

                return;
            }

            $name = $record->get(Field::NAME);
        } else {
            if (!$name) {
                $io->writeLine("Can't uninstall. No --name or --id specified.");
                $io->setExitStatus(1);

                return;
            }

            $record = $this->entityManager
                ->getRDBRepository(ExtensionEntity::ENTITY_TYPE)
                ->where([
                    'name' => $name,
                    'isInstalled' => true,
                ])
                ->findOne();

            if (!$record) {
                $io->writeLine("Extension '$name' is not installed.");
                $io->setExitStatus(1);

                return;
            }

            $id = $record->getId();
        }

        $manager = $this->createExtensionManager();

        $io->write("Uninstalling... Do not close the terminal. This may take a while...");

        try {
            $manager->uninstall(['id' => $id]);
        } catch (Throwable $e) {
            $io->writeLine("");
            $io->writeLine($e->getMessage());
            $io->setExitStatus(1);

            return;
        }

        $io->writeLine("");

        if ($toKeep) {
            $io->writeLine("Extension '$name' is uninstalled.");
            $io->setExitStatus(1);

            return;
        }

        try {
            $manager->delete(['id' => $id]);
        } catch (Throwable $e) {
            $io->writeLine($e->getMessage());
            $io->writeLine("Extension '$name' is uninstalled but could not be deleted.");

            return;
        }

        $io->writeLine("Extension '$name' is uninstalled and deleted.");
    }

    private function printList(IO $io): void
    {
        $collection = $this->entityManager
            ->getRDBRepositoryByClass(ExtensionEntity::class)
            ->find();

        $count = count($collection);

        /** @noinspection PhpIfWithCommonPartsInspection */
        if ($count === 0) {
            $io->writeLine("");
            $io->writeLine("No extensions.");
            $io->writeLine("");

            return;
        }

        $io->writeLine("");
        $io->writeLine("Extensions:");
        $io->writeLine("");

        foreach ($collection as $extension) {
            $isInstalled = $extension->isInstalled();

            $io->writeLine(' Name: ' . $extension->getName());
            $io->writeLine(' ID: ' . $extension->getId());
            $io->writeLine(' Version: ' . $extension->getVersion());
            $io->writeLine(' Installed: ' . ($isInstalled ? 'yes' : 'no'));

            $io->writeLine("");
        }
    }

    private function createExtensionManager(): ExtensionManager
    {
        return new ExtensionManager($this->container);
    }
}
