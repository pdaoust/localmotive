<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');

if (!$user = tryLogin()) die ();

$pageTitle = 'Localmotive - Edit account details';

$passwordError = false;
$errorFields = array ();

$routes = getRoutes();

$svc = $user->getCategory();
$depot = $user->getDepot();
if (is_object($depot)) {
	if ($depot->privateKey) $privateKey = $depot->privateKey;
}
$depots = $svc->getChildren(null, false, array ('personType' => P_DEPOT));
$depotStatus = false;
$errorFields = array ();

if (isset($_REQUEST['action'])) {
	switch ($_REQUEST['action']) {
		case 'save':
			$user->contactName = trim($_REQUEST['contactName']);
			$user->groupName = trim($_REQUEST['groupName']);
			$user->email = trim($_REQUEST['email']);
			if ($user->checkPassword($_REQUEST['oldPassword'])) {
				if ($_REQUEST['newPassword1'] == $_REQUEST['newPassword2'] && (string) $_REQUEST['newPassword1']) {
					$user->setPassword($_REQUEST['newPassword1']);
				} else {
					$errorFields[] = 'passwordMatch';
				}
			} else if ($_REQUEST['oldPassword'] && $_REQUEST['newPassword1']) {
				$errorFields[] = 'oldPassword';
			}
			if (isset($_REQUEST['addresses'])) {
				$addresses = $_REQUEST['addresses'];
				foreach ($addresses as $k => $v) {
					if (isset($user->addresses[$k])) {
						if (isset($v['del'])) {
							$user->removeAddress($k);
						} else {
							if (isset($v['ship'])) $ship = ($v['ship'] ? AD_SHIP : 0);
							else $ship = 0;
							if (isset($v['mail'])) $mail = ($v['mail'] ? AD_MAIL : 0);
							else $mail = 0;
							if (isset($v['pay'])) $pay = ($v['pay'] ? AD_PAY : 0);
							else $pay = 0;
							$user->addresses[$k]->addressType = $ship | $mail | $pay;
							$user->addresses[$k]->careOf = $v['careOf'];
							$user->addresses[$k]->address1 = $v['address1'];
							$user->addresses[$k]->address2 = $v['address2'];
							$user->addresses[$k]->city = $v['city'];
							$user->addresses[$k]->prov = $v['prov'];
							$user->addresses[$k]->postalCode = $v['postalCode'];
							$user->addresses[$k]->phone = $v['phone'];
							$user->addresses[$k]->directions = $v['directions'];
							if (!$user->addresses[$k]->validate() && !in_array('addresses', $errorFields)) $errorFields[] = 'addresses';
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
						$r->s('personID', $user->personID);
						$address = new Address ($r);
						if (!$address->validate() && !in_array('addresses', $errorFields)) {
							$errorFields[] = 'addresses';
							$newaddresses[$k] = $address;
						} else {
							$address->save();
							$user->addAddress($address);
						}
					}
				}
			}
			if (isset($_REQUEST['routeID'])) {
				if ((int) $_REQUEST['routeID'] != $user->getRouteID()) $user->setRoute((int) $_REQUEST['routeID']);
			}
			if (isset($_REQUEST['privateKey'])) $privateKey = $_REQUEST['privateKey'];
			if (isset($_REQUEST['depotID'])) {
				$depotStatus = setDepot();
				switch ($depotStatus) {
					case 0:
					case E_NO_OBJECT:
					case E_NO_OBJECT_ID:
						$errorFields[] = 'depot';
						break;
					case E_LOGIN_CREDENTIALS_INCORRECT:
						$errorFields[] = 'depotPrivateKey';
				}
			}
			$user->phone = $_REQUEST['phone'];
			if (isset($_REQUEST['defaultPaymentType'])) $user->defaultPaymentType = ((int) $_REQUEST['defaultPaymentType'] ? (int) $_REQUEST['defaultPaymentType'] : null);
			// $user->compost = ($_REQUEST['compost'] ? true : false);
			if (isset($_REQUEST['customCancelsRecurring'])) $user->customCancelsRecurring = ($_REQUEST['customCancelsRecurring'] ? true : false);
			if (isset($_REQUEST['description'])) $user->description = $_REQUEST['description'];
			if ($user->personType & P_SUPPLIER) $user->website = $_REQUEST['website'];
			if (!$user->validate()) $errorFields = array_merge($errorFields, $user->getErrorDetail());
			if (!count($errorFields)) {
				$saveStatus = $user->save();
				if ($saveStatus) {
					redirectThisPage('index.php');
					die ();
				}
				switch ($user->getError()) {
					case E_DATABASE:
						databaseError($db);
						die ();
					case E_INVALID_DATA:
						$errorFields = $user->getErrorDetail();
						break;
				}
			}
			break;
		case 'hasPrivateKey':
			if (isset($_REQUEST['depotID'])) {
				$depot = new Person ((int) $_REQUEST['depotID']);
				echo '{\'privateKey\': \'' . addslashes($depot->privateKey) . '\'}';
				$json = true;
			}
			die ();
	}
}

function setDepot() {
	if (isset($_REQUEST['depotID'])) $newDepot = new Person ((int) $_REQUEST['depotID']);
	else return E_NO_OBJECT_ID;
	if (!$newDepot) return E_NO_OBJECT;
	if (!$newDepot->personID) return E_NO_OBJECT;
	if ($newDepot->privateKey) {
		global $logger;
		if (!isset($_REQUEST['privateKey'])) return E_LOGIN_CREDENTIALS_INCORRECT;
		if (!$_REQUEST['privateKey']) return E_LOGIN_CREDENTIALS_INCORRECT;
		if ($_REQUEST['privateKey'] != $depot->privateKey) return E_LOGIN_CREDENTIALS_INCORRECT;
	}
	global $user, $depot;
	if (is_object($depot)) {
		if ($depot->personID == $newDepot->personID) return true;
	}
	return (bool) $user->setParent($newDepot->personID);
}

$payTypes = getPayTypes();

include ($path . '/header.tpl.php');
include ($path . '/market/templates/editAccount.tpl.php');
include ($path . '/footer.tpl.php');

?>
