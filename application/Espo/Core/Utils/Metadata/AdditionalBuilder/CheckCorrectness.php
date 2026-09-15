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

namespace Espo\Core\Utils\Metadata\AdditionalBuilder;

use Espo\Core\Utils\Metadata\AdditionalBuilder;
use RuntimeException;
use stdClass;

/**
 * @noinspection PhpUnused
 */
class CheckCorrectness implements AdditionalBuilder
{

    public function build(stdClass $data): void
    {
        $entityAcl = $data->entityAcl ?? null;

        if (!is_object($entityAcl)) {
            throw new RuntimeException("No entityAcl.");
        }

        foreach (get_object_vars($entityAcl) as $scope => $defs) {
            if (!is_object($defs)) {
                throw new RuntimeException("Bad data in entityAcl > $scope.");
            }

            $isEntity = $data->scopes?->$scope->entity ?? null;
            $hasEntityDefs = is_object($data->entityDefs->$scope ?? null);

            if (!$isEntity && !$hasEntityDefs) {
                throw new RuntimeException("Unexpected scope '$scope' entityAcl. Entity '$scope' is not defined.");
            }
        }
    }
}
