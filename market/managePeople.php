<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
include ($path . '/market/templates/noderow.tpl.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1) {
	if ($ajax) {
		echo '0';
		$json = true;
	} else restrictedError();
	die ();
}

$pageTitle = 'Localmotive - Manage people';
$verifyPersonFields = false;
$errorPersonFields = array ();
$verifyRouteFields = false;
$errorGroupFields = array ();
$verifyRouteFields = false;
$errorRouteFields = array ();

if (isset($_REQUEST['sortOrder'])) {
	switch ($_REQUEST['sortOrder']) {
		case 'stars':
			$sortOrder = 'stars DESC';
			break;
		case 'groupName':
			$sortOrder = 'groupName, node.contactName';
			break;
		case 'city':
			$sortOrder = 'city';
			break;
		case 'route':
			$sortOrder = 'routeID, node.deliverySlot';
			break;
		case 'bins':
			$sortOrder = 'bins DESC';
			break;
		case 'coldpacks':
			$sortOrder = 'coldpacks DESC';
			break;
		case 'bottles':
			$sortOrder = 'bottles DESC';
			break;
		case 'email':
			$sortOrder = 'email';
			break;
		case 'owing':
			$sortOrder = 'balance';
			break;
		case 'credit':
			$sortOrder = 'balance DESC';
			break;
		case 'tree':
			$sortOrder = null;
		case 'contactName':
		default:
			$sortOrder = 'contactName';
	}
} else $sortOrder = 'contactName';

if (isset($_REQUEST['nodeID'])) {
	if ((int) $_REQUEST['nodeID']) {
		$node = new Person ((int) $_REQUEST['nodeID']);
		if ($node->personID) {
			if (!$node->isIn($user)) $node = $user;
		} else $node = $user;
	} else $node = $user;
} else $node = $user;

function outputPersonData ($person, $status, $extras = null) {
	if (!is_array($extras)) $extras = array();
	echo json_encode(array_merge(array(
		'status' => (int) $status,
		'personID' => (int) $person->personID,
		'parentID' => (int) $person->getParentID(),
		'contactName' => $person->contactName,
		'groupName' => $person->groupName,
		'label' => $person->getLabel(),
		'personType' => (int) $person->personType,
		'phone' => $person->phone,
		'email' => $person->email,
		'privateKey' => $person->privateKey,
		'payTypeIDs' => $person->payTypeIDs,
		'payTypeIDsParent' => $person->getPayTypeIDs(false),
		'payTypeID' => $person->payTypeID,
		'payTypeIDParent' => $person->getPayTypeID(false),
		'minOrder' => $person->minOrder,
		'minOrderParent' => $person->getMinOrder(false),
		'minOrderDeliver' => $person->minOrderDeliver,
		'minOrderDeliverParent' => $person->getMinOrderDeliver(false),
		'bulkDiscount' => $person->bulkDiscount,
		'bulkDiscountParent' => $person->getBulkDiscount(false),
		'bulkDiscountQuantity' => $person->bulkDiscountQuantity,
		'bulkDiscountQuantityParent' => $person->getBulkDiscountQuantity(false),
		'maxStars' => $person->maxStars,
		'maxStarsParent' => $person->getMaxStars(false),
		'deposit' => $person->deposit,
		'depositParent' => $person->getDeposit(false),
		'credit' => $person->credit,
		'creditParent' => $person->getCredit(false),
		'customCancelsRecurring' => $person->customCancelsRecurring,
		'customCancelsRecurringParent' => $person->getCustomCancelsRecurring(false),
		'canCustomOrder' => $person->canCustomOrder,
		'canCustomOrderParent' => $person->getCanCustomOrder(false),
		'stars' => $person->stars,
		'recent' => ((bool) $person->recent ? 'true' : 'false'),
		'notes' => $person->notes,
		'description' => $person->description,
		'website' => $person->website,
		//'compost' => (bool) $person->compost,
		'active' => (bool) $person->active,
		'isActive' => (bool) $person->isActive(),
		'routeID' => $person->getRouteID(false),
		'isLeafNode' => (bool) $person->isLeafNode(),
		'dateCreated' => ($person->dateCreated ? '"' . strftime(TF_HUMAN, $person->dateCreated) . '"' : null),
		'cc' => ($person->cc ? $person->cc : null),
		'pad' => (bool) $person->pad,
		'addresses' => $person->addresses,
		'path' => $person->getToken(),
		'activeStates' => $person->getActiveStates()
	), $extras));
}

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'moveNode':
			// echo 'moven';
			if (!$person = new Person ((int) $_REQUEST['nodeID'])) {
				if ($ajax) {
					echo '{"status": 0}';
					die ();
				}
			} else {
				switch ($_REQUEST['direction']) {
					case 'up':
						if ($person->moveLeft()) $moveState = -1;
						else $moveState = 0;
						break;
					case 'down':
						if ($person->moveRight()) $moveState = 1;
						else $moveState = 0;
				}
				if ($ajax) {
					if (!$moveState) {
						echo '{"status": 0}';
						die ();
					}
					$path = $person->getPath();
					foreach ($path as $k => $v) {
						$path[$k] = sprintf('%05s', $v);
					}
					echo json_encode(array(
						'status' => 1,
						'nodeID' => 'node0_'.implode('_', $path),
						'd' => $moveState,
						'size' => (($person->getRgt() - $person->getLft() + 1) / 2)
					));
					die ();
				}
			}
			break;
		case 'setParent':
			$db->start('mpSetParent');
			if (!$person = new Person ((int) $_REQUEST['personID'])) {
				$this->rollback('mpSetParent');
				break;
			}
			if (!$person->setParent((int) $_REQUEST['parentID'])) $db->rollback('mpSetParent');
			else $db->commit('mpSetParent');
			break;
		case 'loadPerson':
			$ajax = true;
			if (isset($_REQUEST['personID'])) {
				$personID = (int) $_REQUEST['personID'];
				if ($personID) {
					$person = new Person ($personID);
					if (!$person->personID) {
						echo '{"status": 0, "error": "person doesn\'t exist"}';
						$json = true;
						die ();
					}
				} else $person = new Person ();
			} else $person = new Person ();
			if (isset($_REQUEST['parentID'])) {
				$parentID = (int) $_REQUEST['parentID'];
				if ($parentID) {
					$parent = new Person ($parentID);
					if (!$parent->personID && !$person->personID) {
						echo '{"status": 0, "error": "neither person nor parent exist"}';
						$json = true;
						die ();
					}
				} else {
					if ($person->personID) $parent = $person->getParent();
					else {
						echo '{"status": 0, "error": "parent doesn\'t exist and person not specified"}';
						$json = true;
						die ();
					}
				}
			} else if (!$person->personID) {
				echo '{"status": 0, "error": "neither person or parent specified"}';
				$json = true;
				die ();
			} else $parent = $person->getParent();
			if (!$parent) $parent = new Person;
			outputPersonData($person, 1);
			$json = true;
			die ();
			break;
		case 'editPerson':
			if (isset($_REQUEST['personID'])) {
				$db->start('mpEditPerson');
				$person = new Person ((int) $_REQUEST['personID']);
				$personType = $person->personType;
				if (!$person->isIn($node) && (int) $_REQUEST['personID']) {
					$db->rollback('mpEditPerson');
					if ($ajax) {
						echo '{"status": 0}';
						$json = true;
						die ();
					} else break;
				}
				$errorFields = array ();
				$person->contactName = stripslashes($_REQUEST['contactName']);
				$person->groupName = stripslashes($_REQUEST['groupName']);
				$person->personType = 0;
				foreach ($personTypeNames as $k => $v) {
					if (isset($_REQUEST[$v])) {
						if ((bool) $_REQUEST[$v]) $person->personType |= $k;
					}
				}
				// if ($person->getRouteID() != $_REQUEST['routeID']) $person->setRoute((int) $_REQUEST['routeID']);
				if (isset($_REQUEST['addresses'])) {
					$addresses = $_REQUEST['addresses'];
					foreach ($addresses as $k => $v) {
						if (isset($person->addresses[$k])) {
							if (isset($v['del'])) {
								$person->removeAddress($k);
							} else {
								if (isset($v['ship'])) $ship = ($v['ship'] ? AD_SHIP : 0);
								else $ship = 0;
								if (isset($v['mail'])) $mail = ($v['mail'] ? AD_MAIL : 0);
								else $mail = 0;
								if (isset($v['pay'])) $pay = ($v['pay'] ? AD_PAY : 0);
								else $pay = 0;
								$person->addresses[$k]->addressType = $ship | $mail | $pay;
								$person->addresses[$k]->careOf = $v['careOf'];
								$person->addresses[$k]->address1 = $v['address1'];
								$person->addresses[$k]->address2 = $v['address2'];
								$person->addresses[$k]->city = $v['city'];
								$person->addresses[$k]->prov = $v['prov'];
								$person->addresses[$k]->postalCode = $v['postalCode'];
								$person->addresses[$k]->phone = $v['phone'];
								$person->addresses[$k]->directions = $v['directions'];
								if (!$person->addresses[$k]->validate() && !in_array('addresses', $errorFields)) {
									$errorFields[] = 'addresses';
									$person->addresses[$k]->errorFields = $person->addresses[$k]->getErrorDetail();
								}
							}
						}
					}
				}
				if (isset($_REQUEST['newaddresses'])) {
					$addresses = $_REQUEST['newaddresses'];
					$newaddresses = array ();
					foreach ($addresses as $k => $v) {
						if (!isset($v['del'])) {
							$r = new Record ($v);
							$r->d('ship');
							$r->d('mail');
							$r->d('pay');
							if (isset($v['ship'])) $ship = ($v['ship'] ? AD_SHIP : 0);
							else $ship = 0;
							if (isset($v['mail'])) $mail = ($v['mail'] ? AD_MAIL : 0);
							else $mail = 0;
							if (isset($v['pay'])) $pay = ($v['pay'] ? AD_PAY : 0);
							else $pay = 0;
							$r->s('addressType', $ship | $mail | $pay);
							$r->s('personID', $person->personID ? $person->personID : -1);
							$address = new Address ($r);
							if (!$address->validate() && !in_array('addresses', $errorFields)) {
								$errorFields[] = 'addresses';
								$address->errorFields = $address->getErrorDetail();
								$newaddresses[$k] = $address;
							} else {
								$person->addAddress($address);
							}
						}
					}
				}
				$person->phone = stripslashes($_REQUEST['phone']);
				$person->email = (trim($_REQUEST['email']) ? stripslashes($_REQUEST['email']) : null);
				if (trim($_REQUEST['password'])) $person->setPassword(trim($_REQUEST['password']));
				$person->privateKey = trim(stripslashes($_REQUEST['privateKey']));
				$person->notes = stripslashes($_REQUEST['notes']);
				$person->payTypeID = (int) $_REQUEST['payTypeID'];
				if (isset($_REQUEST['payTypeIDs']['inherit']) && $_REQUEST['payTypeIDs']['inherit']) $person->payTypeIDs = null;
				else {
					$person->payTypeIDs = array();
					if (isset($_REQUEST['payTypeIDs']) && is_array($_REQUEST['payTypeIDs'])) {
						foreach ($_REQUEST['payTypeIDs'] as $k => $v) {
							if ((int) $k && in_array((int) $k, $payTypeIDs)) $person->payTypeIDs[] = (int) $k;
						}
					}
				}
				$person->description = stripslashes($_REQUEST['description']);
				$person->website = stripslashes($_REQUEST['website']);
				if (isset($_REQUEST['customCancelsRecurring'])) $person->customCancelsRecurring = (bool) $_REQUEST['customCancelsRecurring'];
				switch ((int) $_REQUEST['canCustomOrder']) {
					case -1:
						$person->canCustomOrder = null;
						break;
					default:
						$person->canCustomOrder = (bool) $_REQUEST['canCustomOrder'];
				}
				// $person->compost = $_REQUEST['compost'] ? true : false;
				$person->minOrder = ($_REQUEST['minOrder'] ? (float) $_REQUEST['minOrder'] : (($_REQUEST['minOrder'] == '') ? null : 0));
				$person->minOrderDeliver = ($_REQUEST['minOrderDeliver'] ? (float) $_REQUEST['minOrderDeliver'] : (($_REQUEST['minOrderDeliver'] == '') ? null : 0));
				$person->bulkDiscount = ($_REQUEST['bulkDiscount'] ? (float) $_REQUEST['bulkDiscount'] : (($_REQUEST['bulkDiscount'] == '') ? null : 0));
				$person->bulkDiscountQuantity = ($_REQUEST['bulkDiscountQuantity'] ? (int) $_REQUEST['bulkDiscountQuantity'] : (($_REQUEST['bulkDiscountQuantity'] == '') ? null : 0));
				$person->maxStars = ($_REQUEST['maxStars'] ? (int) $_REQUEST['maxStars'] : (($_REQUEST['maxStars'] == '') ? null : 0));
				$person->stars = (int) $_REQUEST['stars'];
				if (isset($_REQUEST['recent'])) $person->recent = ($_REQUEST['recent'] ? true : false);
				$person->deposit = ($_REQUEST['deposit'] ? (float) $_REQUEST['deposit'] : (($_REQUEST['deposit'] == '') ? null : 0));
				$person->active = ($_REQUEST['active'] ? true : false);
				if (isset($_REQUEST['pad']) && $_REQUEST['pad'] && $person->cc && $person->txnID) $person->pad = true;
				else $person->pad = false;
				if (isset($_REQUEST['forgetCC'])) {
					$person->txnID = false;
					$person->cc = false;
					$person->pad = false;
				}
				$person->credit = ($_REQUEST['credit'] ? (float) $_REQUEST['credit'] : (($_REQUEST['credit'] == '') ? null : 0));
				$isNew = false;
				if (!$person->validate()) {
					$db->rollback('mpEditPerson');
					if ($ajax) {
						if ($person->getError() == E_INVALID_DATA) {
							$errorFields = array_merge($errorFields, $person->getErrorDetail());
							$status = -1;
						} else $status = 0;
					}
				}  // -- breakpoint
				if (!count($errorFields)) {
					$person->save();
					if (!(int) $_REQUEST['personID']) $person->setParent((int) $_REQUEST['parentID']);
					if (isset($_REQUEST['routeID'])) {
						if ((int) $_REQUEST['routeID']) {
							$person->setRoute((int) $_REQUEST['routeID']);
						} else $person->setRoute(null);
					} else $person->setRoute(null);
					if (($personType & P_CUSTOMER) != ($person->personType & P_CUSTOMER)) $person->personType ^= P_CUSTOMER;
					if (isset($_REQUEST['customer'])) {
						if ($_REQUEST['customer'] && !($personType & P_CUSTOMER)) $person->openCustomerAccount();
						else if (!$_REQUEST['customer'] && $personType & P_CUSTOMER) $person->closeCustomerAccount();
					} else if ($personType & P_CUSTOMER) $person->closeCustomerAccount();
					$db->commit('mpEditPerson');
					if (!(int) $_REQUEST['personID']) {
						$isNew = true;
						$status = 2;
					} else $status = 1;
				} else {
					if (!$status) $status = -1;
				}
				//$ajax = true;
				if ($ajax) {
					$extras = array ();
					if ($status > 0) { /* // -- this is causing troubles!
						$activeStates = $person->getActiveStates();
						foreach ($activeStates as $thisPath => $thisState) {
							$activeStates[$thisPath] = '[\'' . $thisPath . '\', ' . ($thisState ? 'true' : 'false') . ']';
						}
						$extras['activeStates'] = $activeStates; */
					} else $extras['activeStates'] = array ();
					if (isset($newaddresses)) $extras['newaddresses'] = $newaddresses;
					$extras['parentID'] = ((int) $_REQUEST['personID'] ? null : (int) $_REQUEST['parentID']);
					if ($status == -1) $extras['errorFields'] = $errorFields;
					if ($isNew) {
						$treeToken = $person->getToken($node);
						$extras['position'] = $person->getSpotInTree($sortOrder, $node->personID);
						$isActive = $person->isActive();
						ob_start();
						outputPersonRow($person, $status, $extras);
						$extras['newRow'] = ob_get_clean();
					}
					if ((int) $_REQUEST['personID']) $parent = $person->getParent();
					else $parent = new Person ($_REQUEST['parentID']);
					if (!$parent) $parent = new Person ();
					outputPersonData($person, $status, $extras);
					die ();
				}
			}
			break;
		case 'deletePerson':
			if (isset($_REQUEST['personID'])) {
				$person = new Person ((int) $_REQUEST['personID']);
				if (!$person->isIn($user, false)) {
					if ($ajax) {
						echo '{"status": 0, "message": "You are not allowed to delete this person or group."}';
						$json = true;
						die ();
					} else break;
				}
				$path = $person->getPath();
				$parentID = $person->getParentID();
				$success = $person->delete();
				if ($ajax) {
					if ($success) {
						foreach ($path as $i => $thisID) {
							$path[$i] = sprintf('%05s', $thisID);
						}
						$out = array (
							'status' => true,
							'nodeID' => 'node0_'.implode('_', $path)
						);
						array_pop($path);
						$parent = new Person ((int) $parentID);
						$out['parentID'] = 'node0_' . implode('_', $path);
						$out['parentIsLeaf'] = (bool) $parent->isLeafNode();
						echo json_encode($out);
						die;
					} else echo '{"status": 0}';
					$json = true;
					die ();
				}
			}
			break;
		case 'loadOrders':
			if (isset($_REQUEST['personID'])) {
				if (!$person = new Person ((int) $_REQUEST['personID'])) {
					echo '<p class="notice">Oops! We could not find the person you were looking for; this may be a bug, so it has been logged.</p>';
					die ();
				}
				if (!$person->isIn($node)) {
					echo '<p class="notice">You do not have permission to view this person\'s orders!</p>';
					die ();
				}
				foreach (array(O_SALE, O_RECURRING) as $v) {
					switch ($v) {
						case O_SALE:
							$orders = $person->getOrders(null, null, $v, false, 'dateStarted', false, true, false);
							$orderLabel = 'Regular';
							break;
						case O_RECURRING:
							$orders = $person->getOrders(null, null, $v, false, 'dateStarted');
							$orderLabel = 'Recurring';
					}
					echo '<h3>' . htmlEscape($orderLabel) . ' orders <a href="order.php?customerID=' . $person->personID . '&orderType=' . $orderTypeNames[$v] . '&forceNew=1" target="_blank" class="button small">+</a></h3>';
					echo '<ul class="orders' . ($v == O_RECURRING ? ' recurring' : null) . '">';
					if (count($orders)) {
						foreach ($orders as $thisOrder) {
							$totals = $thisOrder->getTotal();
							echo "\t<li id=\"order" . $thisOrder->orderID . "\"><a href=\"order.php?orderID=" . $thisOrder->orderID . '" class="orderLink" target="_blank">Order #' . $thisOrder->orderID . ($thisOrder->label ? ' - ' . htmlEscape($thisOrder->label) : null) . '</a> <a href="javascript:deleteOrder(' . $thisOrder->orderID . ')"><img src="img/del.png" class="icon" alt="delete"/></a><br/><em class="gray">(started ' . strftime('%x', $thisOrder->getDateStarted()) . ', total ' . money_format(NF_MONEY, $totals['gross']) . ($thisOrder->orderType & O_TEMPLATE ? ', ' . $thisOrder->getPeriod() : null) . ")</em></li>\n";
						}
					}
					echo "</ul>";
				}
				die ();
			} else {
				echo '<p class=\"notice\">Oops! We\'ve encountered a bug in the program; we could not determine the type of order you were looking for. This bug has been logged.</p>';
				die ();
			}
			break;
		case 'loadMoveTree':
			if (isset($_REQUEST['personID']) && (int) $_REQUEST['personID']) {
				$person = new Person((int) $_REQUEST['personID']);
				if ($person->isIn($user)) {
					$tree = $user->getTree(null, null, array('personType' => (P_DEPOT + P_CATEGORY)));
					$parentID = $person->getParentID();
					echo '<form action="managePeople.php" method="POST"><input type="hidden" name="action" value="setParent"/><input type="hidden" name="personID" value="' . (int) $_REQUEST['personID'] . '"/>';
					echo '<select name="parentID">';
					foreach ($tree as $thisNode) {
						echo '<option value="' . $thisNode->personID . '"' . ($person->isIn($thisNode) ? ' disabled="disabled"' : null) . '>' . str_repeat('&nbsp;&nbsp;', $thisNode->getDepth($user)) . htmlEscape($thisNode->contactName) . ($thisNode->groupName ? ', <em>' . htmlEscape($thisNode->groupName) . '</em>' : null) . '</option>';
					}
					echo '</select> <input type="submit" value="move"/>';
					echo '</form>';
				} else echo '<p class="notice">You are not allowed to move this person or group.</p>';
			} else echo '<p class="notice">That person or group was not found.</p>';
			die ();
		/*case 'checkDuplicateEmail':
			if (isset($_REQUEST['email'])) {
				echo '{\'duplicate\': ' . (checkDuplicateEmail($_REQUEST['email']) ? 'true' : 'false') . '}';
			} else echo '{\'duplicate\': false}';
			$json = true;
			die ();*/
		default:
	}
	// redirectThisPage('managePeople.php' . ($viewBy ? '?viewBy=' . $viewBy : ''));
}

$routes = array ();
$routes[] = new Route ();

if (!$db->query('SELECT * FROM deliveryDay')) {
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
	if (isset($routeDays[$r->v('routeID')])) $r->r['deliveryDays'] = $routeDays[$r->v('routeID')];
	$routes[] = new Route ($r);
}

$people = $node->getTree($sortOrder, 'tree');
$payTypes = getPayTypes();
$menuHide = true;

$noSidebars = true;
$fillContainer = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/managePeople.tpl.php');
include ($path . '/footer.tpl.php');

?>
