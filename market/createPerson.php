<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1) restrictedError();

if (isset($_REQUEST['parentID'])) {
	if ((int) $_REQUEST['parentID']) {
		$parent = new Person ((int) $_REQUEST['parentID']);
		if ($parent->personID) {
			if (!$parent->isIn($user)) $parent = $user;
		} else $parent = $user;
	} else $parent = $user;
} else $parent = $user;

$pageTitle = 'Localmotive - Create person in ' . $parent->getLabel();
$verifyPersonFields = false;
$errorPersonFields = array ();
$verifyRouteFields = false;
$errorRouteFields = array ();

$routes = array ();
$routes[] = new Route ();

if (!$db->query('SELECT * FROM deliveryDay')) {
	databaseError($db);
	die ();
}
$deliveryDays = array ();
while ($thisDeliveryDayData = $db->getRow()) {
	$thisDeliveryDay = new DeliveryDay ($thisDeliveryDayData);
	$deliveryDays[$thisDeliveryDay->deliveryDayID] = $thisDeliveryDay;
}

if (!$db->query('SELECT * FROM routeDay')) {
	databaseError($db);
	die ();
}
$routeDays = array ();
while ($thisRouteDayData = $db->getRow()) {
	$thisRouteDay = new RouteDay ($thisRouteDayData);
	if (!isset($routeDays[$thisRouteDay->routeID])) $routeDays[$thisRouteDay->routeID] = array ();
	$routeDays[$thisRouteDay->routeID][$thisRouteDay->deliveryDayID] = $thisRouteDay;
}
if (!$db->query('SELECT * FROM route ORDER BY label')) {
	databaseError($db);
	die ();
}
while ($thisRouteData = $db->getRow()) {
	if (isset($routeDays[$thisRouteData['routeID']])) $thisRouteData['deliveryDays'] = $routeDays[$thisRouteData['routeID']];
	$thisRoute = new Route ($thisRouteData);
	$routes[] = $thisRoute;
}

include ($path . '/header.tpl.php');
include ($path . '/market/templates/createPerson.tpl.php');
include ($path . '/footer.tpl.php');

?>
