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

$deliveryDay = false;
if (isset($_REQUEST['dayConfirm'])) {
	$nextDeliveryDay = myCheckDate((int) $_REQUEST['yearConfirm'] . '-' . (int) $_REQUEST['monthConfirm'] . '-' . (int) $_REQUEST['dayConfirm']);
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
$pageTitle = 'Localmotive - Confirm orders';

$orders = getWaitingOrders($nextDeliveryDay, false);
$people = array ();
foreach ($orders as $thisOrder) {
	$orders[$thisOrder->orderID]->complete();
	if (!isset($people[$thisOrder->personID])) {
		$people[$thisOrder->personID] = $thisOrder->getPerson();
	}
}

$showBlankFields = true;
$noSidebars = true;
$fillContainer = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/createRecurringOrders.tpl.php');
include ($path . '/footer.tpl.php');

?>
