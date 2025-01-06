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

namespace Espo\Core\MassAction\Actions;

use Espo\Tools\MassUpdate\Processor;
use Espo\Tools\MassUpdate\Data as MassUpdateData;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;

class MassUpdate implements MassAction
{
    public function __construct(private Processor $processor)
    {}

    public function process(Params $params, Data $data): Result
    {
        $massUpdateData = MassUpdateData::fromMassActionData($data);

        return $this->processor->process($params, $massUpdateData);
    }
}
