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
		'xsmall' => array(64, 64),	
		'small' => array(128, 128),	
		'medium' => array(256, 256),
		'large' => array(512, 512),
		'xlarge' => array(1024, 1024),
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
		
		$fileName = "data/upload/{$attachment->id}";
		
		$fileType = $attachment->get('type');
		
		if (!file_exists($fileName)) {
			throw new NotFound();
		}
		
		if (!in_array($fileType, $this->allowedFileTypes)) {
			throw new Error();
		}
		
		if (!empty($size)) {
			if (!empty($this->imageSizes[$size])) {			
				// TODO cache thumbs				
				list($originalWidth, $originalHeight) = getimagesize($fileName);
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
						$sourceImage = imagecreatefromjpeg($fileName);
						break;
					case 'image/png':
						$sourceImage = imagecreatefrompng($fileName);
						break;
					case 'image/gif':
						$sourceImage = imagecreatefromgif($fileName);
						break;					
				}
				imagecopyresized($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);			
			} else {
				throw new Error();
			}		
		}

		
		if (!empty($fileType)) {
			header('Content-Type: ' . $fileType);
		}		
		header('Pragma: public');

		if (!empty($targetImage)) {
			ob_clean();
			flush();
			imagejpeg($targetImage);
			imagedestroy($targetImage);
		} else {
			header('Content-Length: ' . filesize($fileName));
			ob_clean();
			flush();
			readfile($fileName);
		}
		exit;		
	}	
}

