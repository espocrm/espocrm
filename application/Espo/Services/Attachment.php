<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Utils\File\MimeType;
use Espo\ORM\Entity;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;

use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Entities\Attachment as AttachmentEntity;

use Espo\Core\FileStorage\Storages\EspoUploadDir;
use Espo\Core\Acl\Table;

use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Job\Job\Data as JobData;

use Espo\Tools\Attachment\Jobs\MoveToStorage;

use stdClass;

/**
 * @extends Record<\Espo\Entities\Attachment>
 */
class Attachment extends Record
{
    /**
     * @var string[]
     */
    protected $notFilteringAttributeList = ['contents'];

    /**
     * @var string[]
     */
    protected $attachmentFieldTypeList = [
        self::FIELD_TYPE_FILE,
        self::FIELD_TYPE_IMAGE,
        self::FIELD_TYPE_ATTACHMENT_MULTIPLE,
    ];

    /**
     * @var string[]
     */
    protected $inlineAttachmentFieldTypeList = [
        self::FIELD_TYPE_WYSIWYG,
    ];

    /**
     * @var string[]
     */
    protected $adminOnlyHavingInlineAttachmentsEntityTypeList = [
        'TemplateManager',
    ];

    /**
     * @var string[]
     */
    protected $allowedRoleList = [
        AttachmentEntity::ROLE_ATTACHMENT,
        AttachmentEntity::ROLE_INLINE_ATTACHMENT,
    ];

    private const FIELD_TYPE_FILE = 'file';
    private const FIELD_TYPE_IMAGE = 'image';
    private const FIELD_TYPE_ATTACHMENT_MULTIPLE = 'attachmentMultiple';
    private const FIELD_TYPE_WYSIWYG = 'wysiwyg';

    /**
     * @throws Forbidden
     * @todo Check where is it used. Maybe needs to be removed.
     */
    public function upload(string $fileData): Entity
    {
        if (!$this->acl->checkScope('Attachment', Table::ACTION_CREATE)) {
            throw new Forbidden();
        }

        $contents = '';

        $arr = explode(',', $fileData);

        if (count($arr) > 1) {
            list($prefix, $contents) = $arr;

            $contents = base64_decode($contents);
        }

        $attachment = $this->entityManager->getNewEntity('Attachment');

        $attachment->set('contents', $contents);

        $this->entityManager->saveEntity($attachment);

        return $attachment;
    }

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

        $isBeingUploaded = (bool) $data->isBeingUploaded;

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
        $field = null;

        $role = AttachmentEntity::ROLE_ATTACHMENT;

        if (isset($data->parentType)) {
            $relatedEntityType = $data->parentType;

            unset($data->relatedType);
        }
        else if (isset($data->relatedType)) {
            $relatedEntityType = $data->relatedType;
        }

        if (isset($data->field)) {
            $field = $data->field;
        }

        if (isset($data->role)) {
            $role = $data->role;
        }

        if (!$relatedEntityType || !$field) {
            throw new BadRequest("Params 'field' and 'parentType' not passed along with 'file'.");
        }

        if (!in_array($role, $this->allowedRoleList)) {
            throw new BadRequest("Not supported attachment 'role'.");
        }

        $this->checkAttachmentField($relatedEntityType, $field, $role);

        $size = mb_strlen($contents, '8bit');

        if ($role === AttachmentEntity::ROLE_ATTACHMENT) {
            $maxSize = $this->metadata
                ->get(['entityDefs', $relatedEntityType, 'fields', $field, 'maxFileSize']);

            if (!$maxSize) {
                $maxSize = $this->config->get('attachmentUploadMaxSize');
            }

            if ($maxSize && $size > $maxSize * 1024 * 1024) {
                throw new Error("File size should not exceed {$maxSize}Mb.");
            }
        }

        if ($role === AttachmentEntity::ROLE_INLINE_ATTACHMENT) {
            $inlineAttachmentUploadMaxSize = $this->config->get('inlineAttachmentUploadMaxSize');

            if ($inlineAttachmentUploadMaxSize && $size > $inlineAttachmentUploadMaxSize * 1024 * 1024) {
                throw new Error("File size should not exceed {$inlineAttachmentUploadMaxSize}Mb.");
            }
        }
    }

    /**
     * @param AttachmentEntity $entity
     * @throws Forbidden
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $storage = $entity->get('storage');

        if (
            $storage &&
            !$this->metadata->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])
        ) {
            $entity->clear('storage');
        }

        if (!$entity->getRole()) {
            $entity->set('role', AttachmentEntity::ROLE_ATTACHMENT);
        }

        $role = $entity->getRole();
        $size = $entity->getSize();

        if ($role === AttachmentEntity::ROLE_ATTACHMENT) {
            $maxSize = $this->getUploadMaxSize($entity);

            if ($size && $size > $maxSize) {
                throw new Forbidden("Attachment size exceeds `attachmentUploadMaxSize`.");
            }
        }

        $this->checkAttachmentType($entity);
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        $storage = $entity->get('storage');

        if (
            $storage &&
            !$this->metadata->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])
        ) {
            $entity->clear('storage');
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkAttachmentType(AttachmentEntity $attachment): void
    {
        $field = $attachment->getTargetField();
        $entityType = $attachment->getParentType() ?? $attachment->getRelatedType();

        if (!$field || !$entityType) {
            return;
        }

        $fieldType = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

        if (
            $fieldType === self::FIELD_TYPE_IMAGE ||
            $attachment->getRole() === AttachmentEntity::ROLE_INLINE_ATTACHMENT
        ) {
            $this->checkAttachmentTypeImage($attachment);

            return;
        }

        $extension = self::getFileExtension($attachment) ?? '';

        $mimeType = $this->getMimeTypeUtil()->getMimeTypeByExtension($extension) ??
            $attachment->getType();

        /** @var string[] $accept */
        $accept = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'accept']) ?? [];

        if ($accept === []) {
            return;
        }

        $found = false;

        foreach ($accept as $token) {
            if (strtolower($token) === '.' . $extension) {
                $found = true;

                break;
            }

            if ($mimeType && MimeType::matchMimeTypeToAcceptToken($mimeType, $token)) {
                $found = true;

                break;
            }
        }

        if (!$found) {
            throw new ForbiddenSilent("Not allowed file type.");
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkAttachmentTypeImage(AttachmentEntity $attachment, ?string $filePath = null): void
    {
        $extension = self::getFileExtension($attachment) ?? '';

        $mimeType = $this->getMimeTypeUtil()->getMimeTypeByExtension($extension);

        /** @var string[] $imageTypeList */
        $imageTypeList = $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];

        if (!in_array($mimeType, $imageTypeList)) {
            throw new ForbiddenSilent("Not allowed file type.");
        }

        $setMimeType = $attachment->getType();

        if (strtolower($setMimeType ?? '') !== $mimeType) {
            throw new ForbiddenSilent("Passed type does not correspond to extension.");
        }

        $this->checkDetectedMimeType($attachment, $filePath);
    }

    private static function getFileExtension(AttachmentEntity $attachment): ?string
    {
        $name = $attachment->getName() ?? '';

        return array_slice(explode('.', $name), -1)[0] ?? null;
    }

    /**
     * @throws Forbidden
     */
    private function checkDetectedMimeType(AttachmentEntity $attachment, ?string $filePath = null): void
    {
        // ext-fileinfo required, otherwise bypass.
        if (!class_exists('\finfo') || !defined('FILEINFO_MIME_TYPE')) {
            return;
        }

        /** @var ?string $contents */
        $contents = $attachment->get('contents');

        if (!$contents && !$filePath) {
            return;
        }

        $extension = self::getFileExtension($attachment) ?? '';

        $mimeTypeList = $this->getMimeTypeUtil()->getMimeTypeListByExtension($extension);

        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        $detectedMimeType = $filePath ?
            $fileInfo->file($filePath) :
            $fileInfo->buffer($contents);

        if (!in_array($detectedMimeType, $mimeTypeList)) {
            throw new ForbiddenSilent("Detected mime type does not correspond to extension.");
        }
    }

    /**
     * @throws Forbidden
     * @throws Error
     */
    protected function checkAttachmentField(
        string $relatedEntityType,
        string $field,
        string $role = AttachmentEntity::ROLE_ATTACHMENT
    ): void {

        if (
            $this->user->isAdmin() &&
            $role === AttachmentEntity::ROLE_INLINE_ATTACHMENT &&
            in_array($relatedEntityType, $this->adminOnlyHavingInlineAttachmentsEntityTypeList)
        ) {
            return;
        }

        $fieldType = $this->metadata->get(['entityDefs', $relatedEntityType, 'fields', $field, 'type']);

        if (!$fieldType) {
            throw new Error("Field '{$field}' does not exist.");
        }

        $fieldTypeList = $role === AttachmentEntity::ROLE_INLINE_ATTACHMENT ?
            $this->inlineAttachmentFieldTypeList :
            $this->attachmentFieldTypeList;

        if (!in_array($fieldType, $fieldTypeList)) {
            throw new Error("Field type '{$fieldType}' is not allowed for {$role}.");
        }

        if ($this->user->isAdmin() && $relatedEntityType === 'Settings') {
            return;
        }

        if (
            !$this->acl->checkScope($relatedEntityType, Table::ACTION_CREATE) &&
            !$this->acl->checkScope($relatedEntityType, Table::ACTION_EDIT)
        ) {
            throw new Forbidden("No access to " . $relatedEntityType . ".");
        }

        if (in_array($field, $this->acl->getScopeForbiddenFieldList($relatedEntityType, Table::ACTION_EDIT))) {
            throw new Forbidden("No access to field '" . $field . "'.");
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function getCopiedAttachment(stdClass $data): AttachmentEntity
    {
        if (empty($data->id)) {
            throw new BadRequest();
        }

        if (empty($data->field)) {
            throw new BadRequest();
        }

        if (isset($data->parentType)) {
            $relatedEntityType = $data->parentType;
        }
        else if (isset($data->relatedType)) {
            $relatedEntityType = $data->relatedType;
        }
        else {
            throw new BadRequest();
        }

        $field = $data->field;

        $this->checkAttachmentField($relatedEntityType, $field);

        /** @var AttachmentEntity|null $attachment */
        $attachment = $this->getEntity($data->id);

        if (!$attachment) {
            throw new NotFound();
        }

        $copied = $this->getAttachmentRepository()->getCopiedAttachment($attachment);

        $attachment = $copied;

        if (isset($data->parentType)) {
            $attachment->set('parentType', $data->parentType);
        }

        if (isset($data->relatedType)) {
            $attachment->set('relatedType', $data->relatedType);
        }

        $attachment->set('field', $field);
        $attachment->set('role', AttachmentEntity::ROLE_ATTACHMENT);

        $this->getAttachmentRepository()->save($attachment);

        return $copied;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     */
    public function getAttachmentFromImageUrl(stdClass $data): AttachmentEntity
    {
        $attachment = $this->getAttachmentRepository()->getNew();

        if (empty($data->url)) {
            throw new BadRequest();
        }

        if (empty($data->field)) {
            throw new BadRequest();
        }

        if (isset($data->parentType)) {
            $relatedEntityType = $data->parentType;
        }
        else if (isset($data->relatedType)) {
            $relatedEntityType = $data->relatedType;
        }
        else {
            throw new BadRequest();
        }

        $url = $data->url;

        $field = $data->field;

        $this->checkAttachmentField($relatedEntityType, $field);

        $imageData = $this->getImageDataByUrl($url);

        if (!$imageData) {
            throw new Error('Attachment::getAttachmentFromImageUrl: Bad image data.');
        }

        $type = $imageData->type;
        $contents = $imageData->contents;

        $size = mb_strlen($contents, '8bit');

        $maxSize = $this->metadata->get(['entityDefs', $relatedEntityType, 'fields', $field, 'maxFileSize']);

        if (!$maxSize) {
            $maxSize = $this->config->get('attachmentUploadMaxSize');
        }

        if ($maxSize) {
            if ($size > $maxSize * 1024 * 1024) {
                throw new Error("File size should not exceed {$maxSize}Mb.");
            }
        }

        $attachment->set([
            'name' => $url,
            'type' => $type,
            'contents' => $contents,
            'role' => AttachmentEntity::ROLE_ATTACHMENT,
        ]);

        if (isset($data->parentType)) {
            $attachment->set('parentType', $data->parentType);
        }

        if (isset($data->relatedType)) {
            $attachment->set('relatedType', $data->relatedType);
        }

        $attachment->set('field', $field);

        $this->getAttachmentRepository()->save($attachment);

        $attachment->clear('contents');

        return $attachment;
    }

    protected function getImageDataByUrl(string $url): ?stdClass
    {
        $type = null;

        if (!function_exists('curl_init')) {
            return null;
        }

        $opts = [];

        $httpHeaders = [];
        $httpHeaders[] = 'Expect:';

        $opts[\CURLOPT_URL]  = $url;
        $opts[\CURLOPT_HTTPHEADER] = $httpHeaders;
        $opts[\CURLOPT_CONNECTTIMEOUT] = 10;
        $opts[\CURLOPT_TIMEOUT] = 10;
        $opts[\CURLOPT_HEADER] = true;
        $opts[\CURLOPT_BINARYTRANSFER] = true;
        $opts[\CURLOPT_VERBOSE] = true;
        $opts[\CURLOPT_SSL_VERIFYPEER] = true;
        $opts[\CURLOPT_SSL_VERIFYHOST] = 2;
        $opts[\CURLOPT_RETURNTRANSFER] = true;
        $opts[\CURLOPT_FOLLOWLOCATION] = true;
        $opts[\CURLOPT_MAXREDIRS] = 2;
        $opts[\CURLOPT_IPRESOLVE] = \CURL_IPRESOLVE_V4;

        $ch = curl_init();

        curl_setopt_array($ch, $opts);

        /** @var string|false $response */
        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);

            return null;
        }

        $headerSize = curl_getinfo($ch, \CURLINFO_HEADER_SIZE);

        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $headLineList = explode("\n", $header);

        foreach ($headLineList as $i => $line) {
            if ($i === 0) {
                continue;
            }

            if (strpos(strtolower($line), strtolower('Content-Type:')) === 0) {
                $part = trim(substr($line, 13));

                if ($part) {
                    $type = trim(explode(";", $part)[0]);
                }
            }
        }

        if (!$type) {
            /** @var string $extension */
            $extension = preg_replace('#\?.*#', '', pathinfo($url, \PATHINFO_EXTENSION));

            $type = $this->getMimeTypeUtil()->getMimeTypeByExtension($extension);
        }

        curl_close($ch);

        if (!$type) {
            return null;
        }

        /** @var string[] $imageTypeList */
        $imageTypeList = $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];

        if (!in_array($type, $imageTypeList)) {
            return null;
        }

        return (object) [
            'type' => $type,
            'contents' => $body,
        ];
    }

    /**
     * @throws NotFound
     * @throws Forbidden
     */
    public function getFileData(string $id): stdClass
    {
       /** @var AttachmentEntity|null $attachment */
        $attachment = $this->getEntity($id);

        if (!$attachment) {
            throw new NotFound();
        }

        return (object) [
            'name' => $attachment->get('name'),
            'type' => $attachment->get('type'),
            'stream' => $this->getAttachmentRepository()->getStream($attachment),
            'size' => $this->getAttachmentRepository()->getSize($attachment),
        ];
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function uploadChunk(string $id, string $fileData): void
    {
        if (!$this->acl->checkScope(AttachmentEntity::ENTITY_TYPE, Table::ACTION_CREATE)) {
            throw new Forbidden();
        }

        /** @var AttachmentEntity|null $attachment */
        $attachment = $this->getEntity($id);

        if (!$attachment) {
            throw new NotFound();
        }

        if (!$attachment->isBeingUploaded()) {
            throw new Forbidden("Attachment is not being-uploaded.");
        }

        if ($attachment->getStorage() !== EspoUploadDir::NAME) {
            throw new Forbidden("Attachment storage is not 'EspoUploadDir'.");
        }

        $arr = explode(';base64,', $fileData);

        if (count($arr) < 2) {
            throw new BadRequest("Bad file data.");
        }

        $contents = base64_decode($arr[1]);

        $filePath = $this->getAttachmentRepository()->getFilePath($attachment);

        $chunkSize = strlen($contents);

        $actualFileSize = 0;

        if ($this->fileManager->isFile($filePath)) {
            $actualFileSize = $this->fileManager->getSize($filePath);
        }

        $maxFileSize = $this->getUploadMaxSize($attachment);

        if ($actualFileSize + $chunkSize > $maxFileSize) {
            throw new Forbidden("Max attachment size exceeded.");
        }

        $this->fileManager->appendContents($filePath, $contents);

        if ($actualFileSize + $chunkSize > $attachment->getSize()) {
            throw new Error("File size mismatch.");
        }

        $isLastChunk = $actualFileSize + $chunkSize === $attachment->getSize();

        if (!$isLastChunk) {
            return;
        }

        try {
            $this->checkAttachmentTypeImage($attachment, $filePath);
        }
        catch (Forbidden $e) {
            $this->entityManager->removeEntity($attachment);

            throw new ForbiddenSilent($e->getMessage());
        }

        $attachment->set('isBeingUploaded', false);

        $this->entityManager->saveEntity($attachment);

        $this->createJobMoveToStorage($attachment);
    }

    private function getUploadMaxSize(AttachmentEntity $attachment): int
    {
        $field = $attachment->get('field');
        $parentType = $attachment->get('parentType') ?? $attachment->get('relatedType');

        if ($field && $parentType) {
            $maxSize = ($this->metadata
                ->get(['entityDefs', $parentType, 'fields', $field, 'maxFileSize']) ?? 0) * 1024 * 1024;

            if ($maxSize) {
                return $maxSize;
            }
        }

        return (int) $this->config->get('attachmentUploadMaxSize', 0) * 1024 * 1024;
    }

    private function createJobMoveToStorage(AttachmentEntity $attachment): void
    {
        /** @var JobSchedulerFactory $jobSchedulerFactory */
        $jobSchedulerFactory = $this->injectableFactory->create(JobSchedulerFactory::class);

        $jobSchedulerFactory->create()
            ->setClassName(MoveToStorage::class)
            ->setData(
                JobData::create()
                    ->withTargetId($attachment->getId())
            )
            ->schedule();
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->getRepository();
    }

    private function getMimeTypeUtil(): MimeType
    {
        return $this->injectableFactory->create(MimeType::class);
    }
}
