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

namespace Espo\ORM;

use Closure;

/**
 * Event dispatcher.
 */
class EventDispatcher
{
    /** @var array{'metadataUpdate': Closure[]} */
    private array $data;

    private const METADATA_UPDATE = 'metadataUpdate';

    public function __construct()
    {
        $this->data = [
            self::METADATA_UPDATE => [],
        ];
    }

    public function subscribeToMetadataUpdate(Closure $callback): void
    {
        $this->data[self::METADATA_UPDATE][] = $callback;
    }

    /**
     * @internal
     * @since 8.4.0
     */
    public function unsubscribeFromMetadataUpdate(Closure $closure): void
    {
        $list = &$this->data[self::METADATA_UPDATE];

        $index = array_search($closure, $list);

        if ($index !== false) {
            unset($list[$index]);

            $list = array_values($list);
        }
    }

    public function dispatchMetadataUpdate(): void
    {
        foreach ($this->data[self::METADATA_UPDATE] as $callback) {
            $callback();
        }
    }
}
