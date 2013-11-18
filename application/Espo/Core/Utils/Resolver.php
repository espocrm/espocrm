<?php

namespace Espo\Core\Utils;


class Resolver
{
    protected $exceptions = array(
		'Doctrine\DBAL\DBALException' => 'DBALException',
		'ReflectionException' => 'ReflectionException',
	);

	private $metadata;


	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;
	}


	protected function getMetadata()
	{
		return $this->metadata;
	}


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

			$GLOBALS['log']->add('INFO', 'Try to resolve the exception - '.$handler);
			$result= false;
			try {
	        	$result= $this->$method($args);
	        } catch(\Exception $e) {
	        	$GLOBALS['log']->add('INFO', 'Could not resolve the exception - '.$handler.'. Details below:');
	        	$GLOBALS['log']->catchException($e, false);
	        }
			return $result;
		}

        return false;
	}


	protected function DBALException($args)
	{
		return $this->getMetadata()->getDoctrineConverter()->rebuildDatabase();
	}

    protected function ReflectionException($args)
	{
		return $this->getMetadata()->getDoctrineConverter()->generateEntities($args);
	}




}

?>
