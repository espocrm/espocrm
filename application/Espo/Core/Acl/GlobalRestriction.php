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

namespace Espo\Core\Acl;

use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;

use stdClass;

/**
 * Lists of restricted fields can be obtained from here. Restricted fields
 * are specified in metadata > entityAcl.
 */
class GlobalRestriction
{
    /** Totally forbidden. */
    public const TYPE_FORBIDDEN = 'forbidden';
    /** Reading forbidden, writing allowed. */
    public const TYPE_INTERNAL = 'internal';
    /** Forbidden for non-admin users. */
    public const TYPE_ONLY_ADMIN = 'onlyAdmin';
    /** Read-only for all users. */
    public const TYPE_READ_ONLY = 'readOnly';
    /** Read-only for non-admin users. */
    public const TYPE_NON_ADMIN_READ_ONLY = 'nonAdminReadOnly';

    /**
     * @var array<int, self::TYPE_*>
     */
    private $fieldTypeList = [
        self::TYPE_FORBIDDEN,
        self::TYPE_INTERNAL,
        self::TYPE_ONLY_ADMIN,
        self::TYPE_READ_ONLY,
        self::TYPE_NON_ADMIN_READ_ONLY,
    ];

    /**
     * @var array<int, self::TYPE_*>
     */
    private $linkTypeList = [
        self::TYPE_FORBIDDEN,
        self::TYPE_INTERNAL,
        self::TYPE_ONLY_ADMIN,
        self::TYPE_READ_ONLY,
        self::TYPE_NON_ADMIN_READ_ONLY,
    ];

    /**
     * Types that should also be taken from entityDefs.
     * @var array<int, self::TYPE_*>
     */
    private array $entityDefsTypeList = [
        self::TYPE_READ_ONLY,
    ];

    private ?stdClass $data = null;

    private string $cacheKey = 'entityAcl';

    public function __construct(
        private Metadata $metadata,
        private DataCache $dataCache,
        private FieldUtil $fieldUtil,
        SystemConfig $systemConfig,
    ) {

        $useCache = $systemConfig->useCache();

        if ($useCache && $this->dataCache->has($this->cacheKey)) {
            /** @var stdClass $cachedData */
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

    private function storeCacheFile(): void
    {
        assert($this->data !== null);

        $this->dataCache->store($this->cacheKey, $this->data);
    }

    private function buildData(): void
    {
        /** @var string[] $scopeList */
        $scopeList = array_keys($this->metadata->get(['entityDefs']) ?? []);

        $data = (object) [];

        foreach ($scopeList as $scope) {
            /** @var string[] $fieldList */
            $fieldList = array_keys($this->metadata->get(['entityDefs', $scope, 'fields']) ?? []);
            /** @var string[] $linkList */
            $linkList = array_keys($this->metadata->get(['entityDefs', $scope, 'links']) ?? []);

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
                    $value = $this->metadata->get(['entityAcl', $scope, 'fields', $field, $type]);

                    if (!$value && in_array($type, $this->entityDefsTypeList)) {
                        $value = $this->metadata->get(['entityDefs', $scope, 'fields', $field, $type]);
                    }

                    if (!$value) {
                        continue;
                    }

                    $isNotEmpty = true;

                    $resultFieldList[] = $field;

                    $fieldAttributeList = $this->fieldUtil->getAttributeList($scope, $field);

                    foreach ($fieldAttributeList as $attribute) {
                        $resultAttributeList[] = $attribute;
                    }
                }

                $scopeData->fields->$type = $resultFieldList;
                $scopeData->attributes->$type = $resultAttributeList;
            }

            foreach ($this->linkTypeList as $type) {
                $resultLinkList = [];

                foreach ($linkList as $link) {
                    $value = $this->metadata->get(['entityAcl', $scope, 'links', $link, $type]);

                    if (!$value && in_array($type, $this->entityDefsTypeList)) {
                        $value = $this->metadata->get(['entityDefs', $scope, 'links', $link, $type]);
                    }

                    if (!$value) {
                        continue;
                    }

                    $isNotEmpty = true;

                    $resultLinkList[] = $link;
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
     * @param self::TYPE_* $type
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
     * @param self::TYPE_* $type
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
     * @param self::TYPE_* $type
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
