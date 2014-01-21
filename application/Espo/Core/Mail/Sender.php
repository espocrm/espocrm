<?php

namespace Espo\Core\Mail;

use \Espo\Entities\Email;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;

class Sender
{
	protected $config;

	protected $transport;

	public function __construct($config)
	{
		$this->config = $config;
		$this->trasport = new SmtpTransport();
		$this->setupGlobal();
	}

	protected function setupGlobal()
	{
		$config = $this->config;

		$opts = array(
			'name' => 'admin',
			'host' => $config->get('smtpServer'),
			'port' => $config->get('smtpPort'),
			'connection_config' => array();
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
		$transport->setOptions($options);

		return $this;
	}

	public function send(Email $email)
	{
		$message = new Message();

		if ($email->get('fromEmailAddressName')) {
			$message->addFrom($email->get('fromEmailAddressName'));
		} else {
			if (!$config->get('outboundEmailFromAddress')) {
				throw new Error('outboundEmailFromAddress is not specified in config.');
			}
			$message->addFrom($email->get('outboundEmailFromAddress'), $email->get('outboundEmailFromName'));
		}

		$toIds = $email->get('toEmailAddressesIds');
		if (is_array($toIds)) {
			$hash = $email->get('toEmailAddressesNames');
			foreach ($toIds as $id) {
				$address = $hash[$id];
				if (!empty($address)) {
					$message->addTo($address);
				}
			}
		}

		$ccIds = $email->get('ccEmailAddressesIds');
		if (is_array($toIds)) {
			$hash = $email->get('ccEmailAddressesIds');
			foreach ($ccIds as $id) {
				$address = $hash[$id];
				if (!empty($address)) {
					$message->addCC($address);
				}
			}
		}

		$bccIds = $email->get('bccEmailAddressesIds');
		if (is_array($toIds)) {
			$hash = $email->get('bccEmailAddressesIds');
			foreach ($bccIds as $id) {
				$address = $hash[$id];
				if (!empty($address)) {
					$message->addBCC($address);
				}
			}
		}

		$message->setSubject($email->get('name'));
		$message->setBody($email->get('body'));
		
		// TODO attachments

		return $this->transport->send($message);
	}
}

