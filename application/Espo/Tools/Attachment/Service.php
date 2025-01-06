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

namespace Espo\Tools\Attachment;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\Attachment;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;

class Service
{
    private ServiceContainer $recordServiceContainer;
    private EntityManager $entityManager;
    private AccessChecker $accessChecker;

    public function __construct(
        ServiceContainer $recordServiceContainer,
        EntityManager $entityManager,
        AccessChecker $accessChecker
    ) {
        $this->recordServiceContainer = $recordServiceContainer;
        $this->entityManager = $entityManager;
        $this->accessChecker = $accessChecker;
    }

    /**
     * Get file data (for downloading).
     *
     * @throws NotFound
     * @throws Forbidden
     */
    public function getFileData(string $id): FileData
    {
        /** @var ?Attachment $attachment */
        $attachment = $this->recordServiceContainer
            ->get(Attachment::ENTITY_TYPE)
            ->getEntity($id);

        if (!$attachment) {
            throw new NotFound();
        }

        return new FileData(
            $attachment->getName(),
            $attachment->getType(),
            $this->getAttachmentRepository()->getStream($attachment),
            $this->getAttachmentRepository()->getSize($attachment)
        );
    }

    /**
     * Copy an attachment record (to reuse the same file w/o copying it in the storage).
     *
     * @throws Forbidden
     * @throws NotFound
     */
    public function copy(string $id, FieldData $data): Attachment
    {
        $this->accessChecker->check($data);

        /** @var ?Attachment $attachment */
        $attachment = $this->recordServiceContainer
            ->get(Attachment::ENTITY_TYPE)
            ->getEntity($id);

        if (!$attachment) {
            throw new NotFound();
        }

        $copied = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

        $copied->set('parentType', $data->getParentType());
        $copied->set('relatedType', $data->getRelatedType());
        $copied->setTargetField($data->getField());
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
