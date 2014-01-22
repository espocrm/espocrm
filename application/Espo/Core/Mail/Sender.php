<?php

namespace Espo\Core\Mail;

use \Espo\Entities\Email;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

use \Espo\Core\Exceptions\Error;

class Sender
{
	protected $config;

	protected $transport;

	public function __construct($config)
	{
		$this->config = $config;
		$this->transport = new SmtpTransport();
		$this->setupGlobal();
	}

	protected function setupGlobal()
	{
		$config = $this->config;

		$opts = array(
			'name' => 'admin',
			'host' => $config->get('smtpServer'),
			'port' => $config->get('smtpPort'),
			'connection_config' => array()
		);
		if ($config->get('smtpAuth')) {
			$opts['connection_class'] = 'login';
			$opts['connection_config']['username'] = $config->get('smtpUsername');
			$opts['connection_config']['password'] = $config->get('smtpPassword');
		}
		if ($config->get('smtpSecurity')) {
			$opts['connection_config']['ssl'] = strtolower($config->get('smtpSecurity'));
		}

		$options = new SmtpOptions($opts);
		$this->transport->setOptions($options);

		return $this;
	}

	public function send(Email $email)
	{
		$message = new Message();
		
		$config = $this->config;

		if ($email->get('from')) {
			$message->addFrom(trim($email->get('from')));
		} else {
			if (!$config->get('outboundEmailFromAddress')) {
				throw new Error('outboundEmailFromAddress is not specified in config.');
			}
			$message->addFrom($config->get('outboundEmailFromAddress'), $config->get('outboundEmailFromName'));
		}
		
		$value = $email->get('to');
		if ($value) {
			$arr = explode(';', $value);
			if (is_array($arr)) {
				foreach ($arr as $address) {
					$message->addTo(trim($address));				
				}
			}
		}
		
		$value = $email->get('cc');
		if ($value) {
			$arr = explode(';', $value);
			if (is_array($arr)) {
				foreach ($arr as $address) {
					$message->addCC(trim($address));
				}
			}
		}
		
		$value = $email->get('bcc');
		if ($value) {
			$arr = explode(';', $value);
			if (is_array($arr)) {
				foreach ($arr as $address) {
					$message->addBCC(trim($address));
				}
			}
		}

		$message->setSubject($email->get('name'));
		$message->setBody($email->get('body'));
		
		// TODO attachments

		try {
			$this->transport->send($message);
			$email->set('status', 'Sent');
			$email->set('dateSent', date("Y-m-d H:i:s"));
		} catch (\Exception $e) {
			throw new Error($e->getMessage(), 500);
		}
			
	}
}

