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

namespace Espo\Core\Authentication\Jwt;

use Espo\Core\Authentication\Jwt\Exceptions\Expired;
use Espo\Core\Authentication\Jwt\Exceptions\NotBefore;

class Validator
{
    private const DEFAULT_TIME_LEEWAY = 60 * 4;
    private int $timeLeeway;
    private ?int $now;

    public function __construct(
        ?int $timeLeeway = null,
        ?int $now = null
    ) {
        $this->timeLeeway = $timeLeeway ?? self::DEFAULT_TIME_LEEWAY;
        $this->now = $now;
    }

    /**
     * @throws Expired
     * @throws NotBefore
     */
    public function validate(Token $token): void
    {
        $exp = $token->getPayload()->getExp();
        $nbf = $token->getPayload()->getNbf();

        $now = $this->now ?? time();

        if ($exp && $exp + $this->timeLeeway <= $now) {
            throw new Expired("JWT expired.");
        }

        if ($nbf && $now < $nbf - $this->timeLeeway) {
            throw new NotBefore("JWT used before allowed time.");
        }
    }
}
