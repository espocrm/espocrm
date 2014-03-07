<?php

ob_start();
$result = array('success' => true, 'errorMsg' => '');

if (!$installer->isWritable()) {
	$result['success'] = false;
	$urls = $installer->getLastWritableError();
 	foreach ($urls as &$url) {
		$url = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$url;
 	}
	$result['errorMsg'] = $langs['Cannot write to files'].':<br>'.implode('<br>', $urls);
}

ob_clean();
echo json_encode($result);