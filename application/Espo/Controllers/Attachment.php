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

namespace Espo\Controllers;

class Attachment extends \Espo\Core\Controllers\Record
{

	public function actionUpload($params, $data)
	{		
		list($prefix, $contents) = explode(',', $data);
		$contents = base64_decode($contents);
		
		$attachment = $this->getEntityManager()->getEntity('Attachment');
		$this->getEntityManager()->saveEntity($attachment);		
		$this->getContainer()->get('fileManager')->putContents('data/upload/' . $attachment->id, $contents);
		
		return array(
			'attachmentId' => $attachment->id
		);	
	}

}

