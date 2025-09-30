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

namespace Espo\Core\Utils\Database\Schema;

use Doctrine\DBAL\Types\Type;
use Espo\Core\Utils\Database\ConfigDataProvider;
use Espo\Core\Utils\Metadata;

class MetadataProvider
{
    public function __construct(
        private ConfigDataProvider $configDataProvider,
        private Metadata $metadata
    ) {}

    private function getPlatform(): string
    {
        return $this->configDataProvider->getPlatform();
    }

    /**
     * @return class-string<RebuildAction>[]
     */
    public function getPreRebuildActionClassNameList(): array
    {
        /** @var class-string<RebuildAction>[] */
        return $this->metadata
            ->get(['app', 'databasePlatforms', $this->getPlatform(), 'preRebuildActionClassNameList']) ?? [];
    }

    /**
     * @return class-string<RebuildAction>[]
     */
    public function getPostRebuildActionClassNameList(): array
    {
        /** @var class-string<RebuildAction>[] */
        return $this->metadata
            ->get(['app', 'databasePlatforms', $this->getPlatform(), 'postRebuildActionClassNameList']) ?? [];
    }

    /**
     * @return array<string, class-string<Type>>
     */
    public function getDbalTypeClassNameMap(): array
    {
        /** @var array<string, class-string<Type>> */
        return $this->metadata
            ->get(['app', 'databasePlatforms', $this->getPlatform(), 'dbalTypeClassNameMap']) ?? [];
    }
}
