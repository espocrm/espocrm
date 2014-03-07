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

namespace Espo\Core\Utils\Api;

class Output
{
	private $slim;


    public function __construct(\Espo\Core\Utils\Api\Slim $slim)
    {
    	$this->slim = $slim;
    }

	protected function getSlim()
	{
		return $this->slim;
	}

	/**
    * Output the result
	*
	* @param mixed $data - JSON
	*/
    public function render($data = null)
	{
    	if (is_array($data)) {
    		$dataArr = array_values($data);
            $data = empty($dataArr[0]) ? false : $dataArr[0];
    	}

		ob_clean();
    	echo $data;
	}
	
	public function processError($message = 'Error', $code = 500)
	{
		$GLOBALS['log']->error('API ['.$this->getSlim()->request()->getMethod().']:'.$this->getSlim()->router()->getCurrentRoute()->getPattern().', Params:'.print_r($this->getSlim()->router()->getCurrentRoute()->getParams(), true).', InputData: '.$this->getSlim()->request()->getBody().' - '.$message);
		$this->displayError($message, $code);
		
		ob_clean();
    	echo $data;
		$this->getSlim()->stop();
	}

	/**
    * Output the error and stop app execution
	*
	* @param string $text
	* @param int $statusCode
	*
	* @return void
	*/
	public function displayError($text, $statusCode = 500)
	{
        $GLOBALS['log']->info('Display Error: '.$text.', Code: '.$statusCode.' URL: '.$_SERVER['REQUEST_URI']);

		if (!empty( $this->slim)) {
			$this->getSlim()->response()->status($statusCode);
			$this->getSlim()->response()->header('X-Status-Reason', $text);
			$this->getSlim()->stop();
	    }
		else {
			$GLOBALS['log']->info('Could not get Slim instance. It looks like a direct call (bypass API). URL: '.$_SERVER['REQUEST_URI']);
			die($text);
		}
	}

}

