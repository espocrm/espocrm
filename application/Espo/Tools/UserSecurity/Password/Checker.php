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

namespace Espo\Tools\UserSecurity\Password;

use SensitiveParameter;

class Checker
{
    private const SPECIAL_CHARACTERS = "'-!\"#$%&()*,./:;?@[]^_`{|}~+<=>";

    public function __construct(
        private ConfigProvider $configProvider,
    ) {}

    public function checkStrength(#[SensitiveParameter] string $password): bool
    {
        $minLength = $this->configProvider->getStrengthLength();

        if ($minLength) {
            if (mb_strlen($password) < $minLength) {
                return false;
            }
        }

        $requiredLetterCount = $this->configProvider->getStrengthLetterCount();

        if ($requiredLetterCount) {
            $letterCount = 0;

            foreach (str_split($password) as $c) {
                if (ctype_alpha($c)) {
                    $letterCount++;
                }
            }

            if ($letterCount < $requiredLetterCount) {
                return false;
            }
        }

        $requiredNumberCount = $this->configProvider->getStrengthNumberCount();

        if ($requiredNumberCount) {
            $numberCount = 0;

            foreach (str_split($password) as $c) {
                if (is_numeric($c)) {
                    $numberCount++;
                }
            }

            if ($numberCount < $requiredNumberCount) {
                return false;
            }
        }

        $bothCases = $this->configProvider->getStrengthBothCases();

        if ($bothCases) {
            $ucCount = 0;
            $lcCount = 0;

            foreach (str_split($password) as $c) {
                if (ctype_alpha($c) && $c === mb_strtoupper($c)) {
                    $ucCount++;
                }

                if (ctype_alpha($c) && $c === mb_strtolower($c)) {
                    $lcCount++;
                }
            }
            if (!$ucCount || !$lcCount) {
                return false;
            }
        }

        $specialCharacterCount = $this->configProvider->getStrengthSpecialCharacterCount();

        if ($specialCharacterCount) {
            $realSpecialCharacterCount = 0;

            foreach (str_split($password) as $c) {
                if (str_contains(self::SPECIAL_CHARACTERS, $c)) {
                    $realSpecialCharacterCount++;
                }
            }

            if ($realSpecialCharacterCount < $specialCharacterCount) {
                return false;
            }
        }

        return true;
    }
}
