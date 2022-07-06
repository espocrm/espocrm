<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\{
    Utils\Metadata,
    Utils\DataCache,
    Utils\FieldUtil,
    Utils\Config,
};

use stdClass;

/**
 * Lists of restricted fields can be obtained from here. Restricted fields
 * are specified in metadata > entityAcl.
 */
class GlobalRestriction
{
    public const TYPE_FORBIDDEN = 'forbidden';

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_ONLY_ADMIN = 'onlyAdmin';

    public const TYPE_READ_ONLY = 'readOnly';

    public const TYPE_NON_ADMIN_READ_ONLY = 'nonAdminReadOnly';

    /**
     * @var string[]
     */
    protected $fieldTypeList = [
        'forbidden', // totally forbidden
        'internal', // reading forbidden, writing allowed
        'onlyAdmin', // forbidden for non admin users
        'readOnly', // read-only for all users
        'nonAdminReadOnly' // read-only for non-admin users
    ];

    /**
     * @var string[]
     */
    protected $linkTypeList = [
        'forbidden', // totally forbidden
        'internal', // reading forbidden, writing allowed
        'onlyAdmin', // forbidden for non admin users
        'readOnly', // read-only for all users
        'nonAdminReadOnly' // read-only for non-admin users
    ];

    private ?stdClass $data = null;

    protected string $cacheKey = 'entityAcl';

    private Metadata $metadata;

    private DataCache $dataCache;

    private FieldUtil $fieldUtil;

    public function __construct(
        Metadata $metadata,
        DataCache $dataCache,
        FieldUtil $fieldUtil,
        Config $config
    ) {
        $this->metadata = $metadata;
        $this->dataCache = $dataCache;
        $this->fieldUtil = $fieldUtil;

        $useCache = $config->get('useCache');

        if ($useCache && $this->dataCache->has($this->cacheKey)) {
            /** @var stdClass */
            $cachedData = $this->dataCache->get($this->cacheKey);

            $this->data = $cachedData;

            return;
        }

        if (!$this->data) {
            $this->buildData();
        }

        if ($useCache) {
            $this->storeCacheFile();
        }
    }

    protected function storeCacheFile(): void
    {
        assert($this->data !== null);

        $this->dataCache->store($this->cacheKey, $this->data);
    }

    protected function buildData(): void
    {
        /** @var string[] */
        $scopeList = array_keys($this->metadata->get(['entityDefs'], []));

        $data = (object) [];

        foreach ($scopeList as $scope) {
            /** @var string[] */
            $fieldList = array_keys($this->metadata->get(['entityDefs', $scope, 'fields'], []));
            /** @var string[] */
            $linkList = array_keys($this->metadata->get(['entityDefs', $scope, 'links'], []));

            $isNotEmpty = false;

            $scopeData = (object) [
                'fields' => (object) [],
                'attributes' => (object) [],
                'links' => (object) [],
            ];

            foreach ($this->fieldTypeList as $type) {
                $resultFieldList = [];
                $resultAttributeList = [];

                foreach ($fieldList as $field) {
                    if ($this->metadata->get(['entityAcl', $scope, 'fields', $field, $type])) {
                        $isNotEmpty = true;

                        $resultFieldList[] = $field;

                        $fieldAttributeList = $this->fieldUtil->getAttributeList($scope, $field);

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
                    if ($this->metadata->get(['entityAcl', $scope, 'links', $link, $type])) {
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

    /**
     * @return string[]
     */
    public function getScopeRestrictedFieldList(string $scope, string $type): array
    {
        assert($this->data !== null);

        if (!property_exists($this->data, $scope)) {
            return [];
        }

        if (!property_exists($this->data->$scope, 'fields')) {
            return [];
        }

        if (!property_exists($this->data->$scope->fields, $type)) {
            return [];
        }

        return $this->data->$scope->fields->$type;
    }

    /**
     * @return string[]
     */
    public function getScopeRestrictedAttributeList(string $scope, string $type): array
    {
        assert($this->data !== null);

        if (!property_exists($this->data, $scope)) {
            return [];
        }

        if (!property_exists($this->data->$scope, 'attributes')) {
            return [];
        }

        if (!property_exists($this->data->$scope->attributes, $type)) {
            return [];
        }

        return $this->data->$scope->attributes->$type;
    }

    /**
     * @return string[]
     */
    public function getScopeRestrictedLinkList(string $scope, string $type): array
    {
        assert($this->data !== null);

        if (!property_exists($this->data, $scope)) {
            return [];
        }

        if (!property_exists($this->data->$scope, 'links')) {
            return [];
        }

        if (!property_exists($this->data->$scope->links, $type)) {
            return [];
        }

        return $this->data->$scope->links->$type;
    }
}
