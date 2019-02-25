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

class Attachment extends \Espo\Core\Controllers\Record
{
    public function actionList($params, $data, $request)
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
        return parent::actionList($params, $data, $request);
    }

    public function postActionGetAttachmentFromImageUrl($params, $data)
    {
        if (empty($data->url)) throw new BadRequest();
        if (empty($data->field)) throw new BadRequest('postActionGetAttachmentFromImageUrl: No field specified');

        return $this->getRecordService()->getAttachmentFromImageUrl($data)->getValueMap();
    }

    public function postActionGetCopiedAttachment($params, $data)
    {
        if (empty($data->id)) throw new BadRequest();
        if (empty($data->field)) throw new BadRequest('postActionGetCopiedAttachment copy: No field specified');

        return $this->getRecordService()->getCopiedAttachment($data)->getValueMap();
    }
}
