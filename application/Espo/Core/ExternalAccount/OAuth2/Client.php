<?php

namespace Espo\Core\ExternalAccount\OAuth2;

class Client
{
	const AUTH_TYPE_URI = 0;	
	const AUTH_TYPE_AUTHORIZATION_BASIC = 1;	
	const AUTH_TYPE_FORM = 2;	
	
	const TOKEN_TYPE_URI = 'Uri';	
	const TOKEN_TYPE_BEARER = 'Bearer';	
	const TOKEN_TYPE_OAUTH = 'OAuth';		
	
	const CONTENT_TYPE_APPLICATION = 0;	
	const CONTENT_TYPE_MULTIPART = 1;	
	
	const HTTP_METHOD_GET = 'GET';	
	const HTTP_METHOD_POST = 'POST';	
	const HTTP_METHOD_PUT = 'PUT';
	
	const HTTP_METHOD_DELETE = 'DELETE';	
	const HTTP_METHOD_HEAD = 'HEAD';	
	const HTTP_METHOD_PATCH = 'PATCH';	
	
	const GRANT_TYPE_AUTHORIZATION_CODE = 'authorization_code';	
	const GRANT_TYPE_REFRESH_TOKEN = 'refresh_token';	
	const GRANT_TYPE_PASSWORD = 'password';	
	const GRANT_TYPE_CLIENT_CREDENTIALS = 'client_credentials';	
	
	protected $clientId = null;
	
	protected $clientSecret = null;
	
	protected $accessToken = null;
	
	protected $authType = self::AUTH_TYPE_URI;
	
	protected $tokenType = self::TOKEN_TYPE_URI;	
	
	protected $accessTokenSecret = null;
	
	protected $accessTokenParamName = 'access_token';
	
	protected $certificateFile = null;
	
	protected $curlOptions = array();
	
	public function __construct(array $params = array())
    {
    	if (!extension_loaded('curl')) {
    		throw new \Exception('CURL extension not found.');
    	}
    }
    
    public function setClientId($clientId)
    {    
    	$this->clientId = $clientId;
	}
	
    public function setClientSecret($clientSecret)
    {    
    	$this->clientSecret = $clientSecret;
	}
    
    public function setAccessToken($accessToken)
    {
		$this->accessToken = $accessToken;
	}
	
	public function setAuthType($authType)
	{
		$this->authType = $authType;
	}
	
	public function setCertificateFile($certificateFile)
	{
		$this->certificateFile = $certificateFile;
	}
	
	public function setCurlOption($option, $value)
	{
		$this->curlOptions[$option] = $value;
	}
	
	public function setCurlOptions($options)
	{
		$this->curlOptions = array_merge($this->curlOptions, $options);
	}
	
	public function setTokenType($tokenType)
	{
		$this->tokenType = $tokenType;
	}
	
	public function setAccessTokenSecret($accessTokenSecret)
	{
		$this->accessTokenSecret = $accessTokenSecret;
	}	
	
	public function request($url, $params = array(), $httpMethod = self::HTTP_METHOD_GET, array $httpHeaders = array(), $contentType = self::CONTENT_TYPE_MULTIPART)
	{
		if ($this->accessToken) {
			switch ($this->tokenType) {
				case self::TOKEN_TYPE_URI:
					$params[$this->accessTokenParamName] = $this->accessToken;
					break;
				case self::TOKEN_TYPE_BEARER:
					$httpHeaders['Authorization'] = 'Bearer ' . $this->accessToken;
					break;
				case self::TOKEN_TYPE_OAUTH:
					$httpHeaders['Authorization'] = 'OAuth ' . $this->accessToken;
					break;
				default:
					throw new \Exception('Unknown access token type.');
					
			}
		}

		return $this->execute($url, $params, $httpMethod, $httpHeaders, $contentType);
	}
	
	private function execute($url, $params = array(), $httpMethod, array $httpHeaders = array(), $contentType = self::CONTENT_TYPE_MULTIPART)
    {
    	$curlOptions = array(
    		CURLOPT_RETURNTRANSFER => true,
    		CURLOPT_SSL_VERIFYPEER => true,
    		CURLOPT_CUSTOMREQUEST => $httpMethod
    	);
    	    	
    	switch ($httpMethod) {
    		case self::HTTP_METHOD_POST:
    			$curlOptions[CURLOPT_POST] = true;
    		case self::HTTP_METHOD_PUT:
    		case self::HTTP_METHOD_PATCH:
				if (self::CONTENT_TYPE_APPLICATION === $contentType) {
					$postFields = http_build_query($params, null, '&');
				}
				$curlOptions[CURLOPT_POSTFIELDS] = $postFields;
				break;
			case self::HTTP_METHOD_HEAD:
				$curlOptions[CURLOPT_NOBODY] = true;
			case self::HTTP_METHOD_DELETE:
			case self::HTTP_METHOD_GET:
				if (strpos($url, '?') === false) {
					$url .= '?';
				}
				$url .= http_build_query($params, null, '&');
				break;
			default:
				break;
    	}

		$curlOptions[CURLOPT_URL] = $url;
		
		$curlOptHttpHeader = array();
		foreach ($httpHeaders as $key => $value) {
			 $curlOptHttpHeader[] = "{$key}: {$value}";
		}
		$curlOptions[CURLOPT_HTTPHEADER] = $curlOptHttpHeader;
		
		$ch = curl_init();
		curl_setopt_array($ch, $curlOptions);
		
		curl_setopt($ch, CURLOPT_HEADER, 1);
		
		if (!empty($this->certificateFile)) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
			curl_setopt($ch, CURLOPT_CAINFO, $this->certificateFile);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		}
		
		if (!empty($this->curlOptions)) {
			curl_setopt_array($ch, $this->curlOptions);
		}
				
		$response = curl_exec($ch);		
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);		
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		
		$responceHeader = substr($response, 0, $headerSize);
		$responceBody = substr($response, $headerSize);
		
		$resultArray = null;
		
		if ($curlError = curl_error($ch)) {
			throw new \Exception($curlError);
		} else {
			$resultArray = json_decode($responceBody, true);
		}
		curl_close($ch);
		
		return array(
			'result' => (null !== $resultArray) ? $resultArray: $responceBody,
			'code' => intval($httpCode),
			'contentType' => $contentType,
			'header' => $responceHeader,
		);
    }
    
    public function getAccessToken($url, $grantType, array $params)
    {
    	$params['grant_type'] = $grantType;
    	
    	$httpHeaders = array();
    	switch ($this->clientAuth) {
    		case self::AUTH_TYPE_URI:
    		case self::AUTH_TYPE_FORM:
    			$params['client_id'] = $this->clientId;
    			$params['client_secret'] = $this->clientSecret;
    			break;
    		case self::AUTH_TYPE_AUTHORIZATION_BASIC:
    			$params['client_id'] = $this->clientId;
    			$httpHeaders['Authorization'] = 'Basic ' . base64_encode($this->clientId .  ':' . $this->clientSecret);
    			break;
    		default:
    			throw new \Exception();
    	}
    	
    	return $this->execute($url, $params, self::HTTP_METHOD_POST, $httpHeaders, self::CONTENT_TYPE_APPLICATION);
    }
}

