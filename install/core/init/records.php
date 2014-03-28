<?php

return array(
	'EmailTemplate' => array(
		0 => array(
			'name' => 'Case-to-Email auto-reply',
			'subject' => 'Case has been created',
			'body' => '<p>Hi {Person.name}</p><p>Case \'<span style="line-height: 1.36;">{Case.name}\'</span><span style="line-height: 1.36;">&nbsp;has been created with number \'{Case.number}\' and a</span><span style="line-height: 1.36;">ssigned to {User.name}.</span></p>',
			'isHtml ' => '1',
		),
	),
	'ScheduledJob' => array(
		0 => array(
			'name' => 'Check Inbound Emails',
			'job' => 'CheckInboundEmails',
			'status' => 'Active',
			'scheduling' => '/10 * * * *',
		),
	),
);