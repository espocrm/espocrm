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

namespace Espo\Core\Utils\Metadata\AdditionalBuilder;

use Espo\Core\Utils\Metadata\AdditionalBuilder;
use Espo\Core\Utils\ObjectUtil;
use stdClass;

/**
 * Establishes backward compatibility of clientDefs > {scope} > dynamicLogic.
 * The dynamic logic is NOT supposed to be set in custom clientDefs as of v9.1.
 * The custom dynamic logic is supposed to be moved to logicDefs by an upgrade script.
 * But extensions can have a dynamic logic defined in clientDefs as they may be supposed
 * to be compatible with older Espo versions.
 *
 * @noinspection PhpUnused
 */
class LogicDefsBc implements AdditionalBuilder
{
    public function build(stdClass $data): void
    {
        if (!isset($data->clientDefs)) {
            return;
        }

        /** @var array<string, stdClass> $clientDefs */
        $clientDefs = get_object_vars($data->clientDefs);

        foreach ($clientDefs as $scope => $defs) {
            $this->processScope($scope, $data);
        }
    }

    private function processScope(string $scope, stdClass $data): void
    {
        if (!isset($data->clientDefs->$scope)) {
            return;
        }

        /** @var stdClass $clientDefs */
        $clientDefs = $data->clientDefs->$scope;

        if (!isset($clientDefs->dynamicLogic)) {
            return;
        }

        /** @var stdClass $dynamicLogic */
        $dynamicLogic = $clientDefs->dynamicLogic;

        $keys = [
            'fields',
            'panels',
        ];

        $subKeys = [
            'visible',
            'required',
            'readOnly',
            'invalid',
        ];

        $data->logicDefs ??= (object) [];
        $data->logicDefs->$scope ??= (object) [];

        /** @var stdClass $logicDefs */
        $logicDefs = $data->logicDefs->$scope;

        $customFile = "custom/Espo/Custom/Resources/metadata/logicDefs/$scope.json";

        $customLogicDefs = file_exists($customFile) ?
            json_decode(file_get_contents($customFile) ?: '') :
            null;

        if (!$customLogicDefs instanceof stdClass) {
            $customLogicDefs = (object) [];
        }

        foreach ($keys as $key) {
            if (!isset($dynamicLogic->$key) || !is_object($dynamicLogic->$key)) {
                continue;
            }

            /** @var array<string, stdClass> $defs */
            $defs = get_object_vars($dynamicLogic->$key);

            foreach ($defs as $name => $subDefs) {

                foreach ($subKeys as $subKey) {
                    if (!property_exists($subDefs, $subKey)) {
                        continue;
                    }

                    if (
                        isset($customLogicDefs->$key->$name) &&
                        property_exists($customLogicDefs->$key->$name, $subKey)
                    ) {
                        // Overridden in custom.
                        continue;
                    }

                    /** @var stdClass|null $item */
                    $item = $subDefs->$subKey;

                    $logicDefs->$key ??= (object) [];
                    $logicDefs->$key->$name ??= (object) [];

                    $logicDefs->$key->$name->$subKey = $item !== null ?
                        ObjectUtil::clone($item) :
                        null;
                }
            }
        }

        if (isset($dynamicLogic->options)) {
            /** @var array<string, stdClass[]> $defs */
            $defs = get_object_vars($dynamicLogic->options);

            foreach ($defs as $name => $subDefs) {
                if (
                    isset($customLogicDefs->options) &&
                    property_exists($customLogicDefs->options, $name)
                ) {
                    // Overridden in custom.
                    continue;
                }

                $logicDefs->options ??= (object) [];

                $logicDefs->options->$name = $subDefs !== null ?
                    array_map(fn ($it) => ObjectUtil::clone($it), $subDefs) :
                    null;
            }
        }
    }
}
