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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Settings extends \Espo\Core\Services\Base
{
    protected function init()
    {
        parent::init();
        $this->addDependency('fieldManagerUtil');
        $this->addDependency('metadata');
        $this->addDependency('acl');
        $this->addDependency('container');
    }

    protected function getFieldManagerUtil()
    {
        return $this->getInjection('fieldManagerUtil');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getContainer()
    {
        return $this->getInjection('container');
    }

    public function getConfigData()
    {
        $data = $this->getConfig()->getAllData();

        $ignoreItemList = [];

        foreach ($this->getSystemOnlyItemList() as $item) {
            $ignoreItemList[] = $item;
        }

        if (!$this->getUser()->isAdmin() || $this->getUser()->isSystem()) {
            foreach ($this->getAdminOnlyItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        if ($this->getUser()->isSystem()) {
            foreach ($this->getUserOnlyItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        if ($this->getConfig()->get('restrictedMode') && !$this->getUser()->isSuperAdmin()) {
            foreach ($this->getConfig()->getSuperAdminOnlySystemItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        if ($portal = $this->getContainer()->get('portal')) {
            $this->getContainer()->get('entityManager')->getRepository('Portal')->loadUrlField($portal);
            $data->siteUrl = $portal->get('url');
        }

        foreach ($ignoreItemList as $item) {
            unset($data->$item);
        }

        if ($this->getUser()->isSystem()) {
            $globalItemList = $this->getGlobalItemList();
            foreach (get_object_vars($data) as $item => $value) {
                if (!in_array($item, $globalItemList)) {
                    unset($data->$item);
                }
            }
        }

        $fieldDefs = $this->getMetadata()->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if ($fieldParams['type'] === 'password') {
                unset($data->$field);
            }
        }

        $this->filterData($data);

        return $data;
    }

    public function setConfigData($data)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        $ignoreItemList = [];

        foreach ($this->getSystemOnlyItemList() as $item) {
            $ignoreItemList[] = $item;
        }

        if ($this->getConfig()->get('restrictedMode') && !$this->getUser()->isSuperAdmin()) {
            foreach ($this->getConfig()->getSuperAdminOnlyItemList() as $item) {
                $ignoreItemList[] = $item;
            }
            foreach ($this->getConfig()->getSuperAdminOnlySystemItemList() as $item) {
                $ignoreItemList[] = $item;
            }
        }

        foreach ($ignoreItemList as $item) {
            unset($data->$item);
        }

        if (
            (isset($data->useCache) && $data->useCache !== $this->getConfig()->get('useCache'))
            ||
            (isset($data->aclStrictMode) && $data->aclStrictMode !== $this->getConfig()->get('aclStrictMode'))
        ) {
            $this->getContainer()->get('dataManager')->clearCache();
        }

        $this->getConfig()->setData($data);

        $result = $this->getConfig()->save();

        if ($result === false) {
            throw new Error('Cannot save settings');
        }

        if (isset($data->defaultCurrency) || isset($data->baseCurrency) || isset($data->currencyRates)) {
            $this->getContainer()->get('dataManager')->rebuildDatabase([]);
        }

        return $result;
    }

    protected function filterData($data)
    {
        if (empty($data->useWebSocket)) {
            unset($data->webSocketUrl);
        }

        if ($this->getUser()->isSystem()) return;

        if ($this->getUser()->isAdmin()) return;

        if (
            !$this->getAcl()->checkScope('Email', 'create')
            ||
            !$this->getConfig()->get('outboundEmailIsShared')
        ) {
            unset($data->outboundEmailFromAddress);
            unset($data->outboundEmailFromName);
            unset($data->outboundEmailBccAddress);
        }
    }

    public function getAdminOnlyItemList()
    {
        $itemList = $this->getConfig()->getAdminOnlyItemList();

        $fieldDefs = $this->getMetadata()->get(['entityDefs', 'Settings', 'fields']);
        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['onlyAdmin'])) {
                foreach ($this->getFieldManagerUtil()->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    public function getUserOnlyItemList()
    {
        $itemList = $this->getConfig()->getUserOnlyItemList();

        $fieldDefs = $this->getMetadata()->get(['entityDefs', 'Settings', 'fields']);
        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['onlyUser'])) {
                foreach ($this->getFieldManagerUtil()->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    public function getSystemOnlyItemList()
    {
        $itemList = $this->getConfig()->getSystemOnlyItemList();

        $fieldDefs = $this->getMetadata()->get(['entityDefs', 'Settings', 'fields']);
        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['onlySystem'])) {
                foreach ($this->getFieldManagerUtil()->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }

    public function getGlobalItemList()
    {
        $itemList = $this->getConfig()->get('globalItems', []);

        $fieldDefs = $this->getMetadata()->get(['entityDefs', 'Settings', 'fields']);
        foreach ($fieldDefs as $field => $fieldParams) {
            if (!empty($fieldParams['global'])) {
                foreach ($this->getFieldManagerUtil()->getAttributeList('Settings', $field) as $attribute) {
                    $itemList[] = $attribute;
                }
            }
        }

        return $itemList;
    }
}
