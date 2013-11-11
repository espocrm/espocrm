<?php

namespace Espo\Utils;

use Espo\Utils as Utils;

class Resolver extends BaseUtils
{
    protected $exceptions = array(
		'Doctrine\DBAL\DBALException' => 'DBALException',
		'ReflectionException' => 'ReflectionException',
	);


	public function handle($Exception)
	{
		$handler= get_class($Exception);
    	$trace= $Exception->getTrace();
		$args= array();
		if (is_array($trace[0]['args'])) {
        	$args= $trace[0]['args'];
		}

		if (in_array($handler, array_keys($this->exceptions))) {
			$method= $this->exceptions[$handler];

			$this->getObject('Log')->add('INFO', 'Try to resolve the exception - '.$handler);
			$result= false;
			try {
	        	$result= $this->$method($args);
	        } catch(\Exception $e) {
	        	$this->getObject('Log')->add('INFO', 'Could not resolve the exception - '.$handler.'. Details below:');
	        	$this->getObject('Log')->catchException($e, false);
	        }
			return $result;
		}

        return false;
	}


	protected function DBALException($args)
	{
		return $this->getObject('Metadata')->rebuildDatabase();
	}

    protected function ReflectionException($args)
	{
		return $this->getObject('Metadata')->generateEntities($args);
	}




}

?>
