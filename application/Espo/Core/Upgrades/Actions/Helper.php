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

namespace Espo\Core\Upgrades\Actions;

use Espo\Core\Exceptions\Error;

class Helper
{
    /**
     * @var ?\Espo\Core\Upgrades\Actions\Base
     */
    private $actionObject;

    /**
     * @param ?\Espo\Core\Upgrades\Actions\Base $actionObject $actionObject
     */
    public function __construct($actionObject = null)
    {
        if (isset($actionObject)) {
            $this->setActionObject($actionObject);
        }
    }

    /**
     * @return void
     */
    public function setActionObject(\Espo\Core\Upgrades\Actions\Base $actionObject)
    {
        $this->actionObject = $actionObject;
    }

    /**
     * @return ?\Espo\Core\Upgrades\Actions\Base
     */
    protected function getActionObject()
    {
        return $this->actionObject;
    }

    /**
     * Check dependencies.
     *
     * @param array<string, string[]|string> $dependencyList
     * @return bool
     * @throws Error
     */
    public function checkDependencies($dependencyList)
    {
        if (!is_array($dependencyList)) { /** @phpstan-ignore-line */
            $dependencyList = (array) $dependencyList;
        }

        /** @var array<string, string[]|string> $dependencyList */

        $actionObject = $this->getActionObject();

        assert($actionObject !== null);

        foreach ($dependencyList as $extensionName => $extensionVersion) {
            $dependencyExtensionEntity = $actionObject
                ->getEntityManager()
                ->getRDBRepository('Extension')
                ->where([
                    'name' => trim($extensionName),
                    'isInstalled' => true,
                ])
                ->findOne();

            $versionString = is_array($extensionVersion) ?
                implode(', ', $extensionVersion) :
                $extensionVersion;

            $errorMessage = 'Dependency Error: The extension "' . $extensionName .'" with version "'.
                $versionString . '" is missing.';

            if (
                !isset($dependencyExtensionEntity) ||
                !$actionObject->checkVersions(
                    $extensionVersion,
                    $dependencyExtensionEntity->get('version'),
                    $errorMessage
                )
            ) {
                throw new Error($errorMessage);
            }
        }

        return true;
    }
}
