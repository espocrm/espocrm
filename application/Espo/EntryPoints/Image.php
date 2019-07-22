<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\NotFoundSilent;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class Image extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = true;

    protected $allowedFileTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    protected $imageSizes = [
        'xxx-small' => [18, 18],
        'xx-small' => [32, 32],
        'x-small' => [64, 64],
        'small' => [128, 128],
        'medium' => [256, 256],
        'large' => [512, 512],
        'x-large' => [864, 864],
        'xx-large' => [1024, 1024],
    ];

    protected $fixOrientationFileTypeList = [
        'image/jpeg',
    ];

    protected $allowedRelatedTypeList = null;

    protected $allowedFieldList = null;

    public function run()
    {
        if (empty($_GET['id'])) {
            throw new BadRequest();
        }
        $id = $_GET['id'];

        $size = null;
        if (!empty($_GET['size'])) {
            $size = $_GET['size'];
        }

        $this->show($id, $size, false);
    }

    protected function show($id, $size, $disableAccessCheck = false)
    {
        $attachment = $this->getEntityManager()->getEntity('Attachment', $id);

        if (!$attachment) {
            throw new NotFoundSilent();
        }

        if (!$disableAccessCheck && !$this->getAcl()->checkEntity($attachment)) {
            throw new Forbidden();
        }

        $sourceId = $attachment->getSourceId();

        $filePath = $this->getEntityManager()->getRepository('Attachment')->getFilePath($attachment);

        $fileType = $attachment->get('type');

        if (!file_exists($filePath)) {
            throw new NotFoundSilent();
        }

        if (!in_array($fileType, $this->allowedFileTypes)) {
            throw new Error();
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

        if (!empty($size)) {
            if (!empty($this->imageSizes[$size])) {
                $thumbFilePath = "data/upload/thumbs/{$sourceId}_{$size}";

                if (!file_exists($thumbFilePath)) {
                    $targetImage = $this->getThumbImage($filePath, $fileType, $size);
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
                    $this->getContainer()->get('fileManager')->putContents($thumbFilePath, $contents);
                }
                $filePath = $thumbFilePath;

            } else {
                throw new Error();
            }
        }

        if (!empty($size)) {
            $fileName = $size . '-' . $attachment->get('name');
        } else {
            $fileName = $attachment->get('name');
        }
        header('Content-Disposition:inline;filename="'.$fileName.'"');
        if (!empty($fileType)) {
            header('Content-Type: ' . $fileType);
        }
        header('Pragma: public');
        header('Cache-Control: max-age=360000, must-revalidate');
        $fileSize = filesize($filePath);
        if ($fileSize) {
            header('Content-Length: ' . $fileSize);
        }
        readfile($filePath);
        exit;
    }

    protected function getThumbImage($filePath, $fileType, $size)
    {
        if (!@is_array(getimagesize($filePath))) {
            throw new Error();
        }

        list($originalWidth, $originalHeight) = getimagesize($filePath);
        list($width, $height) = $this->imageSizes[$size];

        if ($originalWidth <= $width && $originalHeight <= $height) {
            $targetWidth = $originalWidth;
            $targetHeight = $originalHeight;
        } else {
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
                imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($filePath);
                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);
                $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
                imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
                imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($filePath);
                imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($filePath);
                imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
                break;
        }

        if (in_array($fileType, $this->fixOrientationFileTypeList)) {
            $targetImage = $this->fixOrientation($targetImage, $filePath);
        }

        return $targetImage;
    }

    protected function getOrientation($filePath)
    {
        $orientation = 0;
        if (function_exists('exif_read_data')) {
            $orientation = @exif_read_data($filePath)['Orientation'];
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
}
