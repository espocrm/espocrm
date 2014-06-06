<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/ 

namespace Espo\EntryPoints;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class Image extends \Espo\Core\EntryPoints\Base
{
	public static $authRequired = true;
	
	protected $allowedFileTypes = array(
		'image/jpeg',
		'image/png',
		'image/gif',
	);
	
	protected $imageSizes = array(
		'x-small' => array(64, 64),	
		'small' => array(128, 128),	
		'medium' => array(256, 256),
		'large' => array(512, 512),
		'x-large' => array(864, 864),
		'xx-large' => array(1024, 1024),
	);
	
	public function run()
	{	
		$id = $_GET['id'];
		if (empty($id)) {
			throw new BadRequest();
		}
		
		$size = $_GET['size'];
		
		$attachment = $this->getEntityManager()->getEntity('Attachment', $id);
		
		if (!$attachment) {
			throw new NotFound();
		}		
		
		if ($attachment->get('parentId') && $attachment->get('parentType')) {
			$parent = $this->getEntityManager()->getEntity($attachment->get('parentType'), $attachment->get('parentId'));			
			if (!$this->getAcl()->check($parent)) {
				throw new Forbidden();
			}
		}
		
		$filePath = "data/upload/{$attachment->id}";
		
		$fileType = $attachment->get('type');
		
		if (!file_exists($filePath)) {
			throw new NotFound();
		}
		
		if (!in_array($fileType, $this->allowedFileTypes)) {
			throw new Error();
		}
		
		if (!empty($size)) {
			if (!empty($this->imageSizes[$size])) {
				$thumbFilePath = "data/upload/thumbs/{$attachment->id}_{$size}";
				
				if (!file_exists($thumbFilePath)) {
					$targetImage = $this->getThumbImage($filePath, $fileType, $size);					
					ob_start();	
					imagejpeg($targetImage);
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
			$fileName = $attachment->id . '_' . $size . '.jpg';
		} else {
			$fileName = $attachment->get('name');
		}	
		header('Content-Disposition:inline;filename="'.$fileName.'"');		
		if (!empty($fileType)) {
			header('Content-Type: ' . $fileType);
		}		
		header('Pragma: public');
		$fileSize = filesize($filePath);		
		if ($fileSize) {
			header('Content-Length: ' . $fileSize);
		}
		ob_clean();
		flush();
		readfile($filePath);
		exit;		
	}
	
	protected function getThumbImage($filePath, $fileType, $size)
	{
		list($originalWidth, $originalHeight) = getimagesize($filePath);
		list($width, $height) = $this->imageSizes[$size];
		
		if ($originalWidth > $width && ($originalHeight <= $height || $originalWidth > $originalHeight)) {
			$targetWidth = $width;
			$targetHeight = $originalHeight * ($width / $originalWidth);
		} else if ($originalHeight > $height && ($originalWidth <= $width || $originalHeight > $originalWidth)) {
			$targetHeight = $height;
			$targetWidth = $originalWidth * ($height / $targetHeight);
		} else {
			$targetWidth = $originalWidth;
			$targetHeight = $originalHeight;					
		}
				
		$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);				
		switch ($fileType) {
			case 'image/jpeg':
				$sourceImage = imagecreatefromjpeg($filePath);
				break;
			case 'image/png':
				$sourceImage = imagecreatefrompng($filePath);
				break;
			case 'image/gif':
				$sourceImage = imagecreatefromgif($filePath);
				break;					
		}
		imagecopyresized($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);	
		
		return $targetImage;
	}
}

