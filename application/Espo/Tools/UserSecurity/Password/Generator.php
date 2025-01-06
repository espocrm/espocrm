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

use Espo\Core\Utils\Util;

/**
 * A password generator.
 *
 * @todo Use an interface with binding.
 */
class Generator
{
    public function __construct(
        private ConfigProvider $configProvider,
    ) {}

    /**
     * Generate a password.
     */
    public function generate(): string
    {
        $length = $this->configProvider->getStrengthLength();
        $letterCount = $this->configProvider->getStrengthLetterCount();
        $numberCount = $this->configProvider->getStrengthNumberCount();
        $specialCharacterCount = $this->configProvider->getStrengthSpecialCharacterCount() ?? 0;

        $generateLength = $this->configProvider->getGenerateLength() ?? 10;
        $generateLetterCount = $this->configProvider->getGenerateLetterCount() ?? 4;
        $generateNumberCount = $this->configProvider->getGenerateNumberCount() ?? 2;

        $length = is_null($length) ? $generateLength : $length;
        $letterCount = is_null($letterCount) ? $generateLetterCount : $letterCount;
        $numberCount = is_null($numberCount) ? $generateNumberCount : $numberCount;

        if ($length < $generateLength) {
            $length = $generateLength;
        }

        if ($letterCount < $generateLetterCount) {
            $letterCount = $generateLetterCount;
        }

        if ($numberCount < $generateNumberCount) {
            $numberCount = $generateNumberCount;
        }

        return Util::generatePassword($length, $letterCount, $numberCount, true, $specialCharacterCount);
    }
}
