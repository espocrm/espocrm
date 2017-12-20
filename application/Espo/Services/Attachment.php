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

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;

class Attachment extends Record
{
    protected $notFilteringAttributeList = ['contents'];

    protected $attachmentFieldTypeList = ['file', 'image', 'attachmentMultiple'];

    protected $inlineAttachmentFieldTypeList = ['text', 'wysiwyg'];

    public function upload($fileData)
    {
        if (!$this->getAcl()->checkScope('Attachment', 'create')) {
            throw new Forbidden();
        }

        $arr = explode(',', $fileData);
        if (count($arr) > 1) {
            list($prefix, $contents) = $arr;
            $contents = base64_decode($contents);
        } else {
            $contents = '';
        }

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('contents', $contents);
        $this->getEntityManager()->saveEntity($attachment);

        return $attachment;
    }

    public function createEntity($data)
    {
        if (!empty($data->file)) {
            $arr = explode(',', $data->file);
            $contents = '';
            if (count($arr) > 1) {
                $contents = $arr[1];
            }

            $contents = base64_decode($contents);
            $data->contents = $contents;

            $relatedEntityType = null;
            $field = null;
            $role = 'Attachment';
            if (isset($data->parentType)) {
                $relatedEntityType = $data->parentType;
            } else if (isset($data->relatedType)) {
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

            $fieldType = $this->getMetadata()->get(['entityDefs', $relatedEntityType, 'fields', $field, 'type']);
            if (!$fieldType) {
                throw new Error("Field '{$field}' does not exist.");
            }

            if (
                !$this->getAcl()->checkScope($relatedEntityType, 'create')
                &&
                !$this->getAcl()->checkScope($relatedEntityType, 'edit')
            ) {
                throw new Forbidden("No access to " . $relatedEntityType . ".");
            }

            if (in_array($field, $this->getAcl()->getScopeForbiddenFieldList($relatedEntityType, 'edit'))) {
                throw new Forbidden("No access to field '" . $field . "'.");
            }

            $size = mb_strlen($contents, '8bit');

            if ($role === 'Attachment') {
                if (!in_array($fieldType, $this->attachmentFieldTypeList)) {
                    throw new Error("Field type '{$fieldType}' is not allowed for attachment.");
                }
                $maxSize = $this->getMetadata()->get(['entityDefs', $relatedEntityType, 'fields', $field, 'maxFileSize']);
                if (!$maxSize) {
                    $maxSize = $this->getConfig()->get('attachmentUploadMaxSize');
                }
                if ($maxSize) {
                    if ($size > $maxSize * 1024 * 1024) {
                        throw new Error("File size should not exceed {$maxSize}Mb.");
                    }
                }

            } else if ($role === 'Inline Attachment') {
                if (!in_array($fieldType, $this->inlineAttachmentFieldTypeList)) {
                    throw new Error("Field '{$field}' is not allowed to have inline attachment.");
                }
                $inlineAttachmentUploadMaxSize = $this->getConfig()->get('inlineAttachmentUploadMaxSize');
                if ($inlineAttachmentUploadMaxSize) {
                    if ($size > $inlineAttachmentUploadMaxSize * 1024 * 1024) {
                        throw new Error("File size should not exceed {$inlineAttachmentUploadMaxSize}Mb.");
                    }
                }
            } else {
                throw new BadRequest("Not supported attachment role.");
            }
        }

        $entity = parent::createEntity($data);

        if (!empty($data->file)) {
            $entity->clear('contents');
        }

        return $entity;
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $storage = $entity->get('storage');
        if ($storage && !$this->getMetadata()->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])) {
            $entity->clear('storage');
        }
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        $storage = $entity->get('storage');
        if ($storage && !$this->getMetadata()->get(['app', 'fileStorage', 'implementationClassNameMap', $storage])) {
            $entity->clear('storage');
        }
    }
}

