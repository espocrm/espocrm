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

namespace Espo\Core\Application;

use Espo\Core\Utils\Log;
use Espo\Core\ApplicationUser;
use Espo\Core\InjectableFactory;
use Espo\Core\Application\Exceptions\RunnerException;
use Espo\Core\Application\Runner\Params;

use ReflectionClass;

/**
 * Runs a runner.
 */
class RunnerRunner
{
    public function __construct(
        private Log $log,
        private ApplicationUser $applicationUser,
        private InjectableFactory $injectableFactory
    ) {}

    /**
     * @param class-string<Runner|RunnerParameterized> $className
     * @throws RunnerException
     */
    public function run(string $className, ?Params $params = null): void
    {
        if (!class_exists($className)) {
            $this->log->error("Application runner '$className' does not exist.");

            throw new RunnerException();
        }

        $class = new ReflectionClass($className);

        if (
            $class->getStaticPropertyValue('cli', false) &&
            !str_starts_with(php_sapi_name() ?: '', 'cli')
        ) {
            throw new RunnerException("Can be run only via CLI.");
        }

        if ($class->getStaticPropertyValue('setupSystemUser', false)) {
            $this->applicationUser->setupSystemUser();
        }

        $runner = $this->injectableFactory->create($className);

        if ($runner instanceof RunnerParameterized) {
            $runner->run($params ?? Params::create());

            return;
        }

        $runner->run();
    }
}
