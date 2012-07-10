<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/price.inc.php');

$pageTitle = 'Item info';

if (isset($_REQUEST['itemID'])) {
	if (!$item = new Item((int) $_REQUEST['itemID'])) {
		$error = 'Cannot find that item';
		$errorDetail = 'That item doesn\'t appear to be in the database.';
		include ($path . '/market/templates/header_d.tpl.php');
		include ($path . '/market/templates/error.tpl.php');
		include ($path . '/market/templates/footer_d.tpl.php');
		die();
	}
	include ($path . '/market/templates/header_d.tpl.php');
	include ($path . '/market/templates/itemInfo.tpl.php');
	include ($path . '/market/templates/footer_d.tpl.php');
} else {
	$error = 'Cannot find that item';
	$errorDetail = 'That item doesn\'t appear to be in the database.';
	include ($path . '/market/templates/header_d.tpl.php');
	include ($path . '/market/templates/error.tpl.php');
	include ($path . '/market/templates/footer_d.tpl.php');
}
?>
