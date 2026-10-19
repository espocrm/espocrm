<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Container;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;

/** @noinspection PhpMultipleClassDeclarationsInspection */
class BeforeUpgrade
{
    private ?Metadata $metadata = null;
    private ?FileManager $fileManager = null;

    /**
     * @throws Exception
     */
    public function run(Container $container): void
    {
        $this->metadata = $container->getByClass(Metadata::class);
        $this->fileManager = $container->getByClass(FileManager::class);

        $this->cleanupMetadata();
    }

    private function cleanupMetadata(): void
    {
        foreach (array_keys($this->metadata->get('entityAcl', [])) as $scope) {
            if (
                $this->metadata->get("scopes.$scope") ||
                $this->metadata->get("entityDefs.$scope")
            ) {
                continue;
            }

            $file = "custom/Espo/Custom/Resources/metadata/entityAcl/$scope.json";

            if (!$this->fileManager->isFile($file)) {
                continue;
            }

            $this->fileManager->removeFile($file);
        }

        foreach (array_keys($this->metadata->get('aclDefs', [])) as $scope) {
            if (
                $this->metadata->get("scopes.$scope") ||
                $this->metadata->get("entityDefs.$scope")
            ) {
                continue;
            }

            $file = "custom/Espo/Custom/Resources/metadata/aclDefs/$scope.json";

            if (!$this->fileManager->isFile($file)) {
                continue;
            }

            $this->fileManager->removeFile($file);
        }
    }
}
