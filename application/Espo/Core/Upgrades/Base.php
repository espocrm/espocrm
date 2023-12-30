<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Error;

abstract class Base
{
    /**
     * @var \Espo\Core\Container
     */
    private $container;

    /**
     * @var ActionManager
     */
    protected $actionManager;

    /**
     * @var ?string
     */
    protected $name = null;

    /**
     * @var array<string, mixed>
     */
    protected $params = [];

    const UPLOAD = 'upload';

    const INSTALL = 'install';

    const UNINSTALL = 'uninstall';

    const DELETE = 'delete';

    /**
     * @param \Espo\Core\Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        $this->actionManager = new ActionManager($this->name ?? '', $container, $this->params);
    }

    /**
     * @return \Espo\Core\Container
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return ActionManager
     */
    protected function getActionManager()
    {
        return $this->actionManager;
    }

    /**
     * @return array<string, mixed>
     * @throws Error
     */
    public function getManifest()
    {
        return $this->getActionManager()->getManifest();
    }

    /**
     * @param string $processId
     * @return array<string, mixed>
     * @throws Error
     */
    public function getManifestById($processId)
    {
        $actionClass = $this->getActionManager()->getActionClass(self::INSTALL);
        $actionClass->setProcessId($processId);

        return $actionClass->getManifest();
    }

    /**
     * @param string $data
     * @return string
     * @throws Error
     */
    public function upload($data)
    {
        $this->getActionManager()->setAction(self::UPLOAD);

        return $this->getActionManager()->run($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     * @throws Error
     */
    public function install($data)
    {
        $this->getActionManager()->setAction(self::INSTALL);

        return $this->getActionManager()->run($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     * @throws Error
     */
    public function uninstall($data)
    {
        $this->getActionManager()->setAction(self::UNINSTALL);

        return $this->getActionManager()->run($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return mixed
     * @throws Error
     */
    public function delete($data)
    {
        $this->getActionManager()->setAction(self::DELETE);

        return $this->getActionManager()->run($data);
    }

    /**
     * @param string $stepName
     * @param array<string, mixed> $params
     * @return bool
     * @throws Error
     */
    public function runInstallStep($stepName, array $params = [])
    {
        return $this->runActionStep(self::INSTALL, $stepName, $params);
    }

    /**
     *
     * @param string $actionName
     * @param string $stepName
     * @param array<string, mixed> $params
     * @return bool
     * @throws Error
     */
    protected function runActionStep($actionName, $stepName, array $params = [])
    {
        $actionClass = $this->getActionManager()->getActionClass($actionName);
        $methodName = 'step' . ucfirst($stepName);

        if (!method_exists($actionClass, $methodName)) {
            if (!empty($params['id'])) {
                $actionClass->setProcessId($params['id']);
                $actionClass->throwErrorAndRemovePackage('Step "'. $stepName .'" is not found.');
            }

            throw new Error('Step "'. $stepName .'" is not found.');
        }

        $actionClass->$methodName($params); // throw an Exception on error

        return true;
    }
}
