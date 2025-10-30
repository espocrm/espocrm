<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Tools\Kanban;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Metadata;

class MetadataProvider
{
    public function __construct(
        private Metadata $metadata,
    ) {}


    /**
     * @return string[]
     * @throws Error
     */
    public function getStatusList(string $entityType): array
    {
        $field = $this->getStatusField($entityType);

        $statusList = $this->metadata->get("entityDefs.$entityType.fields.$field.options");
        $optionsReference = $this->metadata->get("entityDefs.$entityType.fields.$field.optionsReference");

        if (is_string($optionsReference) && str_contains($optionsReference, '.')) {
            [$refEntityType, $refField] = explode('.', $optionsReference);

            $statusList = $this->metadata->get("entityDefs.$refEntityType.fields.$refField.options");
        }

        if (!$statusList) {
            throw new Error("No options for status field for entity type '$entityType'.");
        }

        return $statusList;
    }

    /**
     * @throws Error
     */
    public function getStatusField(string $entityType): string
    {
        $statusField = $this->metadata->get("scopes.$entityType.statusField");

        if (!$statusField) {
            throw new Error("No status field for entity type '$entityType'.");
        }

        return $statusField;
    }

    /**
     * @return string[]
     */
    public function getStatusIgnoreList(string $entityType): array
    {
        return $this->metadata->get("scopes.$entityType.kanbanStatusIgnoreList") ?? [];
    }
}
