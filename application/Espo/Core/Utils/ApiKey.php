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

namespace Espo\Core\Utils;

class ApiKey
{
    private $config;

    public function __construct(\Espo\Core\Utils\Config $config)
    {
        $this->config = $config;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    public static function hash($secretKey, $string = '')
    {
        return hash_hmac('sha256', $string, $secretKey, true);
    }

    public function getSecretKeyForUserId($id)
    {
        $apiSecretKeys = $this->getConfig()->get('apiSecretKeys');
        if (!$apiSecretKeys) return;
        if (!is_object($apiSecretKeys)) return;
        if (!isset($apiSecretKeys->$id)) return;
        return $apiSecretKeys->$id;
    }

    public function storeSecretKeyForUserId($id, $secretKey)
    {
        $apiSecretKeys = $this->getConfig()->get('apiSecretKeys');
        if (!is_object($apiSecretKeys)) {
            $apiSecretKeys = (object)[];
        }
        $apiSecretKeys->$id = $secretKey;

        $this->getConfig()->set('apiSecretKeys', $apiSecretKeys);
        $this->getConfig()->save();
    }

    public function removeSecretKeyForUserId($id)
    {
        $apiSecretKeys = $this->getConfig()->get('apiSecretKeys');
        if (!is_object($apiSecretKeys)) {
            $apiSecretKeys = (object)[];
        }
        unset($apiSecretKeys->$id);

        $this->getConfig()->set('apiSecretKeys', $apiSecretKeys);
        $this->getConfig()->save();
    }
}
