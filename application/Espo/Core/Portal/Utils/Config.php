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

namespace Espo\Core\Portal\Utils;

use Espo\Core\Utils\Config as BaseConfig;

use RuntimeException;
use stdClass;

class Config extends BaseConfig
{
    private bool $portalParamsSet = false;

    /**
     * @var array<string, mixed>
     */
    private $portalData = [];

    /**
     * @var string[]
     */
    private $portalParamList = [
        'applicationName',
        'companyLogoId',
        'tabList',
        'quickCreateList',
        'dashboardLayout',
        'dashletsOptions',
        'theme',
        'themeParams',
        'language',
        'timeZone',
        'dateFormat',
        'timeFormat',
        'weekStart',
        'defaultCurrency',
    ];

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        if (array_key_exists($name, $this->portalData)) {
            return $this->portalData[$name];
        }

        return parent::get($name, $default);
    }

    public function has(string $name): bool
    {
        if (array_key_exists($name, $this->portalData)) {
            return true;
        }

        return parent::has($name);
    }

    public function getAllNonInternalData(): stdClass
    {
        $data = parent::getAllNonInternalData();

        foreach ($this->portalData as $k => $v) {
            $data->$k = $v;
        }

        return $data;
    }

    /**
     * Override parameters for a portal. Can be called only once.
     *
     * @param array<string, mixed> $data
     */
    public function setPortalParameters(array $data = []): void
    {
        if ($this->portalParamsSet) {
            throw new RuntimeException("Can't set portal params second time.");
        }

        $this->portalParamsSet = true;

        if (empty($data['applicationName'])) {
            unset($data['applicationName']);
        }

        if (empty($data['language'])) {
            unset($data['language']);
        }

        if (empty($data['theme'])) {
            unset($data['theme']);
        }

        if (empty($data['timeZone'])) {
            unset($data['timeZone']);
        }

        if (empty($data['dateFormat'])) {
            unset($data['dateFormat']);
        }

        if (empty($data['timeFormat'])) {
            unset($data['timeFormat']);
        }

        if (empty($data['defaultCurrency'])) {
            unset($data['defaultCurrency']);
        }

        if (isset($data['weekStart']) && $data['weekStart'] === -1) {
            unset($data['weekStart']);
        }

        if (array_key_exists('weekStart', $data) && is_null($data['weekStart'])) {
            unset($data['weekStart']);
        }

        if ($this->get('webSocketInPortalDisabled')) {
            $this->portalData['useWebSocket'] = false;
        }

        foreach ($data as $attribute => $value) {
            if (!in_array($attribute, $this->portalParamList)) {
                continue;
            }

            $this->portalData[$attribute] = $value;
        }
    }
}
