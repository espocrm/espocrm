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

namespace Espo\Core\Upgrades;

use Espo\Core\Container;
use Espo\Core\Exceptions\Error;

abstract class Base
{
    protected ActionManager $actionManager;

    protected ?string $name = null;
    /** @var array<string, mixed> */
    protected array $params = [];

    const UPLOAD = 'upload';
    const INSTALL = 'install';
    const UNINSTALL = 'uninstall';
    const DELETE = 'delete';

    public function __construct(Container $container)
    {
        $this->actionManager = new ActionManager($this->name ?? '', $container, $this->params);
    }

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function getManifest(): array
    {
        return $this->actionManager->getManifest();
    }

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function getManifestById(string $processId): array
    {
        $actionClass = $this->actionManager->getActionClass(self::INSTALL);
        $actionClass->setProcessId($processId);

        return $actionClass->getManifest();
    }

    /**
     * @throws Error
     */
    public function upload(string $data): string
    {
        $this->actionManager->setAction(self::UPLOAD);

        return $this->actionManager->run($data);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function install(array $data): mixed
    {
        $this->actionManager->setAction(self::INSTALL);

        return $this->actionManager->run($data);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function uninstall(array $data): mixed
    {
        $this->actionManager->setAction(self::UNINSTALL);

        return $this->actionManager->run($data);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Error
     */
    public function delete(array $data): mixed
    {
        $this->actionManager->setAction(self::DELETE);

        return $this->actionManager->run($data);
    }

    /**
     * @param array<string, mixed> $params
     * @throws Error
     */
    public function runInstallStep(string $stepName, array $params = []): void
    {
        $this->runActionStep(self::INSTALL, $stepName, $params);
    }

    /**
     * @param array<string, mixed> $params
     * @throws Error
     * @noinspection PhpSameParameterValueInspection
     */
    private function runActionStep(string $actionName, string $stepName, array $params = []): void
    {
        $actionClass = $this->actionManager->getActionClass($actionName);
        $methodName = 'step' . ucfirst($stepName);

        if (!method_exists($actionClass, $methodName)) {
            if (!empty($params['id'])) {
                $actionClass->setProcessId($params['id']);
                $actionClass->throwErrorAndRemovePackage("Step \"$stepName\" is not found.");
            }

            throw new Error('Step "'. $stepName .'" is not found.');
        }

        $actionClass->$methodName($params); // throw an Exception on error
    }
}
