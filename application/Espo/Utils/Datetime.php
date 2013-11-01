<?php

namespace Espo\Utils;

use Espo\Utils as Utils;

class Datetime
{
	protected $options;

    /**
    * Get date in defined format
	*
	* @param string $format
	* @return object
	*/
	protected function date($format)
	{
		return date($format);
	}

    /**
    * Get time
	*
	* @param string $format
	* @return object
	*/
	public function getTime($format='')
	{
		if (empty($format)) {
        	$format= $this->getTimeFormat();
		}

		return $this->date($format);
	}

	/**
    * Get date
	*
	* @param string $format
	* @return object
	*/
	public function getDate($format='')
	{
		if (empty($format)) {
        	$format= $this->getDateFormat();
		}

		return $this->date($format);
	}

	/**
    * Get date in datetime format
	*
	* @param string $format
	* @return object
	*/
	public function getDatetime($format='')
	{
		if (empty($format)) {
        	$format= $this->getDatetimeFormat();
		}

		return $this->date($format);
	}

    /**
    * Get Date format
	*
	* @return string
	*/
	public function getDateFormat()
	{
		return $this->getPhpDateFormat( $this->getOptions()->get('dateFormat') );
	}

	/**
    * Get Time format
	*
	* @return string
	*/
	public function getTimeFormat()
	{
		return $this->getPhpDateFormat( $this->getOptions()->get('timeFormat') );
	}

	/**
    * Get Datetime format
	*
	* @return string
	*/
	public function getDatetimeFormat()
	{
		return $this->getDateFormat().' '.$this->getTimeFormat();
	}

	/**
    * Convert javascript to php date format
	* NEED TO CHANGE
	*
	* @return string
	*/
	public function getPhpDateFormat($jsFormat)
	{
    	$rules = array(
			'YYYY' => 'Y',
			'MM' => 'm',
			'DD' => 'd',
			'HH' => 'H',
			'mm' => 'i',
        	/*'dd' => 'd',
        	'd' => 'j',
        	'DD' => 'l',
        	'o' => 'z',
        	'MM' => 'F',
        	'M' => 'M',
        	'm' => 'n',
        	'mm' => 'm',
        	'yy' => 'Y',
        	'y' => 'y',*/
		);

		$pattern = array_keys($rules);
		$replace = array_values($rules);
		foreach($pattern as &$p) {
			$p = '/'.$p.'/';
		}

		return preg_replace($pattern, $replace, $jsFormat);
	}

	/**
    * Set options from the system config
	*
	* @return object
	*/
	function getOptions()
	{
		if (isset($this->options) && is_object($this->options)) {
    		return $this->options;
    	}

		$this->options = new Utils\Configurator();

		return $this->options;
	}

}

?>