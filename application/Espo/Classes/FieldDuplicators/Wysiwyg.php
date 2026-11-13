<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Classes\FieldDuplicators;

use Espo\Core\Acl;
use Espo\Core\Record\Duplicator\FieldDuplicator;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Entities\Attachment;

use stdClass;

/**
 * @noinspection PhpUnused
 */
class Wysiwyg implements FieldDuplicator
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata,
    ) {}

    public function duplicate(Entity $entity, string $field): stdClass
    {
        $valueMap = (object) [];

        $contents = $entity->get($field);

        if (!$contents) {
            return $valueMap;
        }

        $matches = [];

        $matchResult = preg_match_all("/\?entryPoint=attachment&amp;id=([^&=\"']+)/", $contents, $matches);

        if (
            !$matchResult ||
            empty($matches[1]) ||
            !is_array($matches[1])
        ) {
            return $valueMap;
        }

        $attachmentIdList = $matches[1];

        /** @var Attachment[] $attachmentList */
        $attachmentList = [];

        foreach ($attachmentIdList as $id) {
            $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

            if (!$attachment) {
                continue;
            }

            if (!$this->acl->checkEntityRead($attachment)) {
                continue;
            }

            if (!in_array($attachment->getType(), $this->getAllowedImageFileTypeList())) {
                continue;
            }

            $attachmentList[] = $attachment;
        }

        if (!count($attachmentList)) {
            return $valueMap;
        }

        /** @var AttachmentRepository $attachmentRepository */
        $attachmentRepository = $this->entityManager->getRepository(Attachment::ENTITY_TYPE);

        foreach ($attachmentList as $attachment) {
            $copiedAttachment = $attachmentRepository
                ->getCopiedAttachment($attachment, Attachment::ROLE_INLINE_ATTACHMENT);

            $copiedAttachment->setTargetField($field);

            $copiedAttachment->setMultiple([
                'relatedId' => null,
                'relatedType' => $entity->getEntityType(),
            ]);

            $this->entityManager->saveEntity($copiedAttachment);

            $contents = str_replace(
                '?entryPoint=attachment&amp;id=' . $attachment->getId(),
                '?entryPoint=attachment&amp;id=' . $copiedAttachment->getId(),
                $contents
            );
        }

        $valueMap->$field = $contents;

        return $valueMap;
    }

    /**
     * @return string[]
     */
    private function getAllowedImageFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];
    }
}
