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

namespace Espo\AclPortal;

use \Espo\Entities\User as EntityUser;
use \Espo\ORM\Entity;

class Attachment extends \Espo\Core\AclPortal\Base
{
    public function checkEntityRead(EntityUser $user, Entity $entity, $data)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($entity->get('parentType') === 'Settings') {
            return true;
        }

        $parent = null;
        $hasParent = false;
        if ($entity->get('parentId') && $entity->get('parentType')) {
            $hasParent = true;
            $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
        } else if ($entity->get('relatedId') && $entity->get('relatedType')) {
            $hasParent = true;
            $parent = $this->getEntityManager()->getEntity($entity->get('relatedType'), $entity->get('relatedId'));
        }

        if ($hasParent) {
            if ($parent) {
                if ($parent->getEntityType() === 'Note') {
                    if ($parent->get('parentId') && $parent->get('parentType')) {
                        $parentOfParent = $this->getEntityManager()->getEntity($parent->get('parentType'), $parent->get('parentId'));
                        if ($parentOfParent && $this->getAclManager()->checkEntity($user, $parentOfParent)) {
                            return true;
                        }
                    } else {
                        return true;
                    }
                } else {
                    if ($this->getAclManager()->checkEntity($user, $parent)) {
                        return true;
                    }
                }
            }
        } else {
            return true;
        }

        if ($this->checkEntity($user, $entity, $data, 'read')) {
            return true;
        }

        return false;
    }

    public function checkIsOwner(EntityUser $user, Entity $entity)
    {
        if ($user->id === $entity->get('createdById')) {
            return true;
        }
        return false;
    }
}

