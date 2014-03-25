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

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Forbidden;

class Admin extends \Espo\Core\Controllers\Base
{	
	protected function checkGlobalAccess()
	{
		if (!$this->getUser()->isAdmin()) {
        	throw new Forbidden();
		}
	}

    public function actionRebuild($params, $data)
	{
		try {
			$result = $this->getContainer()->get('schema')->rebuild();
	   	} catch (\Exception $e) {
            $result = false;
		  	$GLOBALS['log']->error('Fault to rebuild database schema'.'. Details: '.$e->getMessage());
		}

		if ($result === false) {
			throw new Error("Error while rebuilding database");
		}

		return json_encode($result);
	}

	public function actionClearCache($params, $data)
	{		
		$cacheDir = $this->getContainer()->get('config')->get('cachePath');			

		$result = $this->getContainer()->get('fileManager')->removeInDir($cacheDir);

		if ($result === false) {
			throw new Error("Error while clearing cache");
		}

		return json_encode($result);
	}

}

