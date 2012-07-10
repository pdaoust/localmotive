<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');

if (!$user = tryLogin()) die ();


if (isset($_REQUEST['personID'])) {
	if (!$person = new Person ((int) ($_REQUEST['personID']))) {
		$error = 'No such person!';
		$errorDetail = 'This account doesn\'t exist.';
		include ($path . '/header' . $template . '.tpl.php');
		include ($path . '/market/templates/error.tpl.php');
		include ($path . '/footer' . $template . '.tpl.php');
		die();
	}
} else $person = &$user;

if (!$person->isIn($user)) {
	require_once ($path . '/header.tpl.php');
	$error = 'Access denied!';
	$errorDetail = 'You do not have access to this person\'s account details.';
	require_once ($path . '/market/templates/error.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}
$pageTitle = 'Order history for ' . $person->getLabel();

if (isset($_REQUEST['timestamp'])) $timestamp = roundDate(myCheckDate($_REQUEST['timestamp']));
if ($timestamp) {
	$dateStart = $timestamp;
	$dateEnd = $timestamp + T_DAY - 1;
} else {
	if (isset($_REQUEST['dateStart'])) $dateStart = myCheckDate($_REQUEST['dateStart']);
	if (!$dateStart) $dateStart = strtotime('-1 month', time());
	$dateStart = roundDate($dateStart);

	if (isset($_REQUEST['dateEnd'])) $dateEnd = myCheckDate($_REQUEST['dateEnd']);
	if (!$dateEnd) $dateEnd = time();
	$dateEnd = roundDate($dateEnd) + T_DAY - 1;
	if ($dateEnd < $dateStart) $dateEnd = $dateStart + T_DAY - 1;
}

if (isset($_REQUEST['recursive'])) {
	$recursive = ((bool) $_REQUEST['recursive'] ? true : false);
} else $recursive = false;
if (isset($_REQUEST['orderBy'])) {
	switch ($_REQUEST['orderBy']) {
		case 'dateToDeliver':
		case 'dateDelivered':
			$orderBy = $_REQUEST['orderBy'];
			break;
		case 'dateCompleted':
		default:
			$orderBy = 'dateCompleted';
	}
} else $orderBy = 'dateCompleted';

if (isset($_REQUEST['lostOnly'])) {
	if ($_REQUEST['lostOnly']) $lostOnly = true;
	else $lostOnly = false;
} else $lostOnly = false;

$orders = $person->getOrders($dateStart, $dateEnd, O_SALE, $recursive, $orderBy, $lostOnly);
$orders = array_reverse($orders);
$personIDs = array ();
$people = array ();
foreach ($orders as $thisOrder) {
	$personIDs[] = $thisOrder->personID;
}
if (count($personIDs)) {
	if (!$db->query('SELECT personID, contactName, groupName FROM person WHERE personID IN (' . implode(', ', array_unique($personIDs)) . ')', true)) {
		databaseError($db);
		die();
	}
	while ($r = $db->getRow(F_RECORD)) {
		$people[$r->v('personID')] = $r->v('contactName') . ($r->v('groupName') ? ', ' . $r->v('groupName') : null);
	}
}
// $orders = array_reverse($orders);
$style = (isset($_REQUEST['style']) ? ($_REQUEST['style'] == 'dialogue' ? 'dialogue' : 'normal') : 'normal');

$noSidebars = true;
$fillContainer = true;
include ($path . ($style == 'dialogue' ? '/market/templates/header_d.tpl.php' : '/header.tpl.php'));
include ($path . '/market/templates/orderHistory.tpl.php');
include ($path . ($style == 'dialogue' ? '/market/templates/footer_d.tpl.php' : '/footer.tpl.php'));
?>
