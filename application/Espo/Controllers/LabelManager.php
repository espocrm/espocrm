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

use Espo\Core\Utils as Utils;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class LabelManager extends \Espo\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionGetScopeList($params)
    {
        $labelManager = $this->getContainer()->get('injectableFactory')->createByClassName('\\Espo\\Core\\Utils\\LabelManager');

        return $labelManager->getScopeList();
    }

    public function postActionGetScopeData($params, $data, $request)
    {
        if (empty($data->scope) || empty($data->language)) {
            throw new BadRequest();
        }
        $labelManager = $this->getContainer()->get('injectableFactory')->createByClassName('\\Espo\\Core\\Utils\\LabelManager');
        return $labelManager->getScopeData($data->language, $data->scope);
    }

    public function postActionSaveLabels($params, $data)
    {
        if (empty($data->scope) || empty($data->language) || !isset($data->labels)) {
            throw new BadRequest();
        }

        $labels = get_object_vars($data->labels);

        $labelManager = $this->getContainer()->get('injectableFactory')->createByClassName('\\Espo\\Core\\Utils\\LabelManager');
        $returnData = $labelManager->saveLabels($data->language, $data->scope, $labels);

        $this->getContainer()->get('dataManager')->clearCache();

        return $returnData;
    }
}
