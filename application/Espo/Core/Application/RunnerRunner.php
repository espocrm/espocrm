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
    private $log;

    private $applicationUser;

    private $injectableFactory;

    public function __construct(
        Log $log,
        ApplicationUser $applicationUser,
        InjectableFactory $injectableFactory
    ) {
        $this->log = $log;
        $this->applicationUser = $applicationUser;
        $this->injectableFactory = $injectableFactory;
    }

    public function run(string $className, ?Params $params = null): void
    {
        if (!$className || !class_exists($className)) {
            $this->log->error("Application runner '{$className}' does not exist.");

            throw new RunnerException();
        }

        $class = new ReflectionClass($className);

        if (
            $class->getStaticPropertyValue('cli', false) &&
            substr(php_sapi_name(), 0, 3) !== 'cli'
        ) {
            throw new RunnerException("Can be run only via CLI.");
        }

        if ($class->getStaticPropertyValue('setupSystemUser', false)) {
            $this->applicationUser->setupSystemUser();
        }

        $runner = $this->injectableFactory->create($className);

        if ($runner instanceof Runner) {
            $runner->run();

            return;
        }

        if ($runner instanceof RunnerParameterized) {
            $runner->run(
                $params ?? Params::create()
            );

            return;
        }

        throw new RunnerException("Class should implement Runner or RunnerParameterized interface.");
    }
}
