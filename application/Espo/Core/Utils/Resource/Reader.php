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

namespace Espo\Core\Utils\Resource;

use Espo\Core\Utils\File\Unifier;
use Espo\Core\Utils\File\UnifierObj;
use Espo\Core\Utils\Resource\Reader\Params;

use stdClass;

/**
 * Reads resource data. Reading is expensive. Read data is supposed to be cached after.
 */
class Reader
{
    public function __construct(
        private Unifier $unifier,
        private UnifierObj $unifierObj
    ) {}

    /**
     * Read resource data.
     */
    public function read(string $path, Params $params): stdClass
    {
        /** @var stdClass */
        return $this->unifierObj->unify($path, $params->noCustom(), $params->getForceAppendPathList());
    }

    /**
     * Read resource data as an associative array.
     *
     * @return array<string, mixed>
     */
    public function readAsArray(string $path, Params $params): array
    {
        /** @var array<string, mixed> */
        return $this->unifier->unify($path, $params->noCustom(), $params->getForceAppendPathList());
    }
}
