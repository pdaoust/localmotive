<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');


if (!$user = tryLogin()) die ();
if ($user->personID != 1) {
	if ($ajax) {
		echo '0';
	} else restrictedError();
	die ();
}

$pageTitle = 'Localmotive - Manage Routes';
$noSidebars = true;
$fillContainer = true;
$verifyPersonFields = false;
$errorPersonFields = array ();
$verifyRouteFields = false;
$errorRouteFields = array ();

function outputDeliveryDayData ($deliveryDay, $status, $extras = null) {
	if (!is_object($deliveryDay)) return false;
	if (get_class($deliveryDay) != 'DeliveryDay') return false;
	if (!($deliveryDay->period % T_WEEK)) $mult = array (T_WEEK, 1);
	else if (!($deliveryDay->period % T_DAY)) $mult = array (T_DAY, 0);
	else if (!($deliveryDay->period % T_YEAR)) $mult = array (T_YEAR, 3);
	else if (!($deliveryDay->period % T_MONTH)) $mult = array (T_MONTH, 2);
	else $mult = null;
	if ($mult) {
		$period = $deliveryDay->period / $mult[0];
		$mult = $mult[1];
	} else {
		$period = null;
		$mult = null;
	}
	$dateStartA = getdate($deliveryDay->dateStart);
	$dateStartJ = array (
		'year' => $dateStartA['year'],
		'month' => $dateStartA['mon'] - 1,
		'day' => $dateStartA['mday']
	);
	$out = array (
		'status' => (int) $status,
		'deliveryDayID' => (int) $deliveryDay->deliveryDayID,
		'dateStart' => strftime('%d %B %Y', $deliveryDay->dateStart),
		'dateStartJ'=> $dateStartJ,
		'period' => $period,
		'mult' => $mult,
		'label' => jsSafeString($deliveryDay->label),
		'cutoffDay' => (int) $deliveryDay->cutoffDay,
		'active' => (bool) $deliveryDay->active,
		'extras' => $extras
	);
	echo json_encode($out);
}

function outputRouteData ($route, $status, $extras = null) {
	if (!is_object($route)) return false;
	if (get_class($route) != 'Route') return false;
	$out = array (
		'status' => (int) $status,
		'routeID' => $route->routeID,
		'label' => jsSafeString($route->label),
		'active' => (bool) $route->active
	);
	echo json_encode($out);
}

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'loadDeliveryDay':
			$ajax = true;
			if (isset($_REQUEST['deliveryDayID'])) {
				$deliveryDay = new DeliveryDay ((int) $_REQUEST['deliveryDayID']);
				if (!$deliveryDay->deliveryDayID && (int) $_REQUEST['deliveryDayID']) echo '{"status": 0}';
				else outputDeliveryDayData($deliveryDay, 1);
			} else echo '{"status": 0}';
			die ();
			break;
		case 'editDeliveryDay':
			if (isset($_REQUEST['deliveryDayID'])) {
				$new = false;
				if ($db->query('SELECT deliveryDayID FROM deliveryDay WHERE deliveryDayID = ' . (int) $_REQUEST['deliveryDayID'])) {
					if (!$db->getRow()) $new = true;
				}
				$deliveryDay = new DeliveryDay ((int) $_REQUEST['deliveryDayID']);
				$deliveryDay->label = $_REQUEST['label'];
				$deliveryDay->dateStart = strtotime($_REQUEST['dateStart']);
				$deliveryDay->period = (int) $_REQUEST['period'] * (int) $_REQUEST['mult'];
				$deliveryDay->active = ($_REQUEST['active'] ? true : false);
				$deliveryDay->cutoffDay = (int) $_REQUEST['cutoffDay'];
				if (!$deliveryDay->save()) {
					if ($ajax) {
						if ($deliveryDay->getError() == E_INVALID_DATA) $status = -1;
						else $status = 0;
					}
				} else if ($ajax) {
					if ($new) $status = 2;
					else $status = 1;
				}
				if ($ajax) {
					outputDeliveryDayData($deliveryDay, $status, ($status == -1 ? array ('errorFields' => $deliveryDay->getErrorDetail()) : null));
					die ();
				}
			} else if ($ajax) {
				echo '{"status"": 0}';
				die ();
			}
			break;
		case 'deleteDeliveryDay':
			if (isset($_REQUEST['deliveryDayID'])) {
				$deliveryDay = new DeliveryDay ((int) $_REQUEST['deliveryDayID']);
				$status = $deliveryDay->delete();
				if ($ajax) {
					echo '{"status": ' . ($status ? 'true' : 'false') . '}';
					die ();
				}
			}
			break;
		case 'loadRoute':
			$ajax = true;
			if (isset($_REQUEST['routeID'])) {
				$route = new Route ((int) $_REQUEST['routeID']);
				if (!$route->routeID && (int) $_REQUEST['routeID']) echo '{"status": 0}';
				else outputRouteData($route, 1);
			} else echo '{"status": 0}';
			die ();
			break;
		case 'editRoute':
			if (isset($_REQUEST['routeID'])) {
				$editRoute = new Route ((int) $_REQUEST['routeID']);
				$editRoute->label = $_REQUEST['label'];
				$editRoute->active = ($_REQUEST['active'] ? true : false);
				if (!$editRoute->save()) {
					if ($ajax) {
						if ($editRoute->getError() == E_INVALID_DATA) $status = -1;
						else $status = 0;
					}
				} else if ($ajax) {
					if (!(int) $_REQUEST['routeID']) $status = 2;
					else $status = 1;
				}
				if ($ajax) {
					outputRouteData($editRoute, $status, ($editRoute->getError() == E_INVALID_DATA ? array ('errorFields' => $editRoute->getErrorDetail()) : null));
					die ();
				}
			} else if ($ajax) {
				echo '{"status": 0}';
				die ();
			}
			break;
		case 'deleteRoute':
			$status = false;
			if (isset($_REQUEST['routeID'])) {
				$editRoute = new Route ((int) $_REQUEST['routeID']);
				$status = $editRoute->delete();
			}
			if ($ajax) {
				echo '{"status": ' . (int) $status . '}';
				die ();
			}
			break;
		case 'addDeliveryDay':
		case 'addRoute':
			if (isset($_REQUEST['routeID'])) {
				if ((int) $_REQUEST['routeID'] && (int) $_REQUEST['deliveryDayID']) {
					$route = new Route ((int) $_REQUEST['routeID']);
					$status = $route->addRouteDay((int) $_REQUEST['deliveryDayID']);
					if ($ajax) {
						echo json_encode(array (
							'status' => $status,
							'active' => $route->active,
							'label' => $route->label,
							'routeID' => $route->routeID,
							'deliveryDayID' => (int) $_REQUEST['deliveryDayID']
						));
						die ();
					}
				}
			}
			if ($ajax) {
				echo '{"status": false}';
				die;
			}
			break;
		case 'removeRoute':
			if (isset($_REQUEST['routeID'])) {
				if ((int) $_REQUEST['routeID'] && (int) $_REQUEST['deliveryDayID']) {
					$route = new Route ((int) $_REQUEST['routeID']);
					$status = $route->deleteRouteDay((int) $_REQUEST['deliveryDayID']);
					if ($ajax) {
						echo '{"status":' . ($status ? 'true' : 'false') . '}';
						die ();
					}
				}
			}
			break;
		case 'moveRoute':
			if (isset($_REQUEST['routeID']) && isset($_REQUEST['deliveryDayID'])) {
				$route = new Route ((int) $_REQUEST['routeID']);
				if ($routeDay = $route->routeDays[(int) $_REQUEST['deliveryDayID']]) {
					switch ($_REQUEST['direction']) {
						case 'up':
							if ($routeDay->moveUp()) $d = -1;
							else $d = 0;
							break;
						case 'down':
							if ($routeDay->moveDown()) $d = 1;
							else $d = 0;
					}
					if ($ajax) {
						if ($d) {
							echo json_encode(array (
								'deliveryDayID' => $routeDay->deliveryDayID,
								'routeID' => $routeDay->routeID,
								'direction' => $d,
								'deliverySlot' => $routeDay->getDeliverySlot(),
								'lastSlot' => $routeDay->getLastSlot()
							));
						} else echo '{"status": 0}';
						die;
					}	
				} else if ($ajax) {
					echo '{"status": 0}';
					die ();
				}
			}
			break;
		case 'setRoute':
			if (isset($_POST['personID'])) {
				if ((int) $_POST['personID'] && (int) $_POST['routeID']) {
					$person = new Person ((int) $_POST['personID']);
					$status = $person->setRoute((int) $_POST['routeID']);
				} else $status = 0;
			} else $status = 0;
			if ($ajax) {
				echo json_encode(array(
					'status' => $status,
					'personID' => (int) $_POST['personID'],
					'routeID' => (int) $_POST['routeID']
				));
				die ();
			}
			break;
		case 'removePerson':
			if (isset($_REQUEST['personID'])) {
				if ((int) $_REQUEST['personID']) {
					$person = new Person ((int) $_REQUEST['personID']);
					$status = $person->setRoute(0);
					if ($ajax) {
						echo '{"status":' . ($status ? 'true' : 'false') . '}';
						die ();
					}
				}
			}
			break;
		case 'moveNode':
			if (isset($_REQUEST['nodeID']) && isset($_REQUEST['nodeType'])) {
				switch ($_REQUEST['nodeType']) {
					case 'person':
						$node = new Person ((int) $_REQUEST['nodeID']);
						break;
					case 'route':
						if (isset($_REQUEST['deliveryDayID'])) $node = new RouteDay ((int) $_REQUEST['nodeID'], (int) $_REQUEST['deliveryDayID']);
						break;
					default:
						$node = false;
				}
				if ($node) {
					if (isset($_REQUEST['direction'])) {
						switch ($_REQUEST['direction']) {
							case 'up':
								$status = $node->moveUp();
								break;
							case 'down':
								$status = $node->moveDown();
								break;
							default: $status = 0;
						}
					} else if (isset($_REQUEST['newSlot'])) {
						if ((int) $_REQUEST['newSlot']) {
							$status = $node->setDeliverySlot((int) $_REQUEST['newSlot']);
						} else $status = 0;
					} else $status = 0;
				} else $status = 0;
			} else $status = 0;
			if ($ajax) {
				echo '{"status":' . ($status ? 'true, "newSlot":' . (int) $node->getDeliverySlot() : 'false') . '}';
				die ();
			}
	}
	redirectThisPage('manageRoutes.php' . ($viewBy ? '?viewBy=' . $viewBy : ''));
}

$routes = array ();

if (!$db->query('SELECT * FROM deliveryDay ORDER BY deliveryDayID')) {
	databaseError($db);
	die ();
}
$deliveryDays = array ();
while ($r = $db->getRow(F_RECORD)) {
	$deliveryDays[$r->v('deliveryDayID')] = new DeliveryDay ($r);
}

if (!$db->query('SELECT * FROM routeDay')) {
	databaseError($db);
	die ();
}
$routeDays = array ();
while ($r = $db->getRow(F_RECORD)) {
	$thisRouteDay = new RouteDay ($r);
	if (!isset($routeDays[$thisRouteDay->routeID])) $routeDays[$thisRouteDay->routeID] = array ();
	$routeDays[$thisRouteDay->routeID][$thisRouteDay->deliveryDayID] = $thisRouteDay;
}
if (!$db->query('SELECT * FROM route ORDER BY label')) {
	databaseError($db);
	die ();
}
while ($r = $db->getRow(F_RECORD)) {
	if (isset($routeDays[$r->v('routeID')])) $r->r['routeDays'] = $routeDays[$r->v('routeID')];
	$routes[$r->v('routeID')] = new Route ($r);
}
$routes[0] = new Route (null);
$people = array ();
foreach ($routes as $thisRoute) {
	$people[$thisRoute->routeID] = $thisRoute->getPeople();
}

include ($path . '/header.tpl.php');
include ($path . '/market/templates/manageRoutes.tpl.php');
include ($path . '/footer.tpl.php');

?>
