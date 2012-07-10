<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1) {
	require_once ($path . '/header.tpl.php');
	$loginError = 'This area is restricted to administrators. Please enter the correct administrator login info below.';
	require_once ($path . '/market/templates/login.tpl.php');
	require_once ($path . '/footer.tpl.php');
	die ();
}

$pageTitle = 'Localmotive - Create recurring orders';

	$q = 'SELECT * FROM orders WHERE orderType & ' . O_TEMPLATE . ' AND (dateCompleted IS NULL OR dateCompleted >= \'' . $db->cleanDate(roundDate($dateStart)) . '\' OR (dateCompleted <= \'' . $db->cleanDate(roundDate($dateStart + T_WEEK)) . '\' AND dateResume >= \'' . $db->cleanDate(roundDate($dateStart)) . '\'))';
	if (!$db->query($q)) {
		databaseError($db);
		die();
	}
	$orderInfo = array ();
	$orders = array ();
	while ($orderData = $db->getRow()) {
		$orderInfo[$orderData['orderid']] = $orderData;
	}
	foreach ($orderInfo as $thisOrderInfo) {
		$thisOrder = new Order ($thisOrderInfo);
		$orders[$thisOrder->orderID] = $thisOrder;
	}
	if (count($orders)) {
		/* $q = 'SELECT * FROM orderItem JOIN item ON orderItem.itemID = item.itemID WHERE orderItem.orderID in (' . implode(', ', array_keys($orders)) . ')';
		if (!$db->query($q)) {
			databaseError($db);
			die();
		}
		while ($orderItemData = $db->getRow()) {
			if ($orderItemData['tax']) $orderItemData['tax'] = explode(',', $orderItemData['tax']);
			else $orderItemData['tax'] = array ();
			if ($thisOrderItem = new OrderItem ($orderItemData)) $orders[$orderItemData['orderID']]->orderItems[$orderItemData['itemID']] = $thisOrderItem;
		} */
		$q = 'SELECT person.personID AS personID, person.balance AS balance, person.contactName AS contactName FROM person, orders WHERE person.personID = orders.personID AND orders.orderID in (' . implode(', ', array_keys($orders)) . ')';
		if (!$db->query($q)) {
			databaseError($db);
			die();
		}
		$people = array ();
		while ($peopleInfo = $db->getRow()) {
			$people[$peopleInfo['personid']] = array ('balancePrev' => $peopleInfo['balance'], 'balanceAfter' => 0, 'contactName' => $peopleInfo['contactname']);
		}
		/* echo '<pre>';
		print_r($orders);
		echo '</pre>'; */
		if (!$dateStart) $dateStart = roundDate(time(), T_WEEK) + T_WEEK;
		$dateEnd = $dateStart + T_WEEK - 1;
		foreach ($orders as $thisOrder) {
			$orders[$thisOrder->orderID]->replicate($dateStart, $dateEnd);
		}
		$q = 'SELECT person.personID AS personID, person.balance AS balance FROM person, orders WHERE person.personID = orders.personID AND orders.orderID in (' . implode(', ', array_keys($orders)) . ')';
		if (!$db->query($q)) {
			databaseError($db);
			die();
		}
		while ($peopleInfo = $db->getRow()) {
			$people[$peopleInfo['personid']]['balanceAfter'] = $peopleInfo['balance'];
		}
	}
// }

$noSidebars = true;
$fillContainer = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/createRecurringOrders.tpl.php');
include ($path . '/footer.tpl.php');

?>
