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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Extension extends \Espo\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionUpload($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new Forbidden();
        }

        $manager = new \Espo\Core\ExtensionManager($this->getContainer());

        $id = $manager->upload($data);
        $manifest = $manager->getManifest();

        return array(
            'id' => $id,
            'version' => $manifest['version'],
            'name' => $manifest['name'],
            'description' => $manifest['description'],
        );
    }

    public function actionInstall($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new Forbidden();
        }
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->isSuperAdmin()) {
                throw new Forbidden();
            }
        }

        $manager = new \Espo\Core\ExtensionManager($this->getContainer());

        $manager->install(get_object_vars($data));

        return true;
    }

    public function actionUninstall($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new Forbidden();
        }
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->isSuperAdmin()) {
                throw new Forbidden();
            }
        }

        $manager = new \Espo\Core\ExtensionManager($this->getContainer());
        $manager->uninstall(get_object_vars($data));
        return true;
    }


    public function actionDelete($params, $data, $request)
    {
        if (!$request->isDelete()) {
            throw BadRequest();
        }
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->isSuperAdmin()) {
                throw new Forbidden();
            }
        }
        $manager = new \Espo\Core\ExtensionManager($this->getContainer());
        $manager->delete($params);
        return true;
    }

    public function beforeCreate()
    {
        throw new Forbidden();
    }

    public function beforeUpdate()
    {
        throw new Forbidden();
    }

    public function beforePatch()
    {
        throw new Forbidden();
    }

    public function beforeListLinked()
    {
        throw new Forbidden();
    }

    public function beforeMassUpdate()
    {
        throw new Forbidden();
    }

    public function beforeMassDelete()
    {
        throw new Forbidden();
    }

    public function beforeCreateLink()
    {
        throw new Forbidden();
    }

    public function beforeRemoveLink()
    {
        throw new Forbidden();
    }
}
