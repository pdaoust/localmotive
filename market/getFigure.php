<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/misc.inc.php');

if (isset($_REQUEST['type'])) {
	switch ($_REQUEST['type']) {
		case 'payType':
			if (!isset($_REQUEST['payTypeID'])) die ();
			if (!(int) $_REQUEST['payTypeID']) die ();
			if (!$payType = new PayType ((int) $_REQUEST['payTypeID'])) die ();
			if (!isset($_REQUEST['amount'])) die ();
			if (!(int) $_REQUEST['amount'] && $_REQUEST['amount'] != '0') die ();
			printf('%.2f', $payType->getSurcharge((int) $_REQUEST['amount']));
			die ();
		case 'adjuster':
	}
}

?>
