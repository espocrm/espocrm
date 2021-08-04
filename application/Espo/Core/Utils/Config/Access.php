<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Utils\Config;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\FieldUtil;

class Access
{
    public const DEFAULT_ACCESS_LEVEL = self::ACCESS_LEVEL_USER;

    public const ACCESS_LEVEL_GLOBAL = 'global';

    public const ACCESS_LEVEL_USER = 'user';

    public const ACCESS_LEVEL_ADMIN = 'admin';

    public const ACCESS_LEVEL_SUPER_ADMIN = 'superAdmin';

    public const ACCESS_LEVEL_SYSTEM = 'system';

    private $config;

    private $metadata;

    private $fieldUtil;

    public function __construct(Config $config, Metadata $metadata, FieldUtil $fieldUtil)
    {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fieldUtil = $fieldUtil;
    }

    /**
     * @return string[]
     */
    public function getAdminOnlyItemList(): array
    {
        $itemList = $this->config->get('adminItems') ?? [];

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['onlyAdmin'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values($itemList);
    }

    /**
     * @return string[]
     */
    public function getUserOnlyItemList(): array
    {
        $itemList = $this->config->get('userItems') ?? [];

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['onlyUser'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values($itemList);
    }

    /**
     * @return string[]
     */
    public function getSystemOnlyItemList(): array
    {
        $itemList = $this->config->get('systemItems') ?? [];

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['onlySystem'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values($itemList);
    }

    /**
     * @return string[]
     */
    public function getGlobalItemList(): array
    {
        $itemList = $this->config->get('globalItems', []);

        $fieldDefs = $this->metadata->get(['entityDefs', 'Settings', 'fields']);

        foreach ($fieldDefs as $field => $fieldParams) {
            if (empty($fieldParams['global'])) {
                continue;
            }

            foreach ($this->fieldUtil->getAttributeList('Settings', $field) as $attribute) {
                $itemList[] = $attribute;
            }
        }

        return array_values($itemList);
    }

    /**
     * @return string[]
     */
    public function getSuperAdminOnlyItemList(): array
    {
        $itemList = $this->config->get('superAdminItems') ?? [];

        return array_values($itemList);
    }
}
