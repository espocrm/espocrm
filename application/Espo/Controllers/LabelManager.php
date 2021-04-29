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

namespace Espo\Controllers;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Api\Request,
    DataManager,
};

use Espo\{
    Tools\LabelManager\LabelManager as LabelManagerTool,
    Entities\User,
};

class LabelManager
{
    private $user;

    private $dataManager;

    private $labelManagerTool;

    public function __construct(User $user, DataManager $dataManager, LabelManagerTool $labelManagerTool)
    {
        $this->user = $user;
        $this->dataManager = $dataManager;
        $this->labelManagerTool = $labelManagerTool;

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionGetScopeList()
    {
        return $this->labelManagerTool->getScopeList();
    }

    public function postActionGetScopeData(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->scope) || empty($data->language)) {
            throw new BadRequest();
        }

        return $this->labelManagerTool->getScopeData($data->language, $data->scope);
    }

    public function postActionSaveLabels(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->scope) || empty($data->language) || !isset($data->labels)) {
            throw new BadRequest();
        }

        $labels = get_object_vars($data->labels);

        $returnData = $this->labelManagerTool->saveLabels($data->language, $data->scope, $labels);

        $this->dataManager->clearCache();

        return $returnData;
    }
}
