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

use \Espo\Core\Exceptions\Error;

class Notification extends \Espo\Core\Controllers\Record
{
    public static $defaultAction = 'list';

    public function getActionList($params, $data, $request, $response)
    {
        $userId = $this->getUser()->id;

        $offset = intval($request->get('offset'));
        $maxSize = intval($request->get('maxSize'));
        $after = $request->get('after');

        if (empty($maxSize)) {
            $maxSize = self::MAX_SIZE_LIMIT;
        }

        $params = array(
            'offset' => $offset,
            'maxSize' => $maxSize,
            'after' => $after
        );

        $result = $this->getService('Notification')->getList($userId, $params);

        return array(
            'total' => $result['total'],
            'list' => $result['collection']->toArray()
        );
    }

    public function actionNotReadCount()
    {
        $userId = $this->getUser()->id;
        return $this->getService('Notification')->getNotReadCount($userId);
    }

    public function postActionMarkAllRead($params, $data, $request)
    {
        $userId = $this->getUser()->id;
        return $this->getService('Notification')->markAllRead($userId);
    }

    public function beforeExport()
    {
        throw new Error();
    }

    public function beforeMassUpdate()
    {
        throw new Error();
    }

    public function beforeCreateLink()
    {
        throw new Error();
    }

    public function beforeRemoveLink()
    {
        throw new Error();
    }

    public function beforeMerge()
    {
        throw new Error();
    }
}
