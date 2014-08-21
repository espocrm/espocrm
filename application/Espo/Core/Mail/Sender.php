<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/

namespace Espo\Core\Mail;

use \Espo\Entities\Email;

use \Zend\Mime\Message as MimeMessage;
use \Zend\Mime\Part as MimePart;
use \Zend\Mime\Mime as Mime;

use \Zend\Mail\Message;
use \Zend\Mail\Transport\Smtp as SmtpTransport;
use \Zend\Mail\Transport\SmtpOptions;

use \Espo\Core\Exceptions\Error;

class Sender
{
	protected $config;

	protected $transport;

	protected $isGlobal = false;

	protected $params = array();

	public function __construct($config)
	{
		$this->config = $config;
		$this->useGlobal();
	}

	public function resetParams()
	{
		$this->params = array();
		return $this;
	}

	public function setParams(array $params = array())
	{
		$this->params = array_merge($this->params, $params);
		return $this;
	}

	public function useSmtp(array $params = array())
	{
		$this->isGlobal = false;
		$this->params = $params;

		$this->transport = new SmtpTransport();

		$opts = array(
			'name' => 'admin',
			'host' => $params['server'],
			'port' => $params['port'],
			'connection_config' => array()
		);
		if ($params['auth']) {
			$opts['connection_class'] = 'login';
			$opts['connection_config']['username'] = $params['username'];
			$opts['connection_config']['password'] = $params['password'];
		}
		if ($params['security']) {
			$opts['connection_config']['ssl'] = strtolower($params['security']);
		}

		$options = new SmtpOptions($opts);
		$this->transport->setOptions($options);

		return $this;
	}

	public function useGlobal()
	{
		$this->params = array();
		if ($this->isGlobal) {
			return $this;
		}

		$this->transport = new SmtpTransport();

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

		$this->isGlobal = true;

		return $this;
	}

	public function send(Email $email, $params = array())
	{
		$message = new Message();

		$config = $this->config;
		
		$params = $this->params + $params;

		if ($email->get('from')) {
			$fromName = null;
			if (!empty($params['fromName'])) {
				$fromName = $params['fromName'];
			} else {
				$fromName = $config->get('outboundEmailFromName');
			}
			$message->addFrom(trim($email->get('from')), $fromName);
		} else {
			if (!empty($params['fromAddress'])) {
				$fromAddress = $params['fromAddress'];
			} else {
				if (!$config->get('outboundEmailFromAddress')) {
					throw new Error('outboundEmailFromAddress is not specified in config.');
				}
				$fromAddress = $config->get('outboundEmailFromAddress');
			}

			if (!empty($params['fromName'])) {
				$fromName = $params['fromName'];
			} else {
				$fromName = $config->get('outboundEmailFromName');
			}

			$message->addFrom($fromAddress, $fromName);
		}
		
		if (!empty($params['replyToAddress'])) {
			$replyToName = null;
			if (!empty($params['replyToName'])) {
				$replyToName = $params['replyToName'];
			}
			$message->setReplyTo($params['replyToAddress'], $replyToName);
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

		$body = new MimeMessage;
		$parts = array();
	

		$bodyPart = new MimePart($email->getBodyPlainForSending());			
		$bodyPart->type = 'text/plain';
		$bodyPart->charset = 'utf-8';
		$parts[] = $bodyPart;

		
		if ($email->get('isHtml')) {
			$bodyPart = new MimePart($email->getBodyForSending());
			$bodyPart->type = 'text/html';
			$bodyPart->charset = 'utf-8';
			$parts[] = $bodyPart;
		}
		

		$aCollection = $email->get('attachments');
		if (!empty($aCollection)) {
			foreach ($aCollection as $a) {
				$fileName = 'data/upload/' . $a->id;
				$attachment = new MimePart(file_get_contents($fileName));
				$attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
				$attachment->encoding = Mime::ENCODING_BASE64;
				$attachment->filename = $a->get('name');
				if ($a->get('type')) {
					$attachment->type = $a->get('type');
				}
				$parts[] = $attachment;
			}
		}
		
		$aCollection = $email->getInlineAttachments();
		if (!empty($aCollection)) {
			foreach ($aCollection as $a) {
				$fileName = 'data/upload/' . $a->id;
				$attachment = new MimePart(file_get_contents($fileName));
				$attachment->disposition = Mime::DISPOSITION_INLINE;
				$attachment->encoding = Mime::ENCODING_BASE64;
				$attachment->id = $a->id;
				if ($a->get('type')) {
					$attachment->type = $a->get('type');
				}
				$parts[] = $attachment;
			}
		}

		$body->setParts($parts);
		$message->setBody($body);
		
		if ($email->get('isHtml')) {
			$message->getHeaders()->get('content-type')->setType('multipart/alternative');
		}

		try {
			$this->transport->send($message);
			$email->set('status', 'Sent');
			$email->set('dateSent', date("Y-m-d H:i:s"));
		} catch (\Exception $e) {
			throw new Error($e->getMessage(), 500);
		}

		$this->useGlobal();
	}
}

