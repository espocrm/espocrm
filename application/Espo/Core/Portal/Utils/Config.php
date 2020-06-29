<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Portal\Utils;

use Espo\Core\Utils\Config as BaseConfig;

class Config extends BaseConfig
{
    /**
     * Override parameters for a portal.
     */
    public function setPortalParameters(array $data = [])
    {
        if (empty($data['language'])) unset($data['language']);
        if (empty($data['theme'])) unset($data['theme']);
        if (empty($data['timeZone'])) unset($data['timeZone']);
        if (empty($data['dateFormat'])) unset($data['dateFormat']);
        if (empty($data['timeFormat'])) unset($data['timeFormat']);
        if (empty($data['defaultCurrency'])) unset($data['defaultCurrency']);
        if (isset($data['weekStart']) && $data['weekStart'] === -1) unset($data['weekStart']);
        if (array_key_exists('weekStart', $data) && is_null($data['weekStart'])) unset($data['weekStart']);

        if ($this->get('webSocketInPortalDisabled')) {
            $this->set('useWebSocket', false, true);
        }

        foreach ($data as $attribute => $value) {
            $this->set($attribute, $value, true);
        }
    }

    /**
     * Save is disabled.
     */
    public function save()
    {
    }
}
