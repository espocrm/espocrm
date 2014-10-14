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

namespace Espo\Core\Utils;

class Crypt 
{
	private $config;
	
	private $key = null;
	
	private $cryptKey = null;
	
	public function __construct($config)
	{
		$this->config = $config;		
		$this->cryptKey = $config->get('cryptKey', '');
	}	
	
	protected function getKey()
	{
		if (empty($this->key)) {
			$this->key = hash('sha256', $this->cryptKey, true);
		}
		return $this->key;
	}
	
	public function encrypt($string)
	{
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->getKey(), $string, MCRYPT_MODE_CBC));
	}
	
	public function decrypt($encryptedString)
	{
		return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->getKey(), base64_decode($encryptedString), MCRYPT_MODE_CBC));
	}
	
	public function generateKey()
	{
		return md5(uniqid());
	}
}

