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

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;

class DetailsObtainer
{
    private Metadata $metadata;
    private Config $config;

    public function __construct(
        Metadata $metadata,
        Config $config
    ) {
        $this->metadata = $metadata;
        $this->config = $config;
    }

    /**
     * Get a file extension.
     */
    public static function getFileExtension(Attachment $attachment): ?string
    {
        $name = $attachment->getName() ?? '';

        return array_slice(explode('.', $name), -1)[0] ?? null;
    }

    /**
     * Get an upload max size allowed for an attachment (depending on a field it's related to).
     *
     * @return int A size in bytes.
     */
    public function getUploadMaxSize(Attachment $attachment): int
    {
        if ($attachment->getRole() === Attachment::ROLE_INLINE_ATTACHMENT) {
            return $this->config->get('inlineAttachmentUploadMaxSize') * 1024 * 1024;
        }

        $field = $attachment->getTargetField();
        $parentType = $attachment->getParentType() ?? $attachment->getRelatedType();

        if ($field && $parentType) {
            $maxSize = ($this->metadata
                ->get(['entityDefs', $parentType, 'fields', $field, 'maxFileSize']) ?? 0) * 1024 * 1024;

            if ($maxSize) {
                return $maxSize;
            }
        }

        return (int) $this->config->get('attachmentUploadMaxSize', 0) * 1024 * 1024;
    }

    /**
     * Get a field type (an attachment if related to another record through the field).
     */
    public function getFieldType(Attachment $attachment): ?string
    {
        $field = $attachment->getTargetField();
        $entityType = $attachment->getParentType() ?? $attachment->getRelatedType();

        if (!$field || !$entityType) {
            return null;
        }

        return $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);
    }
}
