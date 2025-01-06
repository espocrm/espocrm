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

namespace Espo\Core\Action\Actions;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Action\Action;
use Espo\Core\Action\Actions\Merge\Merger;
use Espo\Core\Action\Data;
use Espo\Core\Action\Params;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

use stdClass;

class Merge implements Action
{
    public function __construct(private Acl $acl, private Merger $merger)
    {}

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

        if (!$attributes instanceof stdClass) {
            throw new BadRequest("No 'attributes'.");
        }

        $this->merger->process($params, $sourceIdList, $attributes);
    }
}
