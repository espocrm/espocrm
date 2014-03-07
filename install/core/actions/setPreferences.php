<?php

ob_start();
$result = array('success' => false, 'errorMsg' => '');

if (!empty($_SESSION['install'])) {// required fields //['user-name']) && !empty($_SESSION['install']['user-pass'])) {
	$preferences = array(
		'dateFormat' => $_SESSION['install']['dateFormat'], 
		'timeFormat' => $_SESSION['install']['timeFormat'],
		'timeZone' => $_SESSION['install']['timeZone'],
		'weekStart' => $_SESSION['install']['weekStart'],
		'defaultCurrency' => $_SESSION['install']['defaultCurrency'],
		'thousandSeparator' => $_SESSION['install']['thousandSeparator'],
		'decimalMark' => $_SESSION['install']['decimalMark'],
		'language' => $_SESSION['install']['language'],
		'smtpServer' => $_SESSION['install']['smtpServer'],
		'smtpPort' => $_SESSION['install']['smtpPort'],
		'smtpAuth' => $_SESSION['install']['smtpAuth'],
		'smtpSecurity' => $_SESSION['install']['smtpSecurity'],
		'smtpUsername' => $_SESSION['install']['smtpUsername'],
		'smtpPassword' => $_SESSION['install']['smtpPassword'],
		'outboundEmailFromName' => $_SESSION['install']['outboundEmailFromName'],
		'outboundEmailFromAddress' => $_SESSION['install']['outboundEmailFromAddress'],
		'outboundEmailIsShared' => $_SESSION['install']['outboundEmailIsShared'],
	);
	$res = $installer->setPreferences($preferences);
	if (!empty($res)) {
		$result['success'] = true;
	}
	else {
		$result['success'] = false;
		$result['errorMsg'] = 'Cannot save preferences';
	}
}
else {
	$result['success'] = false;
	$result['errorMsg'] = 'Cannot save preferences';
}

ob_clean();
echo json_encode($result);
