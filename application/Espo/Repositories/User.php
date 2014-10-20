<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Repositories;

use Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;

class User extends \Espo\Core\ORM\Repositories\RDB
{
    protected function beforeSave(Entity $entity)
    {
        if ($entity->isNew()) {
            $userName = $entity->get('userName');
            if (empty($userName)) {
                throw new Error();
            }
            
            $user = $this->where(array(
                'userName' => $userName
            ))->findOne();
            
            if ($user) {
                throw new Error();
            }
        } else {
            if ($entity->isFieldChanged('userName')) {
                $userName = $entity->get('userName');
                if (empty($userName)) {
                    throw new Error();
                }
            
                $user = $this->where(array(
                    'userName' => $userName,
                    'id!=' => $entity->id
                ))->findOne();
                if ($user) {
                    throw new Error();
                }
            }
        }
    }
}

