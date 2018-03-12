<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Notificators;

use \Espo\Core\Interfaces\Injectable;

use \Espo\ORM\Entity;

class Base implements Injectable
{
    protected $dependencies = array(
        'user',
        'entityManager',
    );

    protected $injections = array();

    public static $order = 9;

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
    }

    public function getDependencyList()
    {
        return $this->dependencies;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }

    protected function getUser()
    {
        return $this->injections['user'];
    }

    public function process(Entity $entity)
    {
        if (!$entity->get('assignedUserId')) return;
        if (!$entity->isAttributeChanged('assignedUserId')) return;

        $assignedUserId = $entity->get('assignedUserId');

        if ($entity->hasAttribute('createdById') && $entity->hasAttribute('modifiedById')) {
            if ($entity->isNew()) {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('createdById');
            } else {
                $isNotSelfAssignment = $assignedUserId !== $entity->get('modifiedById');
            }
        } else {
            $isNotSelfAssignment = $assignedUserId !== $this->getUser()->id;
        }
        if (!$isNotSelfAssignment) return;

        $notification = $this->getEntityManager()->getEntity('Notification');
        $notification->set(array(
            'type' => 'Assign',
            'userId' => $assignedUserId,
            'data' => array(
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->id,
                'entityName' => $entity->get('name'),
                'isNew' => $entity->isNew(),
                'userId' => $this->getUser()->id,
                'userName' => $this->getUser()->get('name')
            )
        ));
        $this->getEntityManager()->saveEntity($notification);
    }
}
