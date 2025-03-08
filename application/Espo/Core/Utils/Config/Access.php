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

namespace Espo\Core\Utils\Config;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\Settings;
use Espo\ORM\Defs\Params\FieldParam;

class Access
{
    /** Logged-in users can read. Admin can write. */
    public const LEVEL_DEFAULT = 'default';
    /** No one can read/write. */
    public const LEVEL_SYSTEM = 'system';
    /** No one can read, admin can write. */
    public const LEVEL_INTERNAL = 'internal';
    /** Only super-admin can read/write. */
    public const LEVEL_SUPER_ADMIN = 'superAdmin';
    /** Only admin can read/write. */
    public const LEVEL_ADMIN = 'admin';
    /** Even not logged-in can read. Admin can write. */
    public const LEVEL_GLOBAL = 'global';

    public function __construct(
        private Config $config,
        private Metadata $metadata,
        private FieldUtil $fieldUtil
    ) {}

    /**
     * Get read-only parameters.
     *
     * @return string[]
     */
    public function getReadOnlyParamList(): array
    {
        $itemList = [];

        $fieldDefs = $this->metadata->get(['entityDefs', Settings::ENTITY_TYPE, 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams[FieldParam::READ_ONLY])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList(Settings::ENTITY_TYPE, $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        $params = $this->metadata->get(['app', 'config', 'params']) ?? [];

        foreach ($params as $name => $item) {
            if ($item['readOnly'] ?? false) {
                $itemList[] = $name;
            }
        }

        return array_values(array_unique($itemList));
    }

    /**
     * @return string[]
     */
    public function getAdminParamList(): array
    {
        $itemList = $this->config->get('adminItems') ?? [];

        $fieldDefs = $this->metadata->get(['entityDefs', Settings::ENTITY_TYPE, 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['onlyAdmin'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList(Settings::ENTITY_TYPE, $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values(
            array_merge(
                $itemList,
                $this->getParamListByLevel(self::LEVEL_ADMIN)
            )
        );
    }

    /**
     * @return string[]
     */
    public function getInternalParamList(): array
    {
        return $this->getParamListByLevel(self::LEVEL_INTERNAL);
    }

    /**
     * @return string[]
     */
    public function getSystemParamList(): array
    {
        $itemList = $this->config->get('systemItems') ?? [];

        $fieldDefs = $this->metadata->get(['entityDefs', Settings::ENTITY_TYPE, 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['onlySystem'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList(Settings::ENTITY_TYPE, $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values(
            array_merge(
                $itemList,
                $this->getParamListByLevel(self::LEVEL_SYSTEM)
            )
        );
    }

    /**
     * @return string[]
     */
    public function getGlobalParamList(): array
    {
        $itemList = $this->config->get('globalItems', []);

        $fieldDefs = $this->metadata->get(['entityDefs', Settings::ENTITY_TYPE, 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['global'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList(Settings::ENTITY_TYPE, $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values(
            array_merge(
                $itemList,
                $this->getParamListByLevel(self::LEVEL_GLOBAL)
            )
        );
    }

    /**
     * @return string[]
     */
    public function getSuperAdminParamList(): array
    {
        return array_values(
            array_merge(
                $this->config->get('superAdminItems') ?? [],
                $this->getParamListByLevel(self::LEVEL_SUPER_ADMIN)
            )
        );
    }

    /**
     * @param self::LEVEL_* $level
     * @return string[]
     */
    private function getParamListByLevel(string $level): array
    {
        $itemList = [];

        $params = $this->metadata->get(['app', 'config', 'params']) ?? [];

        foreach ($params as $name => $item) {
            $levelItem = $item['level'] ?? null;

            if ($levelItem !== $level) {
                continue;
            }

            $itemList[] = $name;
        }

        return $itemList;
    }
}
