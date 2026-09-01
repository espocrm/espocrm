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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\Application\Exceptions\RunnerException;
use Espo\Core\Application\RunnerParameterized;
use Espo\Core\Application\Runner\Params;
use Espo\Core\EntryPoint\Params as EntryPointParams;
use Espo\Core\EntryPoint\Starter;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use RuntimeException;

/**
 * Runs an entry point.
 */
class EntryPoint implements RunnerParameterized
{
    public const string PARAM_ENTRY_POINT = 'entryPoint';
    public const string PARAM_FINAL = 'final';

    public function __construct(private Starter $starter)
    {}

    public function run(Params $params): void
    {
        $entryPointParams = new EntryPointParams(
            name: $this->getName($params),
            final: $params->get(self::PARAM_FINAL) === true,
        );

        try {
            $this->starter->start($entryPointParams);
        } catch (Forbidden|NotFound $e) {
            $message = $e->getMessage();

            throw new RunnerException($message, previous: $e);
        }
    }

    private function getName(Params $params): ?string
    {
        $name = $params->get(self::PARAM_ENTRY_POINT);

        if ($name !== null && !is_string($name)) {
            throw new RuntimeException("Bad 'entryPoint' value.");
        }

        return $name;
    }
}
