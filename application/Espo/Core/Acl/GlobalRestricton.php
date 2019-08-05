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

namespace Espo\Core\Acl;

class GlobalRestricton
{
    protected $fieldTypeList = [
        'forbidden', // totally forbidden
        'internal', // reading forbidden, writing allowed
        'onlyAdmin', // forbidden for non admin users
        'readOnly', // read-only for all users
        'nonAdminReadOnly' // read-only for non-admin users
    ];

    protected $linkTypeList = [
        'forbidden', // totally forbidden
        'internal', // reading forbidden, writing allowed
        'onlyAdmin', // forbidden for non admin users
        'readOnly', // read-only for all users
        'nonAdminReadOnly' // read-only for non-admin users
    ];

    protected $cacheFilePath = 'data/cache/application/entityAcl.php';

    private $metadata;

    private $fileManager;

    private $fieldManagerUtil;

    private $data;

    public function __construct(
        \Espo\Core\Utils\Metadata $metadata,
        \Espo\Core\Utils\File\Manager $fileManager,
        \Espo\Core\Utils\FieldManagerUtil $fieldManagerUtil,
        bool $useCache = true
    )
    {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->fieldManagerUtil = $fieldManagerUtil;

        $isFromCache = false;

        if ($useCache) {
            if (file_exists($this->cacheFilePath)) {
                $this->data = include($this->cacheFilePath);
                $isFromCache = true;

                if (!($this->data instanceof \StdClass)) {
                    $GLOBALS['log']->error("ACL GlobalRestricton: Bad data fetched from cache.");
                    $this->data = null;
                }
            }
        }

        if (!$this->data) {
            $this->buildData();
        }

        if ($useCache) {
            if (!$isFromCache) {
                $this->storeCacheFile();
            }
        }
    }

    protected function storeCacheFile()
    {
        $this->getFileManager()->putPhpContents($this->cacheFilePath, $this->data, true);
    }

    protected function buildData()
    {
        $scopeList = array_keys($this->getMetadata()->get(['entityDefs'], []));

        $data = (object) [];

        foreach ($scopeList as $scope) {
            $fieldList = array_keys($this->getMetadata()->get(['entityDefs', $scope, 'fields'], []));
            $linkList = array_keys($this->getMetadata()->get(['entityDefs', $scope, 'links'], []));

            $isNotEmpty = false;

            $scopeData = (object) [
                'fields' => (object) [],
                'attributes' => (object) [],
                'links' => (object) []
            ];

            foreach ($this->fieldTypeList as $type) {
                $resultFieldList = [];
                $resultAttributeList = [];

                foreach ($fieldList as $field) {
                    if ($this->getMetadata()->get(['entityAcl', $scope, 'fields', $field, $type])) {
                        $isNotEmpty = true;
                        $resultFieldList[] = $field;
                        $fieldAttributeList = $this->getFieldManagerUtil()->getAttributeList($scope, $field);
                        foreach ($fieldAttributeList as $attribute) {
                            $resultAttributeList[] = $attribute;
                        }
                    }
                }

                $scopeData->fields->$type = $resultFieldList;
                $scopeData->attributes->$type = $resultAttributeList;
            }
            foreach ($this->linkTypeList as $type) {
                $resultLinkList = [];
                foreach ($linkList as $link) {
                    if ($this->getMetadata()->get(['entityAcl', $scope, 'links', $link, $type])) {
                        $isNotEmpty = true;
                        $resultLinkList[] = $link;
                    }
                }
                $scopeData->links->$type = $resultLinkList;
            }

            if ($isNotEmpty) {
                $data->$scope = $scopeData;
            }
        }

        $this->data = $data;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getFieldManagerUtil()
    {
        return $this->fieldManagerUtil;
    }

    public function getScopeRestrictedFieldList($scope, $type)
    {
        if (!property_exists($this->data, $scope)) return [];
        if (!property_exists($this->data->$scope, 'fields')) return [];
        if (!property_exists($this->data->$scope->fields, $type)) return [];

        return $this->data->$scope->fields->$type;
    }

    public function getScopeRestrictedAttributeList($scope, $type)
    {
        if (!property_exists($this->data, $scope)) return [];
        if (!property_exists($this->data->$scope, 'attributes')) return [];
        if (!property_exists($this->data->$scope->attributes, $type)) return [];

        return $this->data->$scope->attributes->$type;
    }

    public function getScopeRestrictedLinkList($scope, $type)
    {
        if (!property_exists($this->data, $scope)) return [];
        if (!property_exists($this->data->$scope, 'links')) return [];
        if (!property_exists($this->data->$scope->links, $type)) return [];

        return $this->data->$scope->links->$type;
    }
}
