<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
use \Espo\Core\Exceptions\BadRequest;

class Settings extends \Espo\Core\Controllers\Base
{
    protected function getConfigData()
    {
        $data = $this->getConfig()->getData($this->getUser()->isAdmin());

        $fieldDefs = $this->getMetadata()->get('entityDefs.Settings.fields');

        foreach ($fieldDefs as $field => $d) {
            if ($d['type'] == 'password') {
                unset($data[$field]);
            }
        }

        $data['jsLibs'] = $this->getMetadata()->get('app.jsLibs');

        return $data;
    }

    public function actionRead($params, $data)
    {
        return $this->getConfigData();
    }

    public function actionUpdate($params, $data, $request)
    {
        return $this->actionPatch($params, $data, $request);
    }

    public function actionPatch($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if (!$request->isPut() && !$request->isPatch()) {
            throw new BadRequest();
        }

        if (isset($data['useCache']) && $data['useCache'] != $this->getConfig()->get('useCache')) {
            $this->getContainer()->get('dataManager')->clearCache();
        }

        $this->getConfig()->setData($data, $this->getUser()->isAdmin());
        $result = $this->getConfig()->save();
        if ($result === false) {
            throw new Error('Cannot save settings');
        }

        /** Rebuild for Currency Settings */
        if (isset($data['baseCurrency']) || isset($data['currencyRates'])) {
            $this->getContainer()->get('dataManager')->rebuildDatabase(array());
        }
        /** END Rebuild for Currency Settings */

        return $this->getConfigData();
    }

    public function postActionTestLdapConnection($params, $data)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if (!isset($data['password'])) {
            $data['password'] = $this->getConfig()->get('ldapPassword');
        }

        $ldapUtils = new \Espo\Core\Utils\Authentication\LDAP\Utils();
        $options = $ldapUtils->normalizeOptions($data);

        $ldapClient = new \Espo\Core\Utils\Authentication\LDAP\Client($options);
        $ldapClient->bind(); //an exception if no connection

        return true;
    }
}
