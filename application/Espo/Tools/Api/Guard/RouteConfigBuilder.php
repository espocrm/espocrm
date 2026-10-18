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

use InvalidArgumentException;
use ReflectionClass;
use RenzoFranceschini\GuardCore\Behavior\BehaviorRule;
use RenzoFranceschini\GuardCore\Config\SecurityConfig;
use RenzoFranceschini\GuardCore\Decorators\SecurityDecorator;
use RenzoFranceschini\GuardCore\Routing\RouteConfig;

/**
 * Builds the per-route policies from `guard.routes`. A key is an optional method prefix plus a path, for example
 * `POST /api/v1/LeadCapture/`. The engine drops invalid route values silently, so they are validated here first.
 */
class RouteConfigBuilder
{
    private const string HONEYPOT_OPTION = 'honeypotFields';

    /** Options that take a closure, which a config file cannot express. */
    private const array NOT_CONFIGURABLE = ['authVerifier', 'apiKeyVerifier', 'customValidators'];

    /**
     * @param array<string, mixed> $routes
     * @return array<string, RouteConfig>
     */
    public function buildMap(array $routes): array
    {
        $map = [];

        foreach ($routes as $key => $options) {
            if (!is_array($options)) {
                throw new InvalidArgumentException("Guard route '$key' must be a map of options.");
            }

            $map[$key] = $this->build($key, $options);
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function build(string $key, array $options): RouteConfig
    {
        $unknownOptions = array_diff(array_keys($options), $this->getConfigurableOptionNames());

        if ($unknownOptions !== []) {
            throw new InvalidArgumentException(
                "Unknown option(s) for guard route '$key': " . implode(', ', $unknownOptions) . "."
            );
        }

        $this->assertBypassedChecksAreValid($key, $options);
        $this->assertBehaviorRulesAreValid($key, $options);

        $honeypotFields = $options[self::HONEYPOT_OPTION] ?? [];
        unset($options[self::HONEYPOT_OPTION]);

        if ($honeypotFields !== []) {
            $options['customValidators'] = [SecurityDecorator::honeypotValidator($honeypotFields)];
        }

        return new RouteConfig(...$options);
    }

    /**
     * @return string[]
     */
    private function getConfigurableOptionNames(): array
    {
        $constructor = (new ReflectionClass(RouteConfig::class))->getConstructor();

        $names = array_map(fn ($parameter) => $parameter->getName(), $constructor?->getParameters() ?? []);

        return array_values(array_diff([...$names, self::HONEYPOT_OPTION], self::NOT_CONFIGURABLE));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function assertBypassedChecksAreValid(string $key, array $options): void
    {
        $invalid = array_diff($options['bypassedChecks'] ?? [], SecurityConfig::VALID_BYPASS_CHECKS);

        if ($invalid !== []) {
            throw new InvalidArgumentException(
                "Invalid bypassedChecks for guard route '$key': " . implode(', ', $invalid) . "."
            );
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function assertBehaviorRulesAreValid(string $key, array $options): void
    {
        foreach ($options['behaviorRules'] ?? [] as $rule) {
            try {
                BehaviorRule::fromArray($rule);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("Invalid behavior rule for guard route '$key': " . $e->getMessage());
            }
        }
    }
}
