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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Forbidden;

class AuthToken extends \Espo\Core\Controllers\Record
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionUpdate($params, $data, $request)
    {
        $dataAr = get_object_vars($data);

        if (
            is_object($data)
            &&
            isset($data->isActive)
            &&
            $data->isActive === false
            &&
            count(array_keys($dataAr)) === 1
        ) {
            return parent::actionUpdate($params, $data, $request);
        }
        throw new Forbidden();
    }

    public function actionMassUpdate($params, $data, $request)
    {
        if (empty($data->attributes)) {
            throw new BadRequest();
        }

        $attributes = $data->attributes;

        if (
            is_object($attributes)
            &&
            isset($attributes->isActive)
            &&
            $attributes->isActive === false
            &&
            count(array_keys(get_object_vars($attributes))) === 1
        ) {
            return parent::actionMassUpdate($params, $data, $request);
        }
        throw new Forbidden();
    }

    public function beforeCreate()
    {
        throw new Forbidden();
    }

    public function beforeCreateLink()
    {
        throw new Forbidden();
    }

    public function beforeRemoveLink()
    {
        throw new Forbidden();
    }
}
