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

namespace Espo\Core\EntryPoint;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\ClassFinder;

/**
 * Runs entry points.
 */
class EntryPointManager
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private ClassFinder $classFinder
    ) {}

    /**
     * @throws NotFound
     */
    public function checkAuthRequired(string $name): bool
    {
        $className = $this->getClassName($name);

        if (!$className) {
            throw new NotFoundSilent("Entry point '$name' not found.");
        }

        $noAuth = false;

        if (isset($className::$noAuth)) {
            $noAuth = $className::$noAuth;
        }

        if ($noAuth) {
            return false;
        }

        // for backward compatibility
        return $className::$authRequired ?? true;
    }

    /**
     * @throws NotFound
     */
    public function run(string $name, Request $request, Response $response): void
    {
        $className = $this->getClassName($name);

        if (!$className) {
            throw new NotFoundSilent("Entry point '$name' not found.");
        }

        $entryPoint = $this->injectableFactory->create($className);

        $entryPoint->run($request, $response);
    }

    /**
     * @return ?class-string<EntryPoint>
     */
    private function getClassName(string $name): ?string
    {
        /** @var ?class-string<EntryPoint> */
        return $this->classFinder->find('EntryPoints', ucfirst($name));
    }
}
