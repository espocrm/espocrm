<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/ 

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;

class Notification extends \Espo\Core\Controllers\Base
{
    public static $defaultAction = 'list';

    public function actionList($params, $data, $request)
    {
        $scope = $params['scope'];
        $id = $params['id'];
        
        $userId = $this->getUser()->id;
        
        $offset = intval($request->get('offset'));
        $maxSize = intval($request->get('maxSize'));        
        
        $params = array(
            'offset' => $offset,
            'maxSize' => $maxSize,
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
    
    public function actionMarkAllRead($params, $data, $request)
    {
        $userId = $this->getUser()->id;
        return $this->getService('Notification')->markAllRead($userId);
    }
}

