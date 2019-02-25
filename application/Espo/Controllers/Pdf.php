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
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class Pdf extends \Espo\Core\Controllers\Base
{
    public function postActionMassPrint($params, $data)
    {
        if (empty($data->idList) || !is_array($data->idList)) {
            throw new BadRequest();
        }
        if (empty($data->entityType)) {
            throw new BadRequest();
        }
        if (empty($data->templateId)) {
            throw new BadRequest();
        }
        if (!$this->getAcl()->checkScope('Template')) {
            throw new Forbidden();
        }
        if (!$this->getAcl()->checkScope($data->entityType)) {
            throw new Forbidden();
        }

        return [
            'id' => $this->getServiceFactory()->create('Pdf')->massGenerate($data->entityType, $data->idList, $data->templateId, true)
        ];
    }
}
