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

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;

class EmailFolder extends Record
{
    protected $systemFolderList = ['inbox', 'important', 'sent'];

    protected $systemFolderEndList = ['drafts', 'trash'];

    protected function init()
    {
        parent::init();
        $this->addDependency('language');
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if (!$this->getUser()->isAdmin() || !$entity->get('assignedUserId')) {
            $entity->set('assignedUserId', $this->getUser()->id);
        }
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }

    public function moveUp($id)
    {
        $entity = $this->getEntityManager()->getEntity('EmailFolder', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        $previousEntity = $this->getRepository()->where(array(
            'order<' => $currentIndex,
            'assignedUserId' => $entity->get('assignedUserId')
        ))->order('order', true)->findOne();

        if (!$previousEntity) return;

        $entity->set('order', $previousEntity->get('order'));
        $previousEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($previousEntity);
    }

    public function moveDown($id)
    {
        $entity = $this->getEntityManager()->getEntity('EmailFolder', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        $nextEntity = $this->getRepository()->where(array(
            'order>' => $currentIndex,
            'assignedUserId' => $entity->get('assignedUserId')
        ))->order('order', false)->findOne();

        if (!$nextEntity) return;

        $entity->set('order', $nextEntity->get('order'));
        $nextEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($nextEntity);
    }

    public function listAll()
    {
        $limit = $this->getConfig()->get('emailFolderMaxCount', 100);

        $folderList = $this->getRepository()->where(array(
            'assignedUserId' => $this->getUser()->id
        ))->order('order')->limit(0, $limit)->find();

        $list = new \Espo\ORM\EntityCollection();

        foreach ($this->systemFolderList as $name) {
            $folder = $this->getEntityManager()->getEntity('EmailFolder');
            $folder->set('name', $this->getInjection('language')->translate($name, 'presetFilters', 'Email'));
            $folder->id = $name;
            $list[] = $folder;
        }


        foreach ($folderList as $folder) {
            $list[] = $folder;
        }

        foreach ($this->systemFolderEndList as $name) {
            $folder = $this->getEntityManager()->getEntity('EmailFolder');
            $folder->set('name', $this->getInjection('language')->translate($name, 'presetFilters', 'Email'));
            $folder->id = $name;
            $list[] = $folder;
        }

        $finalList = [];
        foreach ($list as $item) {
            $attributes = $item->getValues();
            $attributes['childCollection'] = [];
            $finalList[] = $attributes;
        }

        return array(
            'list' => $finalList
        );
    }
}

