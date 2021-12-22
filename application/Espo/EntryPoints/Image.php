<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\EntryPoints;

use Espo\Repositories\Attachment as AttachmentRepository;

use Espo\Core\{
    Exceptions\NotFound,
    Exceptions\NotFoundSilent,
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Exceptions\Error,
    EntryPoint\EntryPoint,
    Acl,
    ORM\EntityManager,
    Api\Request,
    Api\Response,
    FileStorage\Manager as FileStorageManager,
    Utils\File\Manager as FileManager,
    Utils\Config,
    Utils\Metadata,
};

use Espo\Entities\Attachment;

class Image implements EntryPoint
{
    protected $allowedRelatedTypeList = null;

    protected $allowedFieldList = null;

    /** @var FileStorageManager */
    protected $fileStorageManager;

    /** @var Acl */
    protected $acl;

    /** @var EntityManager */
    protected $entityManager;

    /** @var FileManager */
    protected $fileManager;

    /** @var Config */
    protected $config;

    /** @var Metadata */
    private $metadata;

    public function __construct(
        FileStorageManager $fileStorageManager,
        Acl $acl,
        EntityManager $entityManager,
        FileManager $fileManager,
        Config $config,
        Metadata $metadata
    ) {
        $this->fileStorageManager = $fileStorageManager;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->fileManager = $fileManager;
        $this->config = $config;
        $this->metadata = $metadata;
    }

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');
        $size = $request->getQueryParam('size') ?? null;

        if (!$id) {
            throw new BadRequest();
        }

        $this->show($response, $id, $size, false);
    }

    protected function show(Response $response, string $id, ?string $size, bool $disableAccessCheck = false): void
    {
        $attachment = $this->entityManager->getEntity('Attachment', $id);

        if (!$attachment) {
            throw new NotFoundSilent();
        }

        if (!$disableAccessCheck && !$this->acl->checkEntity($attachment)) {
            throw new Forbidden();
        }

        $fileType = $attachment->get('type');

        if (!in_array($fileType, $this->getAllowedFileTypeList())) {
            throw new Forbidden("Not allowed file type '{$fileType}'.");
        }

        if ($this->allowedRelatedTypeList) {
            if (!in_array($attachment->get('relatedType'), $this->allowedRelatedTypeList)) {
                throw new NotFound();
            }
        }

        if ($this->allowedFieldList) {
            if (!in_array($attachment->get('field'), $this->allowedFieldList)) {
                throw new NotFound();
            }
        }

        $toResize = $size && in_array($fileType, $this->getResizableFileTypeList());

        if ($toResize) {
            $fileName = $size . '-' . $attachment->get('name');

            $contents = $this->getThumbContents($attachment, $size);

            $fileSize = strlen($contents);

            $response->writeBody($contents);
        }
        else {
            $fileName = $attachment->get('name');

            $stream = $this->fileStorageManager->getStream($attachment);

            $fileSize = $stream->getSize() ?? $this->fileStorageManager->getSize($attachment);

            $response->setBody($stream);
        }

        if ($fileType) {
            $response->setHeader('Content-Type', $fileType);
        }

        $response
            ->setHeader('Content-Disposition', 'inline;filename="' . $fileName . '"')
            ->setHeader('Pragma', 'public')
            ->setHeader('Cache-Control', 'max-age=360000, must-revalidate')
            ->setHeader('Content-Length', (string) $fileSize)
            ->setHeader('Content-Security-Policy', "default-src 'self'");
    }

    protected function getThumbContents(Attachment $attachment, string $size): string
    {
        if (!array_key_exists($size, $this->getSizes())) {
            throw new Error("Bad size.");
        }

        $useCache = !$this->config->get('thumbImageCacheDisabled', false);

        $sourceId = $attachment->getSourceId();

        $cacheFilePath = "data/upload/thumbs/{$sourceId}_{$size}";

        if ($useCache && $this->fileManager->isFile($cacheFilePath)) {
            return $this->fileManager->getContents($cacheFilePath);
        }

        $filePath = $this->getAttachmentRepository()->getFilePath($attachment);

        if (!$this->fileManager->isFile($filePath)) {
            throw new NotFound();
        }

        $fileType = $attachment->get('type');

        $targetImage = $this->createThumbImage($filePath, $fileType, $size);

        ob_start();

        switch ($fileType) {
            case 'image/jpeg':
                imagejpeg($targetImage);

                break;

            case 'image/png':
                imagepng($targetImage);

                break;

            case 'image/gif':
                imagegif($targetImage);

                break;

            case 'image/webp':
                imagewebp($targetImage);

                break;
        }

        $contents = ob_get_contents();

        ob_end_clean();

        imagedestroy($targetImage);

        if ($useCache) {
            $this->fileManager->putContents($cacheFilePath, $contents);
        }

        return $contents;
    }

    protected function createThumbImage(string $filePath, string $fileType, string $size)
    {
        if (!is_array(getimagesize($filePath))) {
            throw new Error();
        }

        list($originalWidth, $originalHeight) = getimagesize($filePath);

        list($width, $height) = $this->getSizes()[$size];

        if ($originalWidth <= $width && $originalHeight <= $height) {
            $targetWidth = $originalWidth;
            $targetHeight = $originalHeight;
        }
        else {
            if ($originalWidth > $originalHeight) {
                $targetWidth = $width;
                $targetHeight = $originalHeight / ($originalWidth / $width);

                if ($targetHeight > $height) {
                    $targetHeight = $height;
                    $targetWidth = $originalWidth / ($originalHeight / $height);
                }
            } else {
                $targetHeight = $height;
                $targetWidth = $originalWidth / ($originalHeight / $height);

                if ($targetWidth > $width) {
                    $targetWidth = $width;
                    $targetHeight = $originalHeight / ($originalWidth / $width);
                }
            }
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        switch ($fileType) {

            case 'image/jpeg':

                $sourceImage = imagecreatefromjpeg($filePath);

                imagecopyresampled(
                    $targetImage, $sourceImage, 0, 0, 0, 0,
                    $targetWidth, $targetHeight, $originalWidth, $originalHeight
                );
                break;

            case 'image/png':

                $sourceImage = imagecreatefrompng($filePath);

                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);

                $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);

                imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);

                imagecopyresampled(
                    $targetImage, $sourceImage, 0, 0, 0, 0,
                    $targetWidth, $targetHeight, $originalWidth, $originalHeight
                );

                break;

            case 'image/gif':

                $sourceImage = imagecreatefromgif($filePath);

                imagecopyresampled(
                    $targetImage, $sourceImage, 0, 0, 0, 0,
                    $targetWidth, $targetHeight, $originalWidth, $originalHeight
                );

                break;

            case 'image/webp':

                $sourceImage = imagecreatefromwebp($filePath);

                imagecopyresampled(
                    $targetImage, $sourceImage, 0, 0, 0, 0,
                    $targetWidth, $targetHeight, $originalWidth, $originalHeight
                );

                break;
        }

        if (in_array($fileType, $this->getFixOrientationFileTypeList())) {
            $targetImage = $this->fixOrientation($targetImage, $filePath);
        }

        return $targetImage;
    }

    protected function getOrientation($filePath)
    {
        $orientation = 0;

        if (function_exists('exif_read_data')) {
            $orientation = exif_read_data($filePath)['Orientation'] ?? null;
        }

        return $orientation;
    }

    protected function fixOrientation($targetImage, $filePath)
    {
        $orientation = $this->getOrientation($filePath);

        if ($orientation) {
            $angle = array_values([0, 0, 0, 180, 0, 0, -90, 0, 90])[$orientation];

            $targetImage = imagerotate($targetImage, $angle, 0);
        }

        return $targetImage;
    }

    private function getAllowedFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];
    }

    private function getResizableFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'resizableFileTypeList']) ?? [];
    }

    private function getFixOrientationFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'fixOrientationFileTypeList']) ?? [];
    }

    protected function getSizes(): array
    {
        return $this->metadata->get(['app', 'image', 'sizes']) ?? [];
    }

    protected function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepository(Attachment::ENTITY_TYPE);
    }
}
