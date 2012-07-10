<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/calendar.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');

$pageTitle = 'Localmotive - Calendar';

if (!$user = tryLogin()) die ();
if ($user->personID != 1) {
	include ($path . '/header.tpl.php');
	$loginError = 'This area is restricted to administrators. Please enter the correct administrator login info below.';
	include ($path . '/market/templates/login.tpl.php');
	include ($path . '/footer.tpl.php');
	die ();
}

if (!isset($embedded)) $embedded = false;
if (isset($_REQUEST['ajax'])) $embedded = true;

if (isset($date)) {
	$date = myCheckDate($date);
	$date = roundDate($date);
	if (!$date) $date = time ();
} else if (isset($_REQUEST['date'])) {
	$date = myCheckDate($_REQUEST['date']);
	$date = roundDate($date);
	if (!$date) $date = time ();
} else $date = time ();
$di = getdate($date);

$prevMonth = mktime (
	$di['hours'],
	$di['minutes'],
	$di['seconds'],
	($di['mon'] == 1 ? 12 : $di['mon'] - 1),
	$di['mday'],
	($di['mon'] == 1 ? ($di['year'] - 1) : $di['year'])
);
$nextMonth = mktime (
	$di['hours'],
	$di['minutes'],
	$di['seconds'],
	($di['mon'] == 12 ? 1 : ($di['mon'] + 1)),
	$di['mday'],
	($di['mon'] == 12 ? ($di['year'] + 1) : $di['year'])
);

if (!$db->query('SELECT orderID, dateToDeliver, dateCompleted FROM orders WHERE !(orderType & ' . O_TEMPLATE . ') AND dateToDeliver BETWEEN \'' . $db->cleanDate(mktime(0, 0, 0, $di['mon'], 1, $di['year'])) . '\' AND \'' . $db->cleanDate(mktime(0, 0, 0, $di['mon'] + 1, 1, $di['year']) - 1) . '\' and orderType & '.O_DELIVER, true)) {
	databaseError($db);
	die ();
}
$dayTally = array ();
$unconfirmed = array ();
while ($r = $db->getRow(F_RECORD)) {
	$dateToDeliver = strtotime($r->v('dateToDeliver'));
	if (!isset($dayTally[$dateToDeliver])) $dayTally[$dateToDeliver] = 0;
	if (!$r->v('dateCompleted')) {
		if (!isset($unconfirmed[$dateToDeliver])) $unconfirmed[$dateToDeliver] = 0;
		$unconfirmed[$dateToDeliver] ++;
	}
	$dayTally[$dateToDeliver] ++;
}
$days = array ();
foreach ($dayTally as $k => $v) {
	$d = (int) strftime('%d', $k);
	$content = '<div style="clear: both;"><a href="orderHistory.php?timestamp=' . $k . '&orderBy=dateToDeliver&recursive=1" class="iconNum blue" title="View orders"><img src="img/bin.png" class="icon" alt="View orders"/> ' . $v . '</a>' . (isset($unconfirmed[$k]) ? ' <a href="confirmOrders.php?timestamp=' . $k . '" class="iconNum green" title="Confirm orders"><img src="img/y.png" alt="Confirm orders" class="icon"/> ' . $unconfirmed[$k] . '</a>' : null) . '<br/><a href="showOrderedItems.php?timestamp=' . $k . '" title="Show ordered items"><img src="img/itm.png" alt="Show ordered items" class="icon"/></a> <a href="printSchedule.php?timestamp=' . $k . '" title="Print schedule"><img src="img/rt.png" alt="Print schedule" class="icon"/></a> <a href="manageAccounts.php?viewBy=deliveryDay&deliveryDay=' . $k . '" title="Accounting for this day\'s orders"><img src="img/act.png" class="icon" alt="Accounting"/></a></div>';
	$days[$d] = array (null, 'activity', $content);
}

if (!$embedded) include ($path . '/header.tpl.php');
include ($path . '/market/templates/calendar.tpl.php');
if (!$embedded) include ($path . '/footer.tpl.php');

?>
