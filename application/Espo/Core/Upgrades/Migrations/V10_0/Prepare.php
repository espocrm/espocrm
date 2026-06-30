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

namespace Espo\Core\Upgrades\Migrations\V10_0;

use Espo\Core\Upgrades\Migration\Script;
use Espo\Entities\Extension;
use Espo\ORM\EntityManager;
use RuntimeException;

class Prepare implements Script
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function run(): void
    {
        $this->processCheckExtensions();
    }

    private function processCheckExtensions(): void
    {
        $errorMessageList = [];

        $this->processCheckExtension('Advanced Pack', '3.13.0', $errorMessageList);
        $this->processCheckExtension('Real Estate', '1.8.5', $errorMessageList);
        $this->processCheckExtension('VoIP Integration', '2.8.0', $errorMessageList);

        if (!count($errorMessageList)) {
            return;
        }

        $message = implode("\n\n", $errorMessageList);

        throw new RuntimeException($message);
    }

    /**
     * @param string[] $errorMessageList
     */
    private function processCheckExtension(string $name, string $minVersion, array &$errorMessageList): void
    {
        $extension = $this->entityManager
            ->getRDBRepositoryByClass(Extension::class)
            ->where([
                'name' => $name,
                'isInstalled' => true,
            ])
            ->findOne();

        if (!$extension) {
            return;
        }

        $version = $extension->getVersion();

        if (version_compare($version, $minVersion, '>=')) {
            return;
        }

        $message =
            "EspoCRM 10.0 is not compatible with '$name' extension of versions lower than $minVersion. " .
            "You need to upgrade the extension.";

        $errorMessageList[] = $message;
    }
}
