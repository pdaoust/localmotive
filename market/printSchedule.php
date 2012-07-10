<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/createSchedule.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1) restrictedError();

$nextDeliveryDay = false;
if (isset($_REQUEST['dayPrint'])) {
	$nextDeliveryDay = myCheckDate((int) $_REQUEST['yearPrint'] . '-' . (int) $_REQUEST['monthPrint'] . '-' . (int) $_REQUEST['dayPrint']);
}
if (isset($_REQUEST['timestamp'])) $nextDeliveryDay = $_REQUEST['timestamp'];
$nextDeliveryDay = myCheckDate($nextDeliveryDay);
$nextDeliveryDay = roundDate($nextDeliveryDay);

$deliveryDays = array ();
$sections = array ();
$entries = array ();
$orders = array ();
$people = array ();

if (!createSchedule($nextDeliveryDay)) {
	include ($path . '/header.tpl.php');
	$error = 'No delivery schedules!';
	$errorDetail = 'There are no delivery schedules for ' . strftime($config['humanDateFormat'], $nextDeliveryDay) . '. If this is not the date you chose, I guess you should contact Paul and tell him to fix it.';
	include ($path . '/market/templates/error.tpl.php');
	die ();
}

$pageTitle = 'Localmotive - Print out delivery schedule';

$showBlankFields = true;
include ($path . '/market/templates/header_d.tpl.php');
include ($path . '/market/templates/printSchedule.tpl.php');
include ($path . '/market/templates/footer_d.tpl.php');

?>
