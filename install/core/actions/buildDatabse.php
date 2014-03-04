<?php

$result = array('success' => true, 'errorMsg' => '');

$installer->buildDatabase();

ob_clean();
echo json_encode($result);