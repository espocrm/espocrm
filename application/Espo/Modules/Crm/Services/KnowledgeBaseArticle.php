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

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;

class KnowledgeBaseArticle extends \Espo\Services\Record
{
    protected $readOnlyAttributeList = ['order'];

    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'fileStorageManager'
        ]);
    }

    protected function getFileStorageManager()
    {
        return $this->getInjection('fileStorageManager');
    }

    public function getCopiedAttachments($id, $parentType = null, $parentId = null)
    {
        $ids = array();
        $names = new \stdClass();

        if (empty($id)) {
            throw new BadRequest();
        }
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->checkEntity($entity, 'read')) {
            throw new Forbidden();
        }
        $entity->loadLinkMultipleField('attachments');
        $attachmentsIds = $entity->get('attachmentsIds');

        foreach ($attachmentsIds as $attachmentId) {
            $source = $this->getEntityManager()->getEntity('Attachment', $attachmentId);
            if ($source) {
                $attachment = $this->getEntityManager()->getEntity('Attachment');
                $attachment->set('role', 'Attachment');
                $attachment->set('type', $source->get('type'));
                $attachment->set('size', $source->get('size'));
                $attachment->set('global', $source->get('global'));
                $attachment->set('name', $source->get('name'));
                $attachment->set('sourceId', $source->getSourceId());
                $attachment->set('storage', $source->get('storage'));

                if (!empty($parentType) && !empty($parentId)) {
                    $attachment->set('parentType', $parentType);
                    $attachment->set('parentId', $parentId);
                }

                if ($this->getFileStorageManager()->isFile($source)) {
                    $this->getEntityManager()->saveEntity($attachment);
                    $contents = $this->getFileStorageManager()->getContents($source);
                    $this->getFileStorageManager()->putContents($attachment, $contents);
                    $ids[] = $attachment->id;
                    $names->{$attachment->id} = $attachment->get('name');
                }
            }
        }

        return array(
            'ids' => $ids,
            'names' => $names
        );
    }

    public function moveUp($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order<' => $currentIndex
        );

        $selectManager->applyOrder('order', true, $selectParams);

        $previousEntity = $this->getRepository()->findOne($selectParams);

        if (!$previousEntity) return;

        $entity->set('order', $previousEntity->get('order'));
        $previousEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($previousEntity);
    }

    public function moveDown($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order>' => $currentIndex
        );

        $selectManager->applyOrder('order', false, $selectParams);

        $nextEntity = $this->getRepository()->findOne($selectParams);

        if (!$nextEntity) return;

        $entity->set('order', $nextEntity->get('order'));
        $nextEntity->set('order', $currentIndex);

        $this->getEntityManager()->saveEntity($entity);
        $this->getEntityManager()->saveEntity($nextEntity);
    }

    public function moveToTop($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order<' => $currentIndex
        );

        $selectManager->applyOrder('order', false, $selectParams);

        $previousEntity = $this->getRepository()->findOne($selectParams);

        if (!$previousEntity) return;

        $entity->set('order', $previousEntity->get('order') - 1);

        $this->getEntityManager()->saveEntity($entity);
    }

    public function moveToBottom($id, $where = null)
    {
        $entity = $this->getEntityManager()->getEntity('KnowledgeBaseArticle', $id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) throw new Error();

        if (!$where) {
            $where = array();
        }

        $params = array(
            'where' => $where
        );

        $selectManager = $this->getSelectManager();
        $selectParams = $selectManager->buildSelectParams($params, true, true);

        $selectParams['whereClause'][] = array(
            'order>' => $currentIndex
        );

        $selectManager->applyOrder('order', true, $selectParams);

        $nextEntity = $this->getRepository()->findOne($selectParams);

        if (!$nextEntity) return;

        $entity->set('order', $nextEntity->get('order') + 1);

        $this->getEntityManager()->saveEntity($entity);
    }
}
