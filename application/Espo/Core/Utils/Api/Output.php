<?php

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

