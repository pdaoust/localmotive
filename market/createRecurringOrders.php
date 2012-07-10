<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/aktiveMerchant/lib/merchant.php');
Merchant_Billing_Base::mode($config['pfMode']);

if (!$user = tryLogin()) die ();
if ($user->personID != 1) restrictedError();

$pageTitle = 'Localmotive - Create recurring orders';

$dateStart = false;
if (isset($_REQUEST['dateStd'])) {
	$dateStart = strtotime((string) $_REQUEST['dateStd']);
}
if (isset($_REQUEST['timestamp'])) $dateStart = (int) $_REQUEST['timestamp'];
$dateStart = myCheckDate($dateStart);
if (!$dateStart) $dateStart = time();
$dateStart = roundDate($dateStart, T_WEEK);



// if (isset($_REQUEST['confirm'])) {
	$q = 'SELECT * FROM orders WHERE orderType & ' . O_TEMPLATE . ' AND (dateCompleted IS NULL OR dateCompleted >= \'' . $db->cleanDate(roundDate($dateStart)) . '\' OR (dateCompleted <= \'' . $db->cleanDate(roundDate($dateStart + T_WEEK)) . '\' AND dateResume >= \'' . $db->cleanDate(roundDate($dateStart)) . '\'))';
	if (!$db->query($q)) {
		databaseError($db);
		die();
	}
	$orderInfo = array ();
	$orders = array ();
	$people = array ();
	$balances = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$orderInfo[$r->v('orderID')] = $r;
	}
	foreach ($orderInfo as $thisOrderInfo) {
		$thisOrder = new Order ($thisOrderInfo);
		$orders[$thisOrder->orderID] = $thisOrder;
		$people[$thisOrder->personID] = $thisOrder->getPerson();
		$balances[$thisOrder->personID] = array (
			'balancePrev' => $people[$thisOrder->personID]->getBalance(),
			'balanceAfter' => 0,
			'payTypeID' => false
		);
		$logger->addEntry('person ' . $thisOrder->personID . ' and order ' . $thisOrder->orderID);
	}
	if (count($orders)) {
		try {
			$gateway = new Merchant_Billing_Payflow( array(
				'login' => $config['pfUser'],
				'user' => $config['pfUser'],
				'password' => $config['pfPwd'],
				'partner' => $config['pfPartner'],
				'currency' => 'CAD'
			));							
		} catch (Exception $e) {
			$logger->addEntry('gateway error: ' . $e->getMessage());
		}
		if (!$dateStart) $dateStart = roundDate(time(), T_WEEK) + T_WEEK;
		$dateEnd = $dateStart + T_WEEK - 1;
		$errors = array ();
		foreach ($orders as $vo) {
			$e = array ();
			$vp = null;
			if (isset($newOrder)) unset($newOrder);
			if (!isset($people[$vo->personID])) {
				unset($orders[$vo->orderID]);
				continue;
			} else $vp = $people[$vo->personID];
			if ($vp->personType & P_CATEGORY) {
				unset($orders[$vo->orderID]);
				continue;
			}
			$totals = $vo->getTotal();
			if (!$totals['gross']) $e[] = 'empty';
			if ($vp->cc && $vp->txnID && $vp->pad) {
				$balances[$vp->personID]['payTypeID'] = PAY_CC;
				$response = $gateway->authorize($totals['gross'], $vp->txnID, array(
					'order_id' => 'REF' . $gateway->generate_unique_id(),
					'description' => 'Automatic payment auth on recurring order template '.$vo->orderID
				));
				if ($response && $response->success()) {
					$txnID = $response->PNRef;
					if ($newOrder = $orders[$vo->orderID]->replicate($dateStart, $dateEnd)) {
						$orders[$vo->orderID] = $newOrder;
						$response2 = $gateway->capture($totals['gross'], $txnID, array (
							'order_id' => $newOrder->orderID,
							'description' => 'Automatic payment on recurring order '.$newOrder->orderID
						));
						if ($response2 && $response2->success()) {
							$newOrder->addPayment($totals['gross'], PAY_CC, $response2->PNRef);
							$balances[$vp->personID]['txnID'] = $response2->PNRef;
							$vp->save();
						} else $e = array_merge($e, getPayFlowErrors($response2->Result));
					}
					if (!isset($newOrder) || (isset($newOrder) && !$newOrder)) @$gateway->void($txnID);
				} else $e = array_merge($e, getPayFlowErrors($response->Result));
			}
			if (!isset($newOrder)) {
				//if (($vp->getBalance(true) >= $totals['gross']) || $vo->orderType & O_SYSTEM) {
					if ($newOrder = $orders[$vo->orderID]->replicate($dateStart, $dateEnd)) $orders[$vo->orderID] = $newOrder;
				//} else $e[] = 'nocredit';
			}
			if (!isset($newOrder) || !$newOrder) {
				if ($vp->personID == 6) $logger->addEntry('Paul!!!!!!!!!!!!!!!!!!!!', $vo->getError());
				switch ($vo->getError()) {
					case E_OBJECT_NOT_ACTIVE:
						$e[] = 'inactive';
						break;
					case E_NO_OBJECT:
						if (!$vo->personID) unset($orders[$vo->orderID]);
						else $e[] = 'noroute';
						break;
					case E_ORDER_EMPTY:
						$e[] = 'empty';
						break;
					case E_ORDER_TOO_SMALL:
						$e[] = 'toosmall';
						break;
					case E_WRONG_ORDER_TYPE:
						$e[] = 'nocsa';
						break;
					case E_DATABASE:
						$e[] = 'database';
						break;
					case E_NO_RECURRING_FOR_NEXT_DELIVERY_DAY:
					case E_NOT_WITHIN_DELIVERY_CUTOFF:
					case E_NO_MORE_RECURRING:
					case E_RECURRING_ALREADY_ORDERED:
						unset($orders[$vo->orderID]);
						break;
				}
			} else {
				$bound = 'PHP-alt-' . md5(date('r', time()));
				$headers = "From: Localmotive Market <orders@localmotive.ca>\r\nReply-To: orders@localmotive.ca\r\n";
				$headers .= 'Content-Type: multipart/alternative; boundary="' . $bound . '"';
				ob_start();
				$customer = $vp;
				echo "\n\n--" . $bound . "\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n"; ?>
LocalMotive Organic Delivery
2351 Allendale Rd
Okanagan Falls, BC  V0H 1R2
250.497.6577

Your recurring order has been processed!

You can review it and check its status anytime. Just log into your
account at http://www.localmotive.ca/market/

<?
				$fromEmail = true;
				$order = $vo;
				include ($path . '/market/templates/invoiceText.tpl.php');
				$message .= "\n\n--" . $bound . "\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n"; ?>
<p><strong>LocalMotive Organic Delivery</strong><br/>
2351 Allendale Rd<br/>
Okanagan Falls, BC  V0H 1R2<br/>
250.497.6577</p>

<h1>Your recurring order has been processed!</h1>

<p>You can review it and check its status anytime. Just log into your account at <a href="http://www.localmotive.ca/market/">http://www.localmotive.ca/market/</a></p>
				<? $hidePackingIcon = true;
				include ($path . '/market/templates/invoice.tpl.php');
				$message = ob_get_clean();
				$mailSent = @mail($customer->email, 'Thank you for your order! (#' . $order->orderID . ')', $message, $headers);
				if (!$mailSent) logError('E-mail to ' . $customer->email . ' (personID ' . $customer->personID . ') failed');
			}
			if (count($e)) $errors[$vo->orderID] = array_unique($e);
			unset($vp);
		}
		$q = 'SELECT person.personID AS personID, person.balance AS balance FROM person, orders WHERE person.personID = orders.personID AND orders.orderID in (' . implode(', ', array_keys($orders)) . ')';
		if (!$db->query($q)) {
			databaseError($db);
			die();
		}
		while ($r = $db->getRow(F_RECORD)) {
			$balances[$r->v('personID')]['balanceAfter'] = $r->v('balance');
		}
	}
// }

$noSidebars = true;
$fillContainer = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/createRecurringOrders.tpl.php');
include ($path . '/footer.tpl.php');

?>
