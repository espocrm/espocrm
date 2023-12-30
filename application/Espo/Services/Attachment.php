<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Services;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Error;

use Espo\Entities\Attachment as AttachmentEntity;

use Espo\Tools\Attachment\AccessChecker;
use Espo\Tools\Attachment\Checker;

use Espo\Tools\Attachment\DetailsObtainer;
use Espo\Tools\Attachment\FieldData;
use stdClass;

/**
 * @extends Record<AttachmentEntity>
 */
class Attachment extends Record
{
    /** @var string[] */
    protected $notFilteringAttributeList = ['contents'];

    protected function afterCreateEntity(Entity $entity, $data)
    {
        if (!empty($data->file)) {
            $entity->clear('contents');
        }
    }

    public function filterUpdateInput(stdClass $data): void
    {
        parent::filterUpdateInput($data);

        unset($data->parentId);
        unset($data->parentType);
        unset($data->relatedId);
        unset($data->relatedType);
        unset($data->isBeingUploaded);
        unset($data->storage);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     */
    public function filterCreateInput(stdClass $data): void
    {
        parent::filterCreateInput($data);

        unset($data->parentId);
        unset($data->relatedId);

        $isBeingUploaded = (bool) ($data->isBeingUploaded ?? false);

        $contents = '';

        if (!$isBeingUploaded) {
            if (!property_exists($data, 'file')) {
                throw new BadRequest("No file contents.");
            }

            if (!is_string($data->file)) {
                throw new BadRequest("Non-string file contents.");
            }

            $arr = explode(',', $data->file);

            if (count($arr) > 1) {
                $contents = $arr[1];
            }

            $contents = base64_decode($contents);
        }

        $data->contents = $contents;

        $relatedEntityType = null;

        if (isset($data->parentType)) {
            $relatedEntityType = $data->parentType;

            unset($data->relatedType);
        }
        else if (isset($data->relatedType)) {
            $relatedEntityType = $data->relatedType;
        }

        $field = $data->field ?? null;
        $role = $data->role ?? AttachmentEntity::ROLE_ATTACHMENT;

        if (!$relatedEntityType || !$field) {
            throw new BadRequest("No `field` and `parentType`.");
        }

        $fieldData = new FieldData(
            $field,
            $data->parentType ?? null,
            $data->relatedType ?? null
        );

        $this->getAccessChecker()->check($fieldData, $role);

        $size = mb_strlen($contents, '8bit');

        $dummy = $this->entityManager->getRepositoryByClass(AttachmentEntity::class)->getNew();

        $dummy->set([
            'parentType' => $data->parentType ?? null,
            'relatedType' => $data->relatedType ?? null,
            'field' => $data->field ?? null,
            'role' => $role,
        ]);

        $maxSize = $this->getDetailsObtainer()->getUploadMaxSize($dummy);

        if ($maxSize && $size > $maxSize * 1024 * 1024) {
            throw new Error("File size should not exceed {$maxSize} Mb.");
        }
    }

    /**
     * @param AttachmentEntity $entity
     * @throws Forbidden
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $storage = $entity->getStorage();

        $availableStorageList = $this->config->get('attachmentAvailableStorageList') ?? [];

        if (
            $storage &&
            (
                !in_array($storage, $availableStorageList) ||
                !$this->metadata->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])
            )
        ) {
            $entity->clear('storage');
        }

        if (!$entity->getRole()) {
            $entity->set('role', AttachmentEntity::ROLE_ATTACHMENT);
        }

        $size = $entity->getSize();

        $maxSize = $this->getDetailsObtainer()->getUploadMaxSize($entity);

        // Checking not actual file size but a set value.
        if ($size && $size > $maxSize) {
            throw new Forbidden("Attachment size exceeds `attachmentUploadMaxSize`.");
        }

        $this->getChecker()->checkType($entity);
    }

    private function getChecker(): Checker
    {
        return $this->injectableFactory->create(Checker::class);
    }

    private function getDetailsObtainer(): DetailsObtainer
    {
        return $this->injectableFactory->create(DetailsObtainer::class);
    }

    private function getAccessChecker(): AccessChecker
    {
        return $this->injectableFactory->create(AccessChecker::class);
    }
}
