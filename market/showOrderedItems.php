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
if ($user->personID != 1) restrictedError();

$pageTitle = 'Localmotive - Show ordered items';

$nextDeliveryDay = false;
if (isset($_REQUEST['dayPrint'])) {
	$nextDeliveryDay = myCheckDate((int) $_REQUEST['yearPrint'] . '-' . (int) $_REQUEST['monthPrint'] . '-' . (int) $_REQUEST['dayPrint']);
}
if (isset($_REQUEST['timestamp'])) $nextDeliveryDay = $_REQUEST['timestamp'];
$nextDeliveryDay = myCheckDate($nextDeliveryDay);

$deliveryDays = getDeliveryDays($nextDeliveryDay);

if (!count($deliveryDays)) {
	include ($path . '/header.tpl.php');
	$error = 'No delivery schedules!';
	$errorDetail = 'There are no delivery schedules for ' . strftime($config['humanDateFormat'], $nextDeliveryDay) . '. If this is not the date you chose, I guess you should contact Paul and tell him to fix it.';
	include ($path . '/market/templates/error.tpl.php');
	die ();
}

if (!$db->query('SELECT item.*, orderItems.totalOrdered FROM item, (SELECT orderItem.itemID, SUM(orderItem.quantityOrdered) as totalOrdered FROM orderItem, orders WHERE orderItem.orderID = orders.orderID AND orders.dateToDeliver = \'' . $db->cleanDate($nextDeliveryDay) . '\' AND orders.orderType & ' . O_BASE . ' = ' . O_SALE . ' GROUP BY orderItem.itemID) AS orderItems WHERE item.itemID = orderItems.itemID', true)) {
	databaseError($db);
	die ();
}
$orderItems = array ();
$quantities = array ();
while ($r = $db->getRow(F_RECORD)) {
	$thisItem = new Item ($r);
	$orderItems[$r->v('itemID')] = $thisItem;
	$quantities[$r->v('itemID')] = $r->v('totalOrdered');
}
$orderItems = $ItemMapper->insertOrderItems($orderItems);
array_shift($orderItems);

$noSidebars = true;
$fillContainer = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/showOrderedItems.tpl.php');
include ($path . '/footer.tpl.php');

?>
