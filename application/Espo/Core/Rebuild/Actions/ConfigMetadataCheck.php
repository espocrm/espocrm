<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Rebuild\Actions;

use Espo\Core\Exceptions\Error;
use Espo\Core\Rebuild\RebuildAction;

use Espo\Core\Utils\Config\Access;
use Espo\Core\Utils\Metadata;

/**
 * Check whether config metadata is valid.
 */
class ConfigMetadataCheck implements RebuildAction
{
    private Metadata $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @throws Error
     */
    public function process(): void
    {
        $levelList = [
            'internal', // @todo Change to constant.
            Access::LEVEL_ADMIN,
            Access::LEVEL_GLOBAL,
            Access::LEVEL_SUPER_ADMIN,
            Access::LEVEL_SYSTEM,
        ];

        $params = $this->metadata->get(['app', 'config', 'params']) ?? [];

        foreach ($params as $name => $item) {
            $level = $item['level'] ?? null;

            if ($level !== null && !in_array($level, $levelList)) {
                throw new Error("Config parameter '{$name}' has not a allowed level in app > config.");
            }
        }
    }
}
