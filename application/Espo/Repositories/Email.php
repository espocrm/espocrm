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

class Email extends \Espo\Core\ORM\Repositories\RDB
{
    protected function prepareAddressess(Entity $entity, $type)
    {
        $eaRepositoty = $this->getEntityManager()->getRepository('EmailAddress');
        
        $address = $entity->get($type);
        $ids = array();
        if (!empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $arr = array_map(function ($e) {
                return trim($e);
            }, explode(';', $address));
            
            $ids = $eaRepositoty->getIds($arr);
        }
        $entity->set($type . 'EmailAddressesIds', $ids);
    }
    
    protected function beforeSave(Entity $entity)
    {
        $eaRepositoty = $this->getEntityManager()->getRepository('EmailAddress');
        
        $from = trim($entity->get('from'));
        if (!empty($from)) {
            $ids = $eaRepositoty->getIds(array($from));
            if (!empty($ids)) {
                $entity->set('fromEmailAddressId', $ids[0]);
            }
        } else {
            $entity->set('fromEmailAddressId', null);
        }
        
        $this->prepareAddressess($entity, 'to');
        $this->prepareAddressess($entity, 'cc');
        $this->prepareAddressess($entity, 'bcc');
        
        parent::beforeSave($entity);
        
        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if (!empty($parentId) || !empty($parentType)) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if (!empty($parent)) {
                if ($parent->getEntityName() == 'Account') {
                    $accountId = $parent->id;
                } else if ($parent->has('accountId')) {
                    $accountId = $parent->get('accountId');
                }
                if (!empty($accountId)) {
                    $entity->set('accountId', $accountId);
                }
            }
        } else {
            // TODO find account by from address
        }
    }
    
    protected function beforeRemove(Entity $entity)
    {
        parent::beforeRemove($entity);
        $attachments = $entity->get('attachments');
        foreach ($attachments as $attachment) {
            $this->getEntityManager()->removeEntity($attachment);
        }
    }
}

