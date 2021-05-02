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

namespace Espo\Core\Action\Actions;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Action\Actions\Merge\Merger,
    Action\Action,
    Action\Params,
    Action\Data,
    Acl,
    Acl\Table,
};

class Merge implements Action
{
    private $acl;

    private $merger;

    public function __construct(Acl $acl, Merger $merger)
    {
        $this->acl = $acl;
        $this->merger = $merger;
    }

    public function process(Params $params, Data $data): void
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->checkScope($entityType, Table::ACTION_EDIT)) {
            throw new Forbidden();
        }

        $sourceIdList = $data->get('sourceIdList');
        $attributes = $data->get('attributes');

        if (!is_array($sourceIdList)) {
            throw new BadRequest("No 'sourceIdList'.");
        }

        if (!is_object($attributes)) {
            throw new BadRequest("No 'attributes'.");
        }

        $this->merger->process($params, $sourceIdList, $attributes);
    }
}
