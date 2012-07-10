<?php

function createSchedule ($nextDeliveryDay, $noRoute = true, $person = null) {
	$time = microtime(true);
	global $deliveryDays, $logger;
	$deliveryDays = getDeliveryDays(false, $nextDeliveryDay);
	// if (!count($deliveryDays)) return false;
	if (!$person) $person = $GLOBALS['user'];
	if (!is_object($person)) $person = $GLOBALS['user'];
	if (get_class($person) != 'Person') $person = $GLOBALS['user'];
	$ordersU = $person->getOrders($nextDeliveryDay, null, O_SALE, true, 'orderID', false, false, true);
	global $sections, $entries, $orders, $people;
	foreach ($ordersU as $k => $v) {
		if (!isset($orders[$v->personID])) $orders[$v->personID] = array ();
		$orders[$v->personID][$v->orderID] = $v;
	}
	$sections[0] = 'Master Delivery Sheet';
	$entries[0] = array ();
	foreach ($deliveryDays as $dd) {
		$routes = $dd->getRoutes();
		if (is_array($routes)) {
			foreach ($routes as $rt) {
				$entries[0][] = array ('type' => 0, 'label' => 'Route: ' . $rt->label);
				$ppl = $rt->getPeople();
				if (is_array($ppl)) {
					if (count($ppl)) {
						foreach ($ppl as $p) {
							if ($p->personType & P_DEPOT) {
								$thisEntries = array ();
								$hasOrders = 0;
								if ($ppl2 = $p->getChildren('contactName', true, array ('personType' => P_CUSTOMER + P_SUPPLIER))) {
									foreach ($ppl2 as $p2) {
										if (getPersonOrders($p2)) {
											// $orders[$p2->personID] = $thisOrders;
											$hasOrders ++;
											$thisEntries[] = array ('type' => P_CUSTOMER, 'label' => $p2->contactName . ($p2->groupName ? ', ' . $p2->groupName : null), 'personID' => $p2->personID, 'balance' => $p2->getBalance());
			$people[$p2->personID] = $p2;
										}
									}
								}
								if ($hasOrders) {
									$entries[0][] = array ('type' => P_DEPOT, 'label' => 'Depot: ' . ($p->groupName ? $p->groupName . ', ' . $p->contactName : $p->contactName), 'personID' => $p->personID, 'bins' => $hasOrders);
									$entries[0] = array_merge($entries[0], $thisEntries);
									$entries[$p->personID] = $thisEntries;
									$sections[$p->personID] = 'Depot: ' . ($p->groupName ? $p->groupName . ', ' . $p->contactName : $p->contactName);
								}
							} else if ($p->personType & P_CUSTOMER) {
								if (getPersonOrders($p)) {
									// $orders[$p->personID] = $thisOrders;
									$entries[0][] = array ('type' => P_CUSTOMER, 'label' => $p->contactName . ($p->groupName ? ', ' . $p->groupName : null), 'personID' => $p->personID, 'balance' => $p->getBalance());
									$people[$p->personID] = $p;
								}
							}
						}
					} else $entries[0][] = array ('type' => -1, 'label' => 'No entries');
				} else $entries[0][] = array ('type' => -1, 'label' => 'No entries');
			}
		}
	}
	// if we're supposed to include any non-deliverables - ticket sales, sign-up fees, etc
	if ($noRoute) {
		$sectionID = max(array_keys($sections)) + 1;
		$sections[$sectionID] = 'Not for delivery';
		$entries[$sectionID] = array ();
		$entries[$sectionID][] = array ('type' => 0, 'label' => 'Not for delivery');
		global $db;
		if (!$db->query('SELECT * FROM orders WHERE !(orderType & ' . (O_TEMPLATE | O_DELIVER) . ') AND (dateCompleted BETWEEN "' . $db->cleanDate($nextDeliveryDay) . '" AND "' . $db->cleanDate($nextDeliveryDay + T_DAY - 1) . '") ORDER BY personID', true)) {
			dbError($db);
			die();
		}
		$noRouteOrders = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$noRouteOrders[$r->v('orderID')] = $r;
		}
		foreach ($noRouteOrders as $k => $v) {
			$v = new Order ($v);
			if (!isset($orders[$v->personID])) {
				$orders[$v->personID] = array ();
				$p = $v->getPerson();
				$entries[$sectionID][] = array ('type' => P_CUSTOMER, 'label' => $p->contactName . ($p->groupName ? ', ' . $p->groupName : null), 'personID' => $p->personID, 'balance' => $p->getBalance());
				$people[$p->personID] = $p;
			}
			if (!isset($orders[$v->personID][$v->orderID])) $orders[$v->personID][$v->orderID] = $v;
		}
		$entries[0] = array_merge($entries[0], $entries[$sectionID]);
	}
	$time = microtime(true) - $time;
	global $logger;
	return true;
}

function getPersonOrders ($person) {
	global $orders;
	// $orders = $person->getOrders($nextDeliveryDay, $nextDeliveryDay + T_DAY - 1, O_SALE, false, 'orderID', false, 'dateToDeliver', false, true);
	if (isset($orders[$person->personID])) {
		if (count($orders[$person->personID])) {
			$orders2 = $person->getOrdersBefore(min(array_keys($orders[$person->personID])));
			if (is_array($orders2)) $orders[$person->personID] = array_merge($orders2, $orders[$person->personID]);
		}
		return true;
	} else return false;
}

?>
