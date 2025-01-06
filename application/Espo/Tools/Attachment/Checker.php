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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Utils\File\MimeType;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\Core\ORM\Type\FieldType;

class Checker
{
    public function __construct(
        private Metadata $metadata,
        private MimeType $mimeType,
        private DetailsObtainer $detailsObtainer
    ) {}

    /**
     * Check a mine-type for allowance.
     *
     * @throws Forbidden
     */
    public function checkType(Attachment $attachment): void
    {
        $field = $attachment->getTargetField();
        $entityType = $attachment->getParentType() ?? $attachment->getRelatedType();

        if (!$field || !$entityType) {
            return;
        }

        if (
            $this->detailsObtainer->getFieldType($attachment) === FieldType::IMAGE ||
            $attachment->getRole() === Attachment::ROLE_INLINE_ATTACHMENT
        ) {
            $this->checkTypeImage($attachment);

            return;
        }

        $extension = strtolower(DetailsObtainer::getFileExtension($attachment) ?? '');

        $mimeType = $this->mimeType->getMimeTypeByExtension($extension) ??
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
     * Check a mime-time for allowance for an image.
     *
     * @throws Forbidden
     */
    public function checkTypeImage(Attachment $attachment, ?string $filePath = null): void
    {
        $extension = DetailsObtainer::getFileExtension($attachment) ?? '';

        $mimeType = $this->mimeType->getMimeTypeByExtension($extension);

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

    /**
     * @throws Forbidden
     */
    private function checkDetectedMimeType(Attachment $attachment, ?string $filePath = null): void
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

        $extension = DetailsObtainer::getFileExtension($attachment) ?? '';

        $mimeTypeList = $this->mimeType->getMimeTypeListByExtension($extension);

        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        $detectedMimeType = $filePath ?
            $fileInfo->file($filePath) :
            $fileInfo->buffer($contents);

        if (!in_array($detectedMimeType, $mimeTypeList)) {
            throw new ForbiddenSilent("Detected mime type does not correspond to extension.");
        }
    }
}
