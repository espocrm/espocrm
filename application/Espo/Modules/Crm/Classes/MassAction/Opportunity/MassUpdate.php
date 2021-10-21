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

namespace Espo\Modules\Crm\Classes\MassAction\Opportunity;

use Espo\Core\MassAction\Actions\MassUpdate as MassUpdateOriginal;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;

use Espo\Core\Utils\Metadata;

class MassUpdate implements MassAction
{
    private $massUpdateOriginal;

    private $metadata;

    public function __construct(
        MassUpdateOriginal $massUpdateOriginal,
        Metadata $metadata
    ) {
        $this->massUpdateOriginal = $massUpdateOriginal;
        $this->metadata = $metadata;
    }

    public function process(Params $params, Data $data): Result
    {
        $probability = null;

        if (
            $data->get('stage') &&
            !$data->has('probability')
        ) {
            $probability = $this->metadata
                ->get(['entityDefs', 'Opportunity', 'fields', 'stage', 'probabilityMap', $data->get('stage')]);
        }

        if ($probability !== null) {
            $data = $data->with('probability', $probability);
        }

        return $this->massUpdateOriginal->process($params, $data);
    }
}
