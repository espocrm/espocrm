<?php

namespace Espo\Tests\Api;

class RestTesterClass
{
	public $mainUrl= 'http://172.20.0.1/espocrm/api';
	public $username= 'admin';
	public $pass= '1';
	public $type= 'PATCH';
	public $url= '/';

	function setType($name)
	{
		$this->type= strtoupper($name);
	}

	function setUrl($url)
	{
        $this->url= (substr($url, 0,1)=='/') ? $this->mainUrl.$url : $this->mainUrl.'/'.$url;
	}

	function getResponse($jsonData='', $output=false)
	{
		$ch = curl_init($this->url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->pass);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Sample Code');

		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->type);

		if (!empty($jsonData)) {
        	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			    'Content-Type: application/json',
			    'Content-Length: ' . strlen($jsonData))
			);
		}

		$response = curl_exec($ch);
		$resultStatus = curl_getinfo($ch);

		curl_close($ch);

		//echo '<pre>';
		//echo $response;

		if($resultStatus['http_code'] == 200) {
			if ($output) {
				echo "\r\n".$response."\r\n\r\n";
			}
		} else {
			if ($output) {
		    	echo 'CALL FAILED: '.print_r($resultStatus, true).'<hr/ >'.$response;
			}
		}

		return array('response'=>$response, 'code'=>$resultStatus['http_code']);
	}

	function isSuccess($jsonData='', $output=false)
	{
        $response= $this->getResponse($jsonData, $output);

		return ($response['code'] == 200) ? true : false;
	}

}

?>