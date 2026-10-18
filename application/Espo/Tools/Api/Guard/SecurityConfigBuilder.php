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

namespace Espo\Tools\Api\Guard;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use RenzoFranceschini\GuardCore\GeoIp\CountryResolver;
use RenzoFranceschini\GuardCore\Config\SecurityConfig;

/**
 * Maps the `guard` settings onto the named arguments of the engine's `SecurityConfig`, so every engine option is
 * available from `data/config.php`. An unknown key fails loudly.
 */
class SecurityConfigBuilder
{
    /** Settings that are not engine options, consumed by other builders. */
    private const array NON_ENGINE_KEYS = ['routes', 'agent', 'ipinfo'];

    /** Options that take a closure or an object, which a config file cannot express. */
    private const array NOT_CONFIGURABLE = [
        'onBlock',
        'customRequestCheck',
        'authVerifier',
        'customResponseModifier',
        'geoIpHandler',
    ];

    /**
     * @param array<string, mixed> $settings
     */
    public function build(
        array $settings,
        ?CountryResolver $geoIpHandler = null,
        ?Closure $onBlock = null,
    ): SecurityConfig
    {
        $options = array_diff_key($settings, array_flip(self::NON_ENGINE_KEYS));

        $unknownKeys = array_diff(array_keys($options), $this->getConfigurableOptionNames());

        if ($unknownKeys !== []) {
            throw new InvalidArgumentException(
                "Unknown guard config key(s): " . implode(', ', $unknownKeys) . "."
            );
        }

        return new SecurityConfig(...$options, geoIpHandler: $geoIpHandler, onBlock: $onBlock);
    }

    /**
     * @return string[]
     */
    private function getConfigurableOptionNames(): array
    {
        $constructor = (new ReflectionClass(SecurityConfig::class))->getConstructor();

        $names = array_map(fn ($parameter) => $parameter->getName(), $constructor?->getParameters() ?? []);

        return array_values(array_diff($names, self::NOT_CONFIGURABLE));
    }
}
