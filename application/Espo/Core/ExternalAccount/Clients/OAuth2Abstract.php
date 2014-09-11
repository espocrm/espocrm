<?php

namespace Espo\Core\ExternalAccount\Clients;

use \Espo\Core\Exceptions\Error;

use \Espo\Core\ExternalAccount\OAuth2\Client;

abstract class OAuth2Abstract
{
	protected $client = null;
	
	protected $paramList = array(
		'endpoint',
		'tokenEndpoint',
		'clientId',
		'clientSecret',
		'accessToken',
		'refreshToken',
		'redirectUri'
	);
	
	protected $clientId = null;
	
	protected $clientSecret = null;
	
	protected $accessToken = null;
	
	protected $refreshToken = null;
	
	protected $redirectUri = null;
	
	public function __construct($client, array $params = array())
	{
		$this->client = $client;

		$this->setParams($params);
	}
	
	public function getParam($name)
	{
		if (in_array($name, $this->paramList)) {
			return $this->$name;
		}
	}
	
	public function setParam($name, $value)
	{
		if (in_array($name, $this->paramList)) {
			$methodName = 'set' . ucfirst($name);
			if (method_exists($this->client, $methodName)) {
				$this->client->$methodName($value);
			}
			$this->$name = $value;
		}
	}
	
	public function setParams($params)
	{
		foreach ($this->paramList as $name) {
			if (!empty($params[$name])) {
				$this->setParam($name, $params[$name]);
			}
		}
	}
	
	public function getAccessTokenFromAuthorizationCode($code)
	{
		$r = $this->client->getAccessToken($this->getParam('tokenEndpoint'), Client::GRANT_TYPE_AUTHORIZATION_CODE, array(
			'code' => $code,
			'redirect_uri' => $this->getParam('redirectUri')
		));
		if ($r['code'] == '200') {
			$data = array();
			if (!empty($r['result'])) {
				$data['accessToken'] = $r['result']['access_token'];
				$data['tokenType'] = $r['result']['token_type'];
				$data['refreshToken'] = $r['result']['refresh_token'];		
			}
			return $data;
		}
		return null;
	}	
}

