<?php

namespace Espo\Core\Utils\Api;

class Rest
{
	private $slim;


    public function __construct(\Slim\Slim $slim)
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
	* @param string $errMessage - error message
	* @param int $errCode - error status code
	*
	* @return void - Only echo the result
	*/
    public function render($data=null, $errMessage='Error', $errCode=500)
	{
    	if (is_array($data)) {
    		$dataArr = array_values($data);

            $data = empty($dataArr[0]) ? false : $dataArr[0];
            $errMessage = empty($dataArr[1]) ? $errMessage : $dataArr[1];
            $errCode = empty($dataArr[2]) ? $errCode : $dataArr[2];
    	}

		//check if result is false
		if ($data === false || !is_string($data)) {
			$logMess= empty($errMessage) ? 'result is not expected' : $errMessage;
			$GLOBALS['log']->add('ERROR', 'API ['.$this->getSlim()->request()->getMethod().']:'.$this->getSlim()->router()->getCurrentRoute()->getPattern().', Params:'.print_r($this->getSlim()->router()->getCurrentRoute()->getParams(), true).', InputData: '.$this->getSlim()->request()->getBody().' - '.$logMess);
			$this->displayError($errMessage, $errCode);
    	}
		//END: check if result is false

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
	public function displayError($text, $statusCode=500)
	{
        $GLOBALS['log']->add('INFO', 'Display Error: '.$text.', Code: '.$statusCode.' URL: '.$_SERVER['REQUEST_URI']);

		if ( !empty( $this->slim) ) {
			$this->getSlim()->response()->status($statusCode);
			$this->getSlim()->response()->header('X-Status-Reason', $text);
			$this->getSlim()->stop();
	    }
		else {
			$GLOBALS['log']->add('INFO', 'Could not get Slim instance. It looks like a direct call (bypass API). URL: '.$_SERVER['REQUEST_URI']);
			die($text);
		}
	}

}

?>