<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Tools\KnowledgeBase;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Entities\Attachment;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Tools\Attachment\AccessChecker as AttachmentAccessChecker;
use Espo\Tools\Attachment\FieldData;

class Service
{
    public function __construct(
        private EntityManager $entityManager,
        private AttachmentAccessChecker $attachmentAccessChecker,
        private ServiceContainer $serviceContainer,
        private SelectBuilderFactory $selectBuilderFactory,
        private Acl $acl,
    ) {}

    /**
     * Copy article attachments for re-using (e.g. in an email).
     *
     * @return Attachment[]
     * @throws NotFound
     * @throws Forbidden
     */
    public function copyAttachments(string $id, FieldData $fieldData): array
    {
        /** @var ?KnowledgeBaseArticle $entity */
        $entity = $this->serviceContainer
            ->get(KnowledgeBaseArticle::ENTITY_TYPE)
            ->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->attachmentAccessChecker->check($fieldData);

        $list = [];

        foreach ($entity->getAttachmentIdList() as $attachmentId) {
            $attachment = $this->copyAttachment($attachmentId, $fieldData);

            if ($attachment) {
                $list[] = $attachment;
            }
        }

        return $list;
    }

    private function copyAttachment(string $attachmentId, FieldData $fieldData): ?Attachment
    {
        /** @var ?Attachment $attachment */
        $attachment = $this->entityManager
            ->getRDBRepositoryByClass(Attachment::class)
            ->getById($attachmentId);

        if (!$attachment) {
            return null;
        }

        $copied = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

        $copied->set('parentType', $fieldData->getParentType());
        $copied->set('relatedType', $fieldData->getRelatedType());
        $copied->setTargetField($fieldData->getField());
        $copied->setRole(Attachment::ROLE_ATTACHMENT);

        $this->getAttachmentRepository()->save($copied);

        return $copied;
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepositoryByClass(Attachment::class);
    }

    /**
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws BadRequest
     */
    public function moveUp(string $id, SearchParams $params): void
    {
        /** @var ?KnowledgeBaseArticle $entity */
        $entity = $this->entityManager->getEntityById(KnowledgeBaseArticle::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->getOrder();

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from(KnowledgeBaseArticle::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->withSearchParams($params)
            ->buildQueryBuilder()
            ->where([
                'order<' => $currentIndex,
            ])
            ->order([
                ['order', 'DESC'],
            ])
            ->build();

        /** @var ?KnowledgeBaseArticle $previousEntity */
        $previousEntity = $this->entityManager
            ->getRDBRepositoryByClass(KnowledgeBaseArticle::class)
            ->clone($query)
            ->findOne();

        if (!$previousEntity) {
            return;
        }

        $entity->set('order', $previousEntity->getOrder());

        $previousEntity->set('order', $currentIndex);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($previousEntity);
    }

    /**
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws BadRequest
     */
    public function moveDown(string $id, SearchParams $params): void
    {
        /** @var ?KnowledgeBaseArticle $entity */
        $entity = $this->entityManager->getEntityById(KnowledgeBaseArticle::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->getOrder();

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from(KnowledgeBaseArticle::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->withSearchParams($params)
            ->buildQueryBuilder()
            ->where([
                'order>' => $currentIndex,
            ])
            ->order([
                ['order', 'ASC'],
            ])
            ->build();

        /** @var ?KnowledgeBaseArticle $nextEntity */
        $nextEntity = $this->entityManager
            ->getRDBRepositoryByClass(KnowledgeBaseArticle::class)
            ->clone($query)
            ->findOne();

        if (!$nextEntity) {
            return;
        }

        $entity->set('order', $nextEntity->getOrder());

        $nextEntity->set('order', $currentIndex);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($nextEntity);
    }

    /**
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws BadRequest
     */
    public function moveToTop(string $id, SearchParams $params): void
    {
        /** @var ?KnowledgeBaseArticle $entity */
        $entity = $this->entityManager->getEntityById(KnowledgeBaseArticle::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->getOrder();

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from(KnowledgeBaseArticle::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->withSearchParams($params)
            ->buildQueryBuilder()
            ->where([
                'order<' => $currentIndex,
            ])
            ->order([
                ['order', 'ASC'],
            ])
            ->build();

        /** @var ?KnowledgeBaseArticle $previousEntity */
        $previousEntity = $this->entityManager
            ->getRDBRepositoryByClass(KnowledgeBaseArticle::class)
            ->clone($query)
            ->findOne();

        if (!$previousEntity) {
            return;
        }

        $entity->set('order', $previousEntity->getOrder() - 1);

        $this->entityManager->saveEntity($entity);
    }

    /**
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws BadRequest
     */
    public function moveToBottom(string $id, SearchParams $params): void
    {
        /** @var ?KnowledgeBaseArticle $entity */
        $entity = $this->entityManager->getEntityById(KnowledgeBaseArticle::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->getOrder();

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from(KnowledgeBaseArticle::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->withSearchParams($params)
            ->buildQueryBuilder()
            ->where([
                'order>' => $currentIndex,
            ])
            ->order([
                ['order', 'DESC'],
            ])
            ->build();

        /** @var ?KnowledgeBaseArticle $nextEntity */
        $nextEntity = $this->entityManager
            ->getRDBRepositoryByClass(KnowledgeBaseArticle::class)
            ->clone($query)
            ->findOne();

        if (!$nextEntity) {
            return;
        }

        $entity->set('order', $nextEntity->getOrder() + 1);

        $this->entityManager->saveEntity($entity);
    }
}
