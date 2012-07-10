<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');

$pageTitle = 'Localmotive - Sign up';
if (isset($_REQUEST['service'])) {
	switch ($_REQUEST['service']) {
		case 'farmersMarket':
			$svcID = 3;
			break;
		case 'healthyHarvest':
			$svcID = 2;
	}
}
if (isset($_REQUEST['svcID'])) $svcID = (int) $_REQUEST['svcID'];
if ((!isset($svcID) || (isset($svcID) && !$svcID)) && isset($config['defaultSvcID'])) $svcID = (int) $config['defaultSvcID'];
if (!isset($svcID) || (isset($svcID) && !$svcID)) $svcID = false;

$root = new Person (1);
$services = $root->getChildren(null, false, array ('personType' => P_CATEGORY));
if (!$svcID) $svc = reset($services);
else if (isset($services[$svcID])) $svc = $services[$svcID];
else $svc = false;
$user = getLoggedInUser();
if ($user->personID) {
	$reactivate = isset($_REQUEST['reactivate']);
	if ($reactivate && !($user->personType & P_SLEEPING)) $reactivate = false;
	if (!$reactivate) $svc = $user->getParent(P_CATEGORY);
	$parent = $user->getParent();
	if ($parent->personType & P_DEPOT) {
		$depotID = $parent->personID;
		$delivery = 'depot';
	}
	else $delivery = 'home';
}
if (!$user->personID) $newPerson = true;
else $newPerson = false;

if ($svc && $newPerson) $pageTitle .= ' for ' . $svc->groupName;

$passwordError = false;
$errorFields = array ();
/*if (!$db->query('SELECT * FROM route ORDER BY label')) {
	databaseError($db);
	die();
}
$routes = array ();
while ($r = $db->getRow(F_RECORD)) {
	$routes[] = new Route ($r);
}*/

// we only want Healthy Harvest
$routes = getRoutes();

$depots = $svc->getChildren(null, false, array ('personType' => P_DEPOT));
foreach ($depots as $k => $v) {
	if (!$v->getRouteID() || ($v->personType & P_PRIVATE) || !$v->isActive()) unset($depots[$k]);
}
$market = new Item(1);
$csaItems = $market->getChildren(null, false, array ('csaRequired' => true, 'price' => $svc->personID, 'leafNode' => true, 'active' => true));
if (!$csaItems) $csaItems = array ();
if (!$user->personID) $payTypes = $svc->getPayTypes();
else $payTypes = $user->getPayTypes();
foreach ($payTypes as $k => $v) {
	if (!$v->isActive()) unset($payTypes[$k]);
}

if (!$newPerson) $pageTitle = 'LocalMotive - Edit your information';

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'save':
			$errorFields = array ();
			// TODO: Doesn't check to see if service was created successfully
			$svc = new Person ((int) $_REQUEST['svcID']);
			if ($reactivate || $newPerson) {
				if ($svc->personType & P_CSA) $user->personType |= P_CSA;
				else $user->personType &= P_ALL ^ P_CSA;
				$user->personType |= P_CUSTOMER;
			}
			if (isset($_REQUEST['coop']) && $_REQUEST['coop']) $user->personType |= P_MEMBER;
			if ($user->personType & P_SLEEPING) $user->personType ^= P_SLEEPING;
			$personData['contactName'] = trim($_REQUEST['contactName']);
			if (isset($_REQUEST['groupName'])) $personData['groupName'] = trim($_REQUEST['groupName']);
			if ($newPerson) {
				$personData['email'] = trim($_REQUEST['email']);
				if (!filter_var($personData['email'], FILTER_VALIDATE_EMAIL)) $errorFields[] = 'email';
			}
			if ($newPerson || $_REQUEST['password1']) {
				if ($_REQUEST['password1'] == $_REQUEST['password2'] && $_REQUEST['password1']) {
					$personData['password'] = $_REQUEST['password1'];
				} else if ($newPerson) {
					$errorFields[] = 'passwordError';
					$personData['password'] = '?';
				}
				if (!$personData['password']) $errorFields[] = 'passwordError';
			}
			if ($user->personID && count($user->addresses)) $address = reset($user->addresses);
			else $address = new Address ();
			$address->address1 = trim($_REQUEST['address1']);
			if (isset($_REQUEST['address2'])) $address->address2 = trim($_REQUEST['address2']);
			$address->city = trim($_REQUEST['city']);
			$address->postalCode = trim($_REQUEST['postalCode']);
			if (isset($_REQUEST['prov'])) $address->prov = trim($_REQUEST['prov']);
			else if (isset($config['provDefault'])) $address->prov = $config['provDefault'];
			if (isset($_REQUEST['postalCode'])) $address->postalCode = trim($_REQUEST['postalCode']);
			if (isset($_REQUEST['directions'])) $address->directions = trim($_REQUEST['directions']);
			if (count($depots)) {
				if (isset($_REQUEST['delivery'])) $delivery = $_REQUEST['delivery'];
				else $delivery = false;
			} else $delivery = 'home';
			switch ($delivery) {
				case 'depot':
					if (isset($_REQUEST['depotID'])) {
						$depotID = (int) $_REQUEST['depotID'];
						if ($depotID) $personData['parentID'] = $depotID;
						else $errorFields[] = 'depotID';
						if (isset($depots[$depotID])) {
							$depot = $depots[$depotID];
							if ($depot->privateKey) {
								if (!isset($_REQUEST['privateKey'])) $errorFields[] = 'privateKey';
								else {
									$privateKey = $_REQUEST['privateKey'];
									if ($privateKey != $depot->privateKey) $errorFields[] = 'privateKey';
								}
							}
							if (!in_array('privateKey', $errorFields)) {
								$address->addressType = AD_MAIL;
							}
						} else $errorFields[] = 'depotID';
					}
					break;
				case 'home':
					if (isset($_REQUEST['routeID'])) {
						$routeID = (int) $_REQUEST['routeID'];
						if (isset($routes[$routeID])) {
							if ($routeID != $user->getRouteID()) $user->setRoute($routeID);
						} else $errorFields[] = 'routeID';
					} else $errorFields[] = 'routeID';
					$address->addressType = AD_SHIP;
					break;
				default:
					$errorFields[] = 'shippingError';
			}
			if (!$address->validate()) {
				$addressError = $address->getErrorDetail();
				if ($addressError[0] == 'personID') unset($addressError[0]);
				if (count($addressError)) $errorFields = array_merge($errorFields, $addressError);
			}
			// if ($svc->personID == 3 && ($user->getRouteID(false) != 14)) $user->setRoute(14);
			$personData['addresses'] = array ($address);
			if ((int) $_REQUEST['svcID'] && !isset($personData['parentID'])) $personData['parentID'] = (int) $_REQUEST['svcID'];
			$personData['phone'] = $_REQUEST['phone'];
			if (isset($_REQUEST['payTypeID'])) {
				if (!(int) $_REQUEST['payTypeID']) $personData['payTypeID'] = false;
				else if (!in_array((int) $_REQUEST['payTypeID'], array_keys($payTypes))) {
					$errorFields[] = 'payTypeID';
					$errorFields[] = 'payTypeIDInvalid';
				}
				else if (!$payTypes[(int) $_REQUEST['payTypeID']]->isActive()) {
					$errorFields[] = 'payTypeID';
					$errorFields[] = 'payTypeIDInactive';
				}
				else $personData['payTypeID'] = ((int) $_REQUEST['payTypeID'] ? (int) $_REQUEST['payTypeID'] : false);
			}
			$personData['notes'] = trim($_REQUEST['notes']);
			// $personData['compost'] = ($_REQUEST['compost'] ? true : false);
			if ($newPerson) {
				if (!$db->query('SELECT personID FROM person WHERE email = "' . $db->cleanString($personData['email']) . '"')) {
					databaseError($db);
					die ();
				}
				if ($db->getRow()) {
					$errorFields[] = 'emailDuplicate';
				}
				if (!isset($_REQUEST['captcha'])) $errorFields[] = 'captcha';
				if (!TextCaptcha::verify($_REQUEST['captcha'])) $errorFields[] = 'captcha';
			}
			$user->__construct($personData);
			if (!$user->validate()) $errorFields = array_merge($errorFields, $user->getErrorDetail());
			if ($reactivate || $newPerson) {
				if (isset($_REQUEST['period'])) {
					$period = (int) $_REQUEST['period'];
					if (($period < 1 && $user->personType & P_CSA) || $period > 4) {
						$errorFields[] = 'period';
						unset($period);
					}
					if (!$period) unset($period);
				} else if ($user->personType & P_CSA) $errorFields[] = 'period';
				if ($user->personType & P_CSA) {
					if (isset($_REQUEST['csaItemID'])) {
						$csaItem = new Item((int) $_REQUEST['csaItemID']);
						if ($csaItem) {
							if (!$csaItem->getCsaRequired()) {
								$errorFields[] = 'csaItemID';
								unset($csaItem);
							} else if (!$csaItem->getPrice($svc->personID)) {
								$errorFields[] = 'csaItemID';
								unset($csaItem);
							}
						} else {
							$errorFields[] = 'csaItemID';
							unset($csaItem);
						}
					} else $errorFields[] = 'csaItemID';
				}
			}
			if (isset($_POST['pad']) && $user->cc && $user->txnID) $user->pad = true;
			else $user->pad = false;
			if (isset($_POST['forgetCC']) || (isset($_POST['payTypeID']) && $_POST['payTypeID'] != PAY_CC)) {
				$user->cc = null;
				$user->txnID = null;
				$user->pad = false;
			}
			$orderData = array ();
			if (isset($csaItem)) $orderData['csaItemID'] = $csaItem->itemID;
			if (isset($period)) $orderData['period'] = (int) $period;
			if (!count($errorFields)) {
				if ($reactivate || $newPerson) {
					$user->personType ^= P_CUSTOMER;
					$saveStatus = $user->openCustomerAccount($personData);
				} else {
					if (isset($personData['password'])) $user->setPassword($personData['password']);
					if ($personData['parentID'] != $user->getParentID()) $user->setParent($personData['parentID']);
					$saveStatus = $user->save();
				}
				if ($saveStatus && !$passwordError) {
					if ($reactivate || $newPerson) {
						if (isset($period) || $user->personType & P_CSA) {
							$recurringOrder = $user->startOrder(O_RECURRING | O_EDITABLE | (($user->personType & P_CSA) ? O_CSA : 0) | O_DELIVER, $period * T_WEEK);
							if (isset($csaItem)) {
								if ($csaItem) $recurringOrder->addQuantity((int) $_REQUEST['csaItemID'], 1);
							}
							if ($svc->personID == 2) {
								$startDate = Date::round(strtotime('02 May 2010'), T_DAY);
								if ($startDate > time()) $recurringOrder->setDateStarted($startDate);
							}
							$recurringOrder->save();
						}
						$bound = 'PHP-alt-' . md5(date('r', time()));
						$headers = "From: Localmotive <feedme@localmotive.ca>\r\nReply-To: feedme@localmotive.ca\r\n";
						$headers .= 'Content-Type: multipart/alternative; boundary="' . $bound . '"';
						$message = "\n\n--" . $bound . "\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n";
						ob_start();
						$email = $personData['email'];
						$password = $personData['password'];
						include ($path . '/market/templates/welcomeText.tpl.php');
						$message .= ob_get_clean();
						$message .= "\n\n--" . $bound . "\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n";
						ob_start();
						include ($path . '/market/templates/welcome.tpl.php');
						$message .= ob_get_clean();
						$mailSent = @mail($email, 'Welcome to Localmotive!', $message, $headers);
						$headers = "From: Localmotive <feedme@localmotive.ca>\r\nReply-To: feedme@localmotive.ca\r\n";
						$headers .= "Content-type: text/html; charset=iso-8859-1\n\n";
						ob_start();
						include ($path . '/market/templates/newCustomer.tpl.php');
						$message = ob_get_clean();
						$mailSent = @mail('feedme@localmotive.ca', $user->getLabel() . ' has signed up to Localmotive', $message, $headers);
						if ($newPerson) $user->authenticate($personData['email'], $personData['password']);
					}
					redirectThisPage('index.php');
					die ();
				}
				switch ($user->getError()) {
					case E_DATABASE:
						if (!in_array('emailDuplicate', $errorFields)) {
							databaseError($db);
							die ();
						}
						break;
					case E_INVALID_DATA:
						$errorFields = array_merge($errorFields, $user->getErrorDetail());
						break;
				}
			}
			break;
		case 'getCatInfo':
			if (isset($_REQUEST['svcID'])) {
				if ($svc = new Person ((int) $_REQUEST['svcID'])) {
					$depots = $svc->getChildren(null, false, array ('personType' => P_DEPOT));
					$market = new Item(1);
					$csaItems = $market->getChildren('label', false, array ('csaRequired' => true, 'price' => $svc->personID, 'leafNode' => true));
					foreach ($csaItems as $k => $v) {
						$csaItems[$k] = array ('itemID' => $v->itemID, 'label' => $v->label, 'price' => $v->getPrice($svc->personID));
					}
					$svc = array ('status' => 1, 'svc' => $svc, 'depots' => $depots, 'csaItems' => $csaItems);
					echo json_encode ($svc);
				} else {
					$fail = array ('status' => 0, 'errorMsg' => 'Apparently this service does not exist. This may be a bug; please take the time to report this bug using the tool at the bottom of the page.');
					echo json_encode($fail);
				}
			} else {
				$fail = array ('status' => 0, 'errorMsg' => 'There seems to be something missing from the request.');
				echo json_encode($fail);
			}
			die ();
			break;
		case 'checkDuplicateEmail':
			if (isset($_REQUEST['email'])) {
				echo '{"duplicate": ' . (checkDuplicateEmail($_REQUEST['email']) ? 'true' : 'false') . '}';
			} else echo '{"duplicate": false}';
			die ();
		case 'hasPrivateKey':
			if (isset($_REQUEST['depotID'])) {
				$depot = new Person ((int) $_REQUEST['depotID']);
				echo json_encode(array('privateKey' => $depot->privateKey));
			}
			die ();
	}
}

if ($newPerson) {
	$pageArea = 'programs';
	$question = TextCaptcha::get();
} else $pageArea = 'market';

include ($path . '/header.tpl.php');
include ($path . '/market/templates/signup.tpl.php');
include ($path . '/footer.tpl.php');

?>
