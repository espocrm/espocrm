<?php

namespace Espo\Core\Utils;


class Datetime
{
	private $config;


	public function __construct(\Espo\Core\Utils\Config $config)  //TODO
	{
		$this->config = $config;
	}

	protected function getConfig()
	{
		return $this->config;
	}

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
		return $this->getPhpDateFormat( $this->getConfig()->get('dateFormat') );
	}

	/**
    * Get Time format
	*
	* @return string
	*/
	public function getTimeFormat()
	{
		return $this->getPhpDateFormat( $this->getConfig()->get('timeFormat') );
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

}

?>