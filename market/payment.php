<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once($path . '/market/classes/aktiveMerchant/lib/merchant.php');
Merchant_Billing_Base::mode($config['pfMode']);

if (!$user = tryLogin()) die ();

$pageTitle = 'Localmotive - Make a payment';
$noSidebars = true;
$fillContainer = true;
$payAction = 'payment';

$personID = false;
$order = null;
$person = null;

if (isset($_REQUEST['personID'])) {
	if (!$person = new Person ((int) $_REQUEST['personID'])) {
		require_once ($path . '/header.tpl.php');
		$error = 'No such person!';
		$errorDetail = 'The person ' . (string) $_REQUEST['personID'] . ' does not exist. If you believe this is a bug, please report it using the feedback tool at the bottom of this page.';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
}

if (!$person) {
	if (isset($_SESSION['customerID'])) {
		if (!$person = new Person ((int) $_SESSION['customerID'])) $person = &$user;
	} else $person = &$user;
} else $person = &$user;

if (!$person || ($person && !$person->isIn($user))) $person = &$user;

$customer = &$person;

// by this time, $person should be created. Now we'll check if they're
// allowed to order.

$_SESSION['customerID'] = $person->personID;
list ($payTypes, $payType) = getCheckoutPayTypes($person);
$logger->addEntry('pay types ' . print_r($payTypes, true));
if (isset($payTypes[PAY_CHEQUE])) unset($payTypes[PAY_CHEQUE]);
if (isset($payTypes[PAY_ACCT])) unset($payTypes[PAY_ACCT]);
$balance = $person->getBalance();
$logger->addEntry('balance: ' . $balance);

if (!$_SERVER['HTTPS']) {
	header('Location: ' . $secureUrlPrefix . $config['baseUri'] . '/market/payment.php' . ($person->personID != $user->personID ? '?personID=' . $person->personID : null));
}
if (!isset($_POST['payTypeID'])) {
	if ($balance < 0) $amount = money_format(NF_MONEY_NOCURR, 0 - $balance);
	else $amount = '';
	$noSidebars = true;
	$fillContainer = true;
	include ($path . '/header.tpl.php');
	$payErrors = array ();
	$logger->addEntry('count of payTypes ' . count($payTypes));
	include ($path . '/market/templates/payActions.tpl.php');
	include ($path . '/footer.tpl.php');
	die ();
} else {
	$payErrors = array ();
	if (!isset($_POST['amount'])) $payErrors[] = 'amount';
	$amount = round((float) $_POST['amount'], 2);
	if ($amount <= 0) {
		if ($balance < 0) $amount = 0 - $balance;
		else $amount = 0;
		$payErrors[] = 'amount';
	}
	if (!isset($_POST['payTypeID'])) {
		$payErrors[] = 'payTypeID';
		$logger->addEntry('no payTypeID in form');
	} else if (!$person->canUsePayType($_POST['payTypeID'])) {
		$payErrors[] = 'payTypeID';
		$logger->addEntry('cant use payTypeID');
	} else $payType = new PayType((int) $_POST['payTypeID']);
	switch ($payType->payTypeID) {
		case PAY_CC:
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
			$options = array(
				'order_id' => 'REF' . $gateway->generate_unique_id(),
				'description' => 'Payment'
			);
			if (isset($_POST['useStoredCC']) && $_POST['useStoredCC']) {
				if ($customer->cc && $customer->txnID) {
					$response = $gateway->authorize($amount, $customer->txnID, $options);
				}
			}
			if (!isset($response)) list ($response, $ccPayErrors) = processCCFromForm($_POST, $customer, $amount, $payErrors);
			if (isset($ccPayErrors)) $payErrors = array_merge($payErrors, $ccPayErrors);
			if ($response) {
				$paySuccess = $response->success();
				$txnID = $response->PNRef;
			} else $paySuccess = false;
			break;
		case PAY_PAYPAL:
			// stub: have to create an IPN listener of some sort
		default:
			$payErrors[] = 'payTypeID';
	}
	if (count($payErrors)) {
		$amount = money_format(NF_MONEY_NOCURR, $amount);
		list ($payTypes, $payTypeSugg) = getCheckoutPayTypes($person);
		if (isset($payTypes[PAY_CHEQUE])) unset($payTypes[PAY_CHEQUE]);
		if (isset($payTypes[PAY_ACCT])) unset($payTypes[PAY_ACCT]);
		if (!$payType) $payType = $payTypeSugg;
		include ($path . '/header.tpl.php');
		include ($path . '/market/templates/payActions.tpl.php');
		include ($path . '/footer.tpl.php');
		die();
	}
	if (isset($paySuccess) && $paySuccess) {
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
		$response = $gateway->capture($totals['gross'], $txnID, array());
		if ($response) {
			if ($response->success()) $person->pay($amount, null, $payType->payTypeID, $response->PNRef);		
			else $paySuccess = false;
		} else $paySuccess = false;
	}
	if (isset($paySuccess) && !$paySuccess) {
		require_once ($path . '/header.tpl.php');
		$error = 'Sorry, but your credit card payment wasn\'t processed.';
		$errorDetail = 'We\'re sorry, but the payment processor has declined your credit card payment. Please phone us at 250-497-6577 to make alternate arrangements';
		require_once ($path . '/market/templates/error.tpl.php');
		require_once ($path . '/footer.tpl.php');
		die ();
	}
	$newBalance = $person->getBalance();
	unset($_SESSION['customerID']);
	$bound = 'PHP-alt-' . md5(date('r', time()));
	$headers = "From: Localmotive Market <orders@localmotive.ca>\r\nReply-To: orders@localmotive.ca\r\n";
	$headers .= 'Content-Type: multipart/alternative; boundary="' . $bound . '"';
	ob_start();
	echo "\n\n--" . $bound . "\nContent-Type: text/plain; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n"; ?>
LocalMotive Organic Delivery
2351 Allendale Rd
Okanagan Falls, BC  V0H 1R2
250.497.6577

Thank you for your payment of <?= money_format(NF_MONEY, $amount) ?>!

Your account balance was previously <?= money_format(NF_MONEY, $balance) . ($balance < 0 ? ' owing' : ($balance > 0 ? ' credit' : null)) ?>, and your
payment increased it to <?= money_format(NF_MONEY, $newBalance) . ($newBalance < 0 ? ' owing' : ($newBalance > 0 ? ' credit' : null)) ?>.

You can review your account balance anytime. Just log into your account
at http://www.localmotive.ca/market/

<?
	$message .= "\n\n--" . $bound . "\nContent-Type: text/html; charset=\"iso-8859-1\"\nContent-Transfer-Encoding: 7bit\n\n"; ?>
<p><strong>LocalMotive Organic Delivery</strong><br/>
2351 Allendale Rd<br/>
Okanagan Falls, BC  V0H 1R2<br/>
250.497.6577</p>

<h1>Thank you for your payment of <?= money_format(NF_MONEY, $amount) ?>!</h1>

<p>Your account balance was previously <?= money_format(NF_MONEY, $balance) . ($balance < 0 ? ' owing' : ($balance > 0 ? ' credit' : null)) ?>, and your
payment increased it to <?= money_format(NF_MONEY, $newBalance) . ($newBalance < 0 ? ' owing' : ($newBalance > 0 ? ' credit' : null)) ?>.</p>

<p>You can review your account balance anytime. Just log into your account at <a href="http://www.localmotive.ca/market/">http://www.localmotive.ca/market/</a></p>
	<?
	$message = ob_get_clean();
	$mailSent = @mail($customer->email, 'Thank you for your payment!', $message, $headers);
	if (!$mailSent) logError('E-mail to ' . $customer->email . ' (personID ' . $customer->personID . ') failed');
	$fromEmail = false;
	$oldBalance = $balance;
	include ($path . '/header.tpl.php');
	include ($path . '/market/templates/paymentComplete.tpl.php');
	include ($path . '/footer.tpl.php');
}
?>
