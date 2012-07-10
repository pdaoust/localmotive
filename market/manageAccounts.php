<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');

if (!$user = tryLogin()) die ();
if ($user->personID != 1 && !($user->personType & P_DEPOT)) restrictedError();

$pageTitle = 'Localmotive - Manage accounts';

if (isset($_POST['action'])) {
	$logger->addEntry('post '.print_r($_POST, true));
	if (isset($_POST['personAdj'])) {
		$logger->addEntry('Starting accounting for people');
		foreach ($_POST['personAdj'] as $k => $v) {
			// if ($config['debug']) echo 'PersonID: ' . $thisPersonID . ' ';
			if ($p = new Person ((int) $k)) {
				if (isset($v['credit']) && ((float) $v['credit'] > 0)) {
					$p->createJournalEntry((float) $v['credit'], 'Credit' . ((isset($v['why']) && $v['why']) ? ': ' . $v['why'] : null), PAY_ACCT);
				}
				if (isset($v['payment']) && ((float) $v['payment'] > 0)) {
					if ($p->canUsePayType((int) $v['payTypeID'])) $payTypeID = (int) $v['payTypeID'];
					else $payTypeID = PAY_CHEQUE;
					if ((float) $v['payment']) {
						$logger->addEntry('v = ' . print_r($v, true));
						$p->pay((float) $v['payment'], null, $payTypeID);
					}
				}
				if ((isset($v['binsOut']) && ((int) $v['binsOut'] > 0)) || (isset($v['binsIn']) && ((int) $v['binsIn'] > 0))) {
					$binsOut = (isset($v['binsOut']) ? (int) $v['binsOut'] : 0);
					$binsIn = (isset($v['binsIn']) ? (int) $v['binsIn'] : 0);
					if ($binsOut - $binsIn) {
						$p->bins += $binsOut - $binsIn;
						$p->save();
					}
				}
				if (isset($v['bottlesIn']) && ((int) $v['bottlesIn'] > 0)) {
					$p->createJournalEntry((int) $v['bottlesIn'] * $config['bottleDeposit'], 'Bottle deposit refund for ' . (int) $v['bottlesIn'] . ' bottles');
				}
			}
		}
		$logger->addEntry('Finished accounting for people');
	}
	if (isset($_REQUEST['orderAdj'])) {
		$logger->addEntry('Starting accounting for orders');
		foreach ($_REQUEST['orderAdj'] as $thisOrderID => $thisOrderData) {
			if (isset($thisOrderData['delivered']) || (float) $thisOrderData['payment'] || (float) $thisOrderData['credit']) {
				$thisOrder = new Order ((int) $thisOrderID);
				if ($thisOrder->orderID) {
					// echo 'created order ' . $thisOrder->orderID . '... ';
					if ((float) $thisOrderData['credit']) $thisOrder->addCredit((float) $thisOrderData['credit']);
					if ((float) $thisOrderData['payment']) $thisOrder->addPayment((float) $thisOrderData['payment']);
					if ($thisOrderData['delivered'] && !$thisOrder->getDateDelivered()) $thisOrder->deliver();
				}
			}
		$logger->addEntry('Finished accounting for orders');
		}
	}
	/* if (isset($_REQUEST['orderAdjustment'])) {
		foreach ($_REQUEST['orderAdjustment'] as $thisOrderID => $thisOrderAdjustment) {
			if ((int) $thisOrderID && (float) $thisAccountAdjustment) {
				$db->query('SELECT person.personID AS personID, orders.orderID AS orderID FROM person, orders WHERE orders.orderID = ' . (int) $thisOrderID . ' AND person.personID = orders.orderID');
				if ($orderData = $db->getRow()) {
					$journalEntry = new JournalEntry ();
					$journalEntry->personID = $orderData['personID'];
					$journalEntry->orderID = (int) $thisOrderID;
					$journalEntry->amount = round($thisAccountAdjustment, 2);
					$journalEntry->save();
				}
			}
		}
	}
	if (isset($_REQUEST['payment'])) {
		foreach ($_REQUEST['payment'] as $thisAccountID => $thisAccountAdjustment) {
			if ((int) $thisAccountID && (float) $thisAccountAdjustment) {
				$journalEntry = new JournalEntry ();
				$journalEntry->personID = (int) $thisAccountID;
				$journalEntry->amount = round($thisAccountAdjustment, 2);
			}
		}
	}
	if (isset($_REQUEST['collateral'])) {
		foreach ($_REQUEST['collateral'] AS $thisPersonID => $thisCollateral) {
			$q = 'UPDATE person SET bins = bins + (' . (int) ($thisCollateral[0] - $thisCollateral[1]) . '), coldpacks = coldpacks + (' . (int) ($thisCollateral[2] - $thisCollateral[3]) . '), bottles = bottles + ' . (int) ($thisCollateral[4] - $thisCollateral[5]) . ')';
			if (!$db->query($q)) {
				databaseError($db);
				die ();
			}
		}
	} */
	// redirectThisPage('manageAccounts.php' . (isset($_REQUEST['showAll']) ? '?showAll' : ''));
}

if (isset($_REQUEST['viewBy'])) $viewBy = $_REQUEST['viewBy'];
else $viewBy = 'customers';
switch ($viewBy) {
	case 'deliveryDay':
		require_once('createSchedule.php');
		if (isset($_REQUEST['deliveryDay'])) {
			if ($deliveryDay = myCheckDate($_REQUEST['deliveryDay'])) {
				$sections = array ();
				$entries = array ();
				$orders = array ();
				if (createSchedule($deliveryDay)) break;
			}
		}
	case 'customers':
	case 'suppliers':
		$personType = (($viewBy == 'suppliers') ? P_SUPPLIER : P_CUSTOMER);
		$people = $user->getChildren($_REQUEST['sortOrder'], false, array('personType' => $personType));
}
// can submit accounting details if person is administrator or depot
$canAcct = ($user->personID == 1 ? true : $thisPerson->personType & P_DEPOT);

$noSidebars = true;
$fillContainer = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/manageAccounts.tpl.php');
include ($path . '/footer.tpl.php');

?>
