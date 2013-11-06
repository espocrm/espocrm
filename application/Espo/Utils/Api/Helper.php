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
	* @param string $errMessage - error message
	* @param int $errCode - error status code
	*
	* @return void - Only echo the result
	*/
    function output($data=null, $errMessage='Error', $errCode=500)
	{
		$app= Slim::getInstance();

		//check if result is false
		if ($data === false) {
			global $base;
			$logMess= empty($errMessage) ? 'result is not expected' : $errMessage;
			$base->log->add('ERROR', 'API ['.$app->request()->getMethod().']:'.$app->router()->getCurrentRoute()->getPattern().', Params:'.print_r($app->router()->getCurrentRoute()->getParams(), true).', InputData: '.$app->request()->getBody().' - '.$logMess);
			Utils\Api\Helper::displayError($errMessage, $errCode);
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