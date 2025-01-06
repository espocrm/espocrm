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

namespace Espo\Modules\Crm\Tools\Document;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\Attachment;
use Espo\Modules\Crm\Entities\Document;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Tools\Attachment\AccessChecker as AttachmentAccessChecker;
use Espo\Tools\Attachment\FieldData;

class Service
{
    private EntityManager $entityManager;
    private AttachmentAccessChecker $attachmentAccessChecker;
    private ServiceContainer $serviceContainer;

    public function __construct(
        EntityManager $entityManager,
        AttachmentAccessChecker $attachmentAccessChecker,
        ServiceContainer $serviceContainer
    ) {
        $this->entityManager = $entityManager;
        $this->attachmentAccessChecker = $attachmentAccessChecker;
        $this->serviceContainer = $serviceContainer;
    }

    /**
     * Copy an attachment for re-using (e.g. in an email).
     *
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     */
    public function copyAttachment(string $id, FieldData $fieldData): Attachment
    {
        /** @var ?Document $entity */
        $entity = $this->serviceContainer
            ->getByClass(Document::class)
            ->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->attachmentAccessChecker->check($fieldData);

        $attachmentId = $entity->getFileId();

        if (!$attachmentId) {
            throw new Error("No file.");
        }

        $attachment = $this->copyAttachmentById($attachmentId, $fieldData);

        if (!$attachment) {
            throw new Error("No file.");
        }

        return $attachment;
    }

    private function copyAttachmentById(string $attachmentId, FieldData $fieldData): ?Attachment
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
}
