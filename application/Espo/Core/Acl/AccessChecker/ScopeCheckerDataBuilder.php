<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Acl\AccessChecker;

/**
 * Builds scope checker data.
 */
class ScopeCheckerDataBuilder
{
    private $isOwnChecker;

    private $inTeamChecker;

    public function __construct()
    {
        $this->isOwnChecker = function (): bool {
            return false;
        };

        $this->inTeamChecker = function (): bool {
            return false;
        };
    }

    public function setIsOwn(bool $value): self
    {
        if ($value) {
            $this->isOwnChecker = function (): bool {
                return true;
            };

            return $this;
        }

        $this->isOwnChecker = function (): bool {
            return false;
        };

        return $this;
    }

    public function setInTeam(bool $value): self
    {
        if ($value) {
            $this->inTeamChecker = function (): bool {
                return true;
            };

            return $this;
        }

        $this->inTeamChecker = function (): bool {
            return false;
        };

        return $this;
    }

    public function setIsOwnChecker(callable $checker): self
    {
        $this->isOwnChecker = $checker;

        return $this;
    }

    public function setInTeamChecker(callable $checker): self
    {
        $this->inTeamChecker = $checker;

        return $this;
    }

    public function build(): ScopeCheckerData
    {
        return new ScopeCheckerData($this->isOwnChecker, $this->inTeamChecker);
    }
}
