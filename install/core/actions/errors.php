<?php

$errors = array();
if (!empty($_REQUEST['desc'])) {
	$errors = str_replace('^^', '<br>', $_REQUEST['desc']);
	$smarty->assign('errors', $errors);
}