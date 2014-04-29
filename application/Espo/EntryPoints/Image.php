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

class Image extends \Espo\Core\EntryPoints\Base
{
	public static $authRequired = false;
	
	public function run()
	{
	
		$id = $_GET['id'];
		if (empty($id)) {
			throw new BadRequest();
		}
		
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
		
		if (!file_exists($fileName)) {
			throw new NotFound();
		}
		
		if (!$attachment->get('global')) {
			throw new Forbidden();
		}	
		

		
		if ($attachment->get('type')) {
			header('Content-Type: ' . $attachment->get('type'));
		}

		
		header('Pragma: public');
		header('Content-Length: ' . filesize($fileName));
		ob_clean();
		flush();
		readfile($fileName);
		exit;		
	}	
}

