<?php

namespace Espo\Modules\Crm\Business\Event;

class Ics
{
	private $_d_end;

	private $_d_start;

	private $_s_address;

	private $_s_description;

	private $_s_html;
	
	private $_s_who;
	
	private $_s_email;

	private $_s_uri;
	
	private $_s_uid;

	private $_s_summary;

	private $_s_output;

	private $_s_prodid;
	
	public function __construct($prodid, array $attributes = array())
	{
		if (!is_string($prodid) || $prodid === '') {
			throw new \Exception('PRODID is required');
		}

		$this->_s_prodid = $prodid;

		foreach ($attributes as $key => $value) {
			$this->$key = $value;
		}
	}
	
	public function __set($name, $value)
	{
		switch ($name) {
			case 'startDate':
				$this->_d_start = $value;
				break;

			case 'endDate':
				$this->_d_end = $value;
				break;

			case 'address':
				$this->_s_address = $value;
				break;

			case 'summary':
				$this->_s_summary = $value;
				break;
				
			case 'who':
				$this->_s_who = $value;
				break;
				
			case 'email':
				$this->_s_email = $value;
				break;

			case 'uri':
				$this->_s_uri = $value;
				break;
				
			case 'uid':
				$this->_s_uid = $value;
				break;

			case 'description':
				$this->_s_description = $value;
				break;

			case 'html':
				$this->_s_html = $value;
				break;
		}

		return $this;
	}
	
	public function __get($name) {
		switch ($name)
		{
			case 'startDate':
				return $this->_d_start;
				break;

			case 'endDate':
				return $this->_d_end;
				break;

			case 'address':
				return $this->_s_address;
				break;

			case 'summary':
				return $this->_s_summary;
				break;

			case 'uri':
				return $this->_s_uri;
				break;
				
			case 'who':
				return $this->_s_who;
				break;
				
			case 'email':
				return $this->_s_email;
				break;
				
			case 'uid':
				return $this->_s_uid;
				break;

			case 'description':
				return $this->_s_description;
				break;

			case 'html':
				return $this->_s_html;
				break;
		}
	}
	
	public function get()
	{
		($this->_s_output) ? $this->_s_output : $this->_generate();

		return $this->_s_output;
	}
	
	private function _generate()
	{
		$this->_s_output = "BEGIN:VCALENDAR\n".
			   "VERSION:2.0\n".
				 "PRODID:-".$this->_s_prodid."\n".
				 "METHOD:REQUEST\n".
				 "BEGIN:VEVENT\n".
				 "DTSTART:".$this->_dateToCal($this->startDate)."\n".
				 "DTEND:".$this->_dateToCal($this->endDate)."\n".
				 "SUMMARY:New ".$this->_escapeString($this->summary)."\n".
				 "LOCATION:".$this->_escapeString($this->address)."\n".
				 "ORGANIZER;CN=".$this->_escapeString($this->who).":MAILTO:" . $this->_escapeString($this->email)."\n".
				 "DESCRIPTION:".$this->_escapeString($this->description)."\n".
				 "X-ALT-DESC;FMTTYPE=text/html:".$this->_escapeString($this->html)."\n".
				 "URL;VALUE=URI:".$this->_escapeString($this->uri)."\n".
				 "UID:".$this->uid."\n".
				 "SEQUENCE:0\n".
				 "DTSTAMP:".$this->_dateToCal(time())."\n".
				 "END:VEVENT\n".
				 "END:VCALENDAR\n";
	}
	
	private function _dateToCal($timestamp)
	{
		return date('Ymd\THis\Z', ($timestamp) ? $timestamp : time());
	}

	private function _escapeString($string)
	{
		return preg_replace('/([\,;])/','\\\$1', ($string) ? $string : '');
	}	
}

