<?php

namespace Espo\Core\Loaders;

use Espo\Core\Utils;
use Monolog\Handler;

class Log
{
	private $container;

	public function __construct(\Espo\Core\Container $container)
	{
		$this->container = $container;
	}

	protected function getContainer()
	{
    	return $this->container;
	}

	public function load()
	{
		$config = $this->getContainer()->get('config');

		$logConfig = $config->get('logger');
		
		$log = new Utils\Log('Espo');	
		$levelCode = $log->getLevelCode($logConfig['level']);	

		if ($logConfig['isRotate']) {
			$handler = new Handler\RotatingFileHandler($logConfig['path'], $logConfig['maxRotateFiles'], $levelCode);
		} else {
			$handler = new Handler\StreamHandler($logConfig['path'], $levelCode);
		}
		$log->pushHandler($handler);
		\Monolog\ErrorHandler::register($log);			

		return $log;
	}
}

