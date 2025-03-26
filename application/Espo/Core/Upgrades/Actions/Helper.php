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

namespace Espo\Core\Upgrades\Actions;

use Espo\Core\Exceptions\Error;
use Espo\Entities\Extension;
use Espo\ORM\EntityManager;
use RuntimeException;

class Helper
{
    private ?Base $actionObject = null;

    public function __construct(private EntityManager $entityManager)
    {}

    public function setActionObject(Base $actionObject): void
    {
        $this->actionObject = $actionObject;
    }

    /**
     * Check dependencies.
     *
     * @param array<string, string[]|string> $dependencyList
     * @throws Error
     */
    public function checkDependencies(mixed $dependencyList): bool
    {
        if (!$this->actionObject) {
            throw new RuntimeException("No action passed.");
        }

        if (!is_array($dependencyList)) {
            $dependencyList = (array) $dependencyList;
        }

        /** @var array<string, string[]|string> $dependencyList */

        foreach ($dependencyList as $extensionName => $extensionVersion) {
            $entity = $this->entityManager
                ->getRDBRepositoryByClass(Extension::class)
                ->where([
                    'name' => trim($extensionName),
                    'isInstalled' => true,
                ])
                ->findOne();

            $versionString = is_array($extensionVersion) ?
                implode(', ', $extensionVersion) :
                $extensionVersion;

            $errorMessage = "Dependency error: Extension '$extensionName' with version '$versionString' is missing.";

            if (
                !$entity ||
                !$this->actionObject->checkVersions(
                    $extensionVersion,
                    $entity->getVersion(),
                    $errorMessage
                )
            ) {
                throw new Error($errorMessage);
            }
        }

        return true;
    }
}
