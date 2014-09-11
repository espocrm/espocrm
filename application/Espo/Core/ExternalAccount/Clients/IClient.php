<?php

namespace Espo\Core\ExternalAccount\Clients;

interface IClient
{
	public function getParam($name);
	
	public function setParam($name, $value);
	
	public function setParams(array $params);

	public function ping();
}

