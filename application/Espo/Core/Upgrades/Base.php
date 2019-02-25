<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Upgrades;

use Espo\Core\Utils\Util,
    Espo\Core\Utils\Json,
    Espo\Core\Exceptions\Error;

abstract class Base
{
    private $container;

    protected $name = null;

    protected $params = array();

    const UPLOAD = 'upload';

    const INSTALL = 'install';

    const UNINSTALL = 'uninstall';

    const DELETE = 'delete';

    public function __construct($container)
    {
        $this->container = $container;

        $this->actionManager = new ActionManager($this->name, $container, $this->params);
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getActionManager()
    {
        return $this->actionManager;
    }

    public function getManifest()
    {
        return $this->getActionManager()->getManifest();
    }

    public function upload($data)
    {
        $this->getActionManager()->setAction(self::UPLOAD);

        return $this->getActionManager()->run($data);
    }

    public function install($processId)
    {
        $this->getActionManager()->setAction(self::INSTALL);

        return $this->getActionManager()->run($processId);
    }

    public function uninstall($processId)
    {
        $this->getActionManager()->setAction(self::UNINSTALL);

        return $this->getActionManager()->run($processId);
    }

    public function delete($processId)
    {
        $this->getActionManager()->setAction(self::DELETE);

        return $this->getActionManager()->run($processId);
    }
}
