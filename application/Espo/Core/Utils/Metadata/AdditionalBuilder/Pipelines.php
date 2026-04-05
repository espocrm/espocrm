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
use stdClass;

/**
 * @noinspection PhpUnused
 */
class Pipelines implements AdditionalBuilder
{
    public function build(stdClass $data): void
    {
        $scopes = $data->scopes ?? null;

        if (!$scopes instanceof stdClass) {
            return;
        }

        foreach (get_object_vars($scopes) as $scope => $itemDefs) {
            if (
                !($itemDefs->entity ?? false) ||
                !($itemDefs->pipelines ?? false)
            ) {
                continue;
            }

            $statusField = $itemDefs->statusField ?? null;

            if (!$statusField) {
                continue;
            }

            $entityDefs = $data->entityDefs->$scope ?? null;

            if (!$entityDefs instanceof stdClass) {
                return;
            }

            $fieldsDefs = $entityDefs->fields ?? null;

            if (!$fieldsDefs instanceof stdClass) {
                return;
            }

            $statusFieldDefs = $fieldsDefs->$statusField ?? null;

            if (!$statusFieldDefs instanceof stdClass) {
                return;
            }

            $statusFieldDefs->readOnly = true;
        }
    }
}
