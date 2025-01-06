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
use Espo\Core\Upgrades\Actions\Base as ActionBase;

class ActionManager
{
    private string $managerName;
    private Container $container;
    /** @var array<string, array<string, ActionBase>> */
    private $objects;
    protected ?string $currentAction;
    /** @var array<string, mixed> */
    protected array $params;

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(string $managerName, Container $container, array $params)
    {
        $this->managerName = $managerName;
        $this->container = $container;

        $params['name'] = $managerName;
        $this->params = $params;
    }

    protected function getManagerName(): string
    {
        return $this->managerName;
    }

    protected function getContainer(): Container
    {
        return $this->container;
    }

    public function setAction(string $action): void
    {
        $this->currentAction = $action;
    }

    public function getAction(): string
    {
        assert($this->currentAction !== null);

        return $this->currentAction;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @throws Error
     */
    public function run(mixed $data): mixed
    {
        $object = $this->getObject();

        return $object->run($data);
    }

    /**
     * @throws Error
     */
    public function getActionClass(string $actionName): ActionBase
    {
        return $this->getObject($actionName);
    }

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function getManifest(): array
    {
        return $this->getObject()->getManifest();
    }

    /**
     * @param ?string $actionName
     * @throws Error
     */
    protected function getObject(?string $actionName = null): ActionBase
    {
        $managerName = $this->getManagerName();

        if (!$actionName) {
            $actionName = $this->getAction();
        }

        if (!isset($this->objects[$managerName][$actionName])) {
            $class = "Espo\\Core\\Upgrades\\Actions\\" . ucfirst($managerName) . '\\' . ucfirst($actionName);

            if (!class_exists($class)) {
                throw new Error('Could not find an action ['.ucfirst($actionName).'], class ['.$class.'].');
            }

            /** @var class-string<ActionBase> $class */

            $this->objects[$managerName][$actionName] = new $class($this->container, $this);
        }

        return $this->objects[$managerName][$actionName];
    }
}
