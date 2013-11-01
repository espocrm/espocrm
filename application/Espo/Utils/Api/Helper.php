<?php

namespace Espo\Utils\Api;

use \Slim\Slim,
	\Espo\Utils as Utils;


class Helper
{

	/**
    * Output the result
	*
	* @param mixed $data - JSON
	* @param string $error - error message
	* @param int $errorCode - error status code
	*
	* @return void - Only echo the result
	*/
    function output($data=null, $error='Error', $errorCode=500)
	{
		$app= Slim::getInstance();

		//check if result is false
		if ($data === false) {
			global $base;
			$logMess= empty($error) ? 'result is not expected' : $error;
			$base->log->add('ERROR', 'API:'.$app->router()->getCurrentRoute()->getPattern().', Method: '.$app->router()->getCurrentRoute()->getCallable().', InputData: '.$app->request()->getBody().' - '.$logMess);

			Utils\Api\Helper::displayError($error, $errorCode);
    	}
		//END: check if result is false

		//$json= new Utils\JSON();
		//$data= $json->isJSON($data) ? $data : $json->encode($data);

		//Can be optimized to the manual selection of input data type
		/*if ($jsonEncode) {
        	$data= Utils\JSON::encode($data);
		}*/

		ob_clean();
    	echo $data;
		$app->stop();
	}

	/**
    * Output the error and stop app execution
	*
	* @static
	* @param string $text
	* @param int $statusCode
	*
	* @return void
	*/
	public static function displayError($text, $statusCode=500)
	{
        $log= new Utils\Log();
        $log->add('INFO', 'Display Error: '.$text.', Code: '.$statusCode.' URL: '.$_SERVER['REQUEST_URI']);

		if (class_exists('Slim\Slim', false)) {
	        $app = Slim::getInstance();

			$app->response()->status($statusCode);
			$app->response()->header('X-Status-Reason', $text);
			//$app->response()->header('X-Status-Reason', 'Fatal Error/Exception. Please check error log file for details.');
			$app->stop();
	    }
		else {
			$log->add('INFO', 'Could not get Slim instance. It looks like a direct call (bypass API). URL: '.$_SERVER['REQUEST_URI']);
			die($text);
		}
	}

}

?>