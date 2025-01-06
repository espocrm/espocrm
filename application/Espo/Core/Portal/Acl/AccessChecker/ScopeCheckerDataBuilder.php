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

namespace Espo\Core\Portal\Acl\AccessChecker;

use Closure;

/**
 * Builds scope checker data.
 */
class ScopeCheckerDataBuilder
{
    private Closure $isOwnChecker;
    private Closure $inAccountChecker;
    private Closure $inContactChecker;

    public function __construct()
    {
        $this->isOwnChecker = function (): bool {
            return false;
        };

        $this->inAccountChecker = function (): bool {
            return false;
        };

        $this->inContactChecker = function (): bool {
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

    public function setInAccount(bool $value): self
    {
        if ($value) {
            $this->inAccountChecker = function (): bool {
                return true;
            };

            return $this;
        }

        $this->inAccountChecker = function (): bool {
            return false;
        };

        return $this;
    }

    public function setInContact(bool $value): self
    {
        if ($value) {
            $this->inContactChecker = function (): bool {
                return true;
            };

            return $this;
        }

        $this->inContactChecker = function (): bool {
            return false;
        };

        return $this;
    }

    /**
     * @param Closure(): bool $checker
     */
    public function setIsOwnChecker(Closure $checker): self
    {
        $this->isOwnChecker = $checker;

        return $this;
    }

    /**
     * @param Closure(): bool $checker
     */
    public function setInAccountChecker(Closure $checker): self
    {
        $this->inAccountChecker = $checker;

        return $this;
    }

    /**
     * @param Closure(): bool $checker
     */
    public function setInContactChecker(Closure $checker): self
    {
        $this->inContactChecker = $checker;

        return $this;
    }

    public function build(): ScopeCheckerData
    {
        return new ScopeCheckerData($this->isOwnChecker, $this->inAccountChecker, $this->inContactChecker);
    }
}
