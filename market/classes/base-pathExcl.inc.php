<?php

require_once('const.inc.php');

if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() {
        return 0;
    }
}

function getKey ($includeSalt = true) {
	global $config;
	return Cryptastic::pbkdf2($config['encryptionKey'], ($includeSalt ? $config['encryptionSalt'] : null), 1000, 32);
}

function abortTransaction () {
	flush();
	if (connection_aborted()) {
		global $db;
		$db->abort();
	}
}

function htmlEscape ($v) {
	return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function myCheckDate ($dateString) {
	if ((string) $dateString == (string) (int) $dateString && $dateString) $dateString = (int) $dateString;
	if ($dateString && !is_int($dateString)) {
		$dateString = strtotime($dateString);
		if (!$dateString) return false;
		else return $dateString;
	} else if (is_int($dateString)) return $dateString;
	else if (!$dateString) return null;
	else return false;
}

function roundDate ($date, $roundTo = T_DAY, $adjustDST = false) {
	global $config;
	// if ($config['debug']) echo '<span style="color: #5c0;">Date to be rounded: ' . $date . '</span>';
	if (is_string($date)) $date = strtotime($date);
	if (!(int) $date) return false;
	switch ($roundTo) {
		case T_WEEK:
			$date = strtotime('last Sunday', $date + T_DAY);
		case T_DAY:
		default:
			// $date = (floor($date / T_DAY) * T_DAY) - ($config['timeZone'] * T_HOUR);
			$date = strtotime(strftime('%d %B %Y', $date));
	}
	// make date even number of hours; adjusting for 12:00 DST = 23:00 ST
	if ($adjustDST) $date += (date('I', $date) ? T_HOUR : 0);
	return $date;
}

function toHumanDate ($date = 0, $period = 0, $includeStart = false) {
	return Date::human ($date, $period, $includeStart);
}

// TODO: make $smart which automatically changes
function ordinal ($value) {
// Function written by Marcus L. Griswold (vujsa)
// Can be found at http://www.handyphp.com
// Do not remove this header!

    is_numeric($value) or trigger_error("<b>\"$value\"</b> is not a number! The value must be a number in the function <b>ordinal_suffix()</b>", E_USER_ERROR);
    if (substr($value, -2, 2) == 11 || substr($value, -2, 2) == 12 || substr($value, -2, 2) == 13) $suffix = "th";
    else if (substr($value, -1, 1) == 1) $suffix = "st";
    else if (substr($value, -1, 1) == 2) $suffix = "nd";
    else if (substr($value, -1, 1) == 3) $suffix = "rd";
    else $suffix = "th";
    return $suffix;
}

// Creates textual representation of an integer
// TODO: create ordinals
function numToStr ($num, $ord = false) {
	$num = (int) $num;
	// zero doesn't technically have an ordinal, but what the heck!
	if (!$num) return 'zero' . ($ord ? 'th' : null);
	if ($num < 0) $sign = 'negative ';
	else $sign = '';
	$num = abs($num);
    $big = array ('', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion');
    $small = array ('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen');
    $smallOrd = array ('', 'fir', 'seco', 'thi', 'four', 'fif', 'six', 'seven', 'eigh', 'nin', 'ten', 'eleven', 'twelf', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen');
    $med = array ('', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety');
    $medOrd = array ('', '', 'twentie', 'thirtie', 'fortie', 'fiftie', 'sixtie', 'seventie', 'eightie', 'ninetie');
    $hun = 'hundred';
    $end = array();
    if (strlen($num) % 3) $num = str_repeat('0', 3 - strlen($num) % 3) . $num;
    $num = strrev($num);
    $final = array();
    for ($i = 0; $i < strlen($num); $i += 3) {
        $end[$i] = strrev(substr($num, $i, 3));
    }
    $num = strrev($num);
    $end = array_reverse($end);
    for ($i = 0; $i < sizeof($end); $i ++) {
        $len = strlen($end[$i]);
        $temp = str_split($end[$i]);
        // are there any more non-zeros after this, or should we tack on the ordinal now?
        $rest1 = (int) substr($num, $i * 3 + 1);
        $rest2 = (int) substr($num, $i * 3 + 2);
        $rest3 = (int) substr($num, $i * 3 + 3);
        // is this the last set of thousand?
        $last = (sizeof($end) - $i - 1 == 0);
        if ($last && $i && (int) $end[$i] && $end[$i] < 100 && (!($end[$i] % 10) || $end[$i] < 20)) $final[] = 'and';
        $last = $last && $ord;
        // if there's a non-zero in the hundreds place
        if ((int) $temp[0]) {
			$final[] = $small[$temp[0]] . ' ' . $hun;
		}
		// if there's a non-zero in the tens place
		$tens = (int) ($temp[1] . $temp[2]);
		if ($tens) {
			// check for teens, and use an ordinal if there's nothing after the ones
			if ($tens < 20) $final[] = (!(int) $rest3 && $last) ? $smallOrd[$tens] : $small[$tens];
			// otherwise, just use a normal tens (20 and above) and use an ordinal if there's nothing after the tens
			else {
				// if there's something in the ones, add tens plus dash, then ones
				if ((int) $temp[2]) $final[] = ((int) $temp[1] ? $med[$temp[1]] . '-' : null) . ((!(int) $rest3 && $last) ? $smallOrd[$temp[2]] : $small[$temp[2]]);
				// otherwise, just do the tens
				else $final[] = (!(int) $rest2 && $last) ? $medOrd[$temp[1]] : $med[$temp[1]];
			}
		}
        $final[] = $end[$i] != '000' ? $big[sizeof($end) - $i - 1] : '';
    }
    return $sign . trim(str_replace(array('  ', '  ', '  ', '  ', '  ', '  ', '  '), ' ', implode(' ', $final))) . ($ord ? ordinal((int) $num) : null);
}

function getWaitingOrders ($dateToDeliver, $completed = false) {
	global $db;
	$dateToDeliver = myCheckDate($dateToDeliver);
	if ($dateToDeliver) $dateToDeliver = roundDate($dateToDeliver);
	else $dateToDeliver = null;
	if (!$dateToDeliver) return false;
	if (!$db->query('SELECT * FROM orders WHERE orderType & ' . O_OLD . ' = ' . O_SALE . ($completed ? ' AND dateCompleted IS NOT NULL' : null) . ' AND dateDelivered IS NULL AND dateCanceled IS NULL' . ($dateToDeliver ? ' AND dateToDeliver = \'' . $db->cleanDate($dateToDeliver) . '\'' : null))) {
		databaseError($db);
		die(');
	}
	$orderInfo = array ();
	$orders = array ();
	while ($r = $db->getRow(F_ASSOC)) {
		$orderInfo[] = $r;
	}
	foreach ($orderInfo as $thisOrderInfo) {
		$thisOrder = new Order ($thisOrderInfo);
		$orders[$thisOrder->orderID] = $thisOrder;
	}
	return $orders;
}

function sessionDefaults () {
	unset($_SESSION['loggedIn']);
	unset($_SESSION['personID']);
	unset($_SESSION['username']);
	unset($_SESSION['cookie']);
	unset($_SESSION['remember']);
	unset($_SESSION['customerID']);;
	unset($_SESSION['pageArea']);
	unset($_SESSION['demo']);
}

function redirectThisPage ($pageName) {
	$host = $_SERVER['HTTP_HOST'];
	$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	header("Location: http://$host$uri/$pageName");
}

function databaseError ($db) {
	global $logger, $config, $pageTitle, $pageArea, $path;
	list ($errorMessage, $q) = $db->getError();
	$logger->addEntry($errorMessage . ' (' . $q . ')', E_DATABASE);
	if ($GLOBALS['ajax']) {
		echo '0';
		global $json;
		$json = true;
	} else {
		include ($path . '/header.tpl.php');
		include ($path . '/market/templates/databaseError.tpl.php');
		include ($path . '/footer.tpl.php');
	}
	// die ();
}

function logError ($errorData) {
	global $logger;
	$logger->addEntry($errorData);
}

function restrictedError () {
	global $path, $pageArea, $config, $menuHide;
	if (!$GLOBALS['ajax']) {
		global $json;
		$json = true;
		die();
	}
	require_once ($path . '/header.tpl.php');
	$loginError = 'This area is restricted to administrators. Please enter the correct administrator login info below. If you were previously logged in as an administrator and are now logging in as a user, please go back to your <a href="index.php">main menu</a>.';
	require_once ($path . '/market/templates/login.tpl.php');
	require_once ($path . '/footer.tpl.php');
}

function getNextDeliveryDay ($day = null, $accountForCutoff = true) {
	$day = myCheckDate($day);
	if (!$day) $day = time();
	$day = roundDate($day);
	global $db, $config, $dayNames;
	$nextDay = 0;
	if (!$db->query('SELECT * FROM deliveryDay ORDER BY deliveryDayID')) {
		databaseError($db);
		die ();
	}
	while ($r = $db->getRow(F_RECORD)) {
		// removes one day from timestamp; that way, 'next' will include the current day.
		$deliveryDay = new DeliveryDay ($r);
		$newNextDay = $deliveryDay->getNextDeliveryDay();
		if (!$nextDay || ($nextDay && $newNextDay < $nextDay)) $nextDay = $newNextDay;
	}
	// echo 'next day: ' . strftime('%Y-%m-%d %H:%M:%S', $nextDay);
	return $nextDay;
}

// defunct now.
function getSchedule ($day = null) {
	$day = myCheckDate($day);
	if (!$day) $day = time ();
	$day = roundDate($day);
	global $db, $dayNames;
	if (!$db->query('SELECT deliveryDayID FROM deliveryDay ORDER BY deliveryDayID')) {
		databaseError($db);
		die ();
	}
	$schedule = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$thisDayID = $r->v('deliveryDayID');
		$schedule[$thisDayID] = strtotime('next ' . $dayNames[$thisDayID], roundDate($day) - T_DAY);
	}
	asort($schedule);
	return $schedule;
}

 function getDeliveryDays ($idsOnly = false, $dayStart = null, $dayEnd = null) {
	$dayStart = myCheckDate($dayStart);
	if (!$dayStart) $dayStart = null;
	else $dayStart = roundDate($dayStart);
	$dayEnd = myCheckDate($dayEnd);
	if (!$dayEnd) $dayEnd = null;
	else $dayEnd = roundDate($dayEnd);
	global $db, $dayNames;
	if (!$db->query('SELECT * FROM deliveryDay ORDER BY deliveryDayID')) {
		databaseError($db);
		die ();
	}
	$deliveryDays = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$inSet = false;
		$deliveryDay = new DeliveryDay ($r);
		if ($dayStart) {
			$nextDay = $deliveryDay->getNextDeliveryDay($dayStart, false);
			if (!$dayEnd) {
				if ($nextDay == $dayStart) $inSet = true;
			} else if ($nextDay <= $dayEnd) $inSet = true;
		} else $inSet = true;
		if ($inSet) {
			if (isset($nextDay)) $deliveryDay->nextDay = $nextDay;
			$deliveryDays[$deliveryDay->deliveryDayID] = ($idsOnly ? $deliveryDay->deliveryDayID : $deliveryDay);
		}
	}
	return $deliveryDays;
}

function getRoutes ($activeOnly = true) {
	global $db;
	if (!$db->query('SELECT * FROM route ' . ($activeOnly ? 'WHERE routeID IN (SELECT routeID FROM routeDay GROUP BY routeID) AND active = 1 ' : null) . 'ORDER BY label')) {
		databaseError($db);
		die ();
	}
	$routes = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$routes[$r->v('routeID')] = $r;
	}
	if (!$db->query('SELECT * FROM routeDay, deliveryDay WHERE routeDay.deliveryDayID = deliveryDay.deliveryDayID AND routeID IN (' . implode(', ', array_keys($routes)) . ')' . ($activeOnly ? ' AND deliveryDay.active = 1' : null))) {
		databaseError($db);
		die();
	}
	$routeDays = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$routeID = $r->v('routeID');
		if (!isset($routeDays[$routeID])) $routeDays[$routeID] = array ();
		$routeDays[$routeID][$r->v('deliveryDayID')] = $r;
	}
	foreach ($routeDays as $k => $v) {
		foreach ($v as $k2 => $v2) {
			$routeDays[$k][$k2] = new RouteDay($v2);
		}
	}
	foreach ($routes as $k => $v) {
		if (($activeOnly && isset($routeDays[$k])) || !$activeOnly) {
			if (isset($routeDays[$k])) $v->s('routeDays', $routeDays[$k]);
			$routes[$k] = new Route($v);
		} else unset($routes[$k]);
	}
	return $routes;
}

function getPayTypes () {
	global $db;
	if (!$db->query('SELECT * FROM payType ORDER BY label')) {
		databaseError($db);
		die();
	}
	$payTypes = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$payType = new PayType ($r);
		if ($payType) $payTypes[$payType->payTypeID] = $payType;
	}
	return $payTypes;
}

function getCheckoutPayTypes ($person, $amount = 99999999) {
	$payTypes = $person->getPayTypes();
	if (isset($payTypes[PAY_CHEQUE])) unset($payTypes[PAY_CHEQUE]);
	if ($person->getBalance(true) < $amount) {
		if (isset($payTypes[PAY_ACCT])) unset($payTypes[PAY_ACCT]);
		if ($person->canUsePayType(PAY_CC)) $payType = new PayType (PAY_CC);
		else if ($person->canUsePayType(PAY_PAYPAL)) $payType = new PayType (PAY_PAYPAL);
		else $payType = false;
	} else if ($person->canUsePayType(PAY_ACCT)) $payType = new PayType (PAY_ACCT);
	else $payType = false;
	if (isset($payTypes[PAY_PAYPAL])) unset($payTypes[PAY_PAYPAL]);
	return array ($payTypes, $payType);
}

function processCCFromForm ($formData, Person $person, $amount, $payErrors) {
	global $path, $logger;
	$response = false;
	$amount = round((float) $amount, 2);
	if (!is_array($formData) || $amount <= 0) return array (false, array('amount'));
	global $config;
	if (!isset($formData['cardAction']) || (isset($formData['cardAction']) && !$formData['cardAction'])) {
		$payErrors[] = 'cardAction';
		$formData['cardAction'] = false;
	}
	switch ($formData['cardAction']) {
		case 'useStoredCC':
			if (!$person->cc || !$person->txnID) {
				$payErrors[] = 'CardNum';
				break;
			}
			$credit_card = $person->txnID;
			break;
		case 'useNewCC':
			if (!isset($formData['CardNum'])) $payErrors[] = 'CardNum';
			if (!isset($formData['ExpDateMonth'])) $payErrors[] = 'ExpDateMonth';
			if (!isset($formData['ExpDateYear'])) $payErrors[] = 'ExpDateYear';
			if (!isset($formData['CVNum'])) $payErrors[] = 'CVNum';
			if (!count($payErrors)) {
				$expDateYear = preg_replace('/[^0-9\s]/', '', $formData['ExpDateYear']);
				$expDateYear = substr($expDateYear, -2);
				require_once($path . '/market/classes/aktiveMerchant/lib/merchant.php');
				$contactName = explode(' ', $person->getLabel(true));
				$credit_card = new Merchant_Billing_CreditCard( array(
					'last_name' => array_pop($contactName),
					'first_name' => implode(' ', $contactName),
					"number" => $formData['CardNum'],
					"month" => $formData['ExpDateMonth'],
					"year" => '20' . $expDateYear,
					"verification_value" => $formData['CVNum']
				));
				if (!$credit_card->is_valid()) {
					$errors = $credit_card->errors();
					if (array_key_exists('number', $errors)) $payErrors[] = 'CardNum';
					if (array_key_exists('year', $errors) || array_key_exists('month', $errors)) $payErrors[] = 'expDate';
					if (array_key_exists('verification_value', $errors)) $payErrors[] = 'CVNum';
					$credit_card = false;
				}
			}
	}
	if ($credit_card) {
		Merchant_Billing_Base::mode($config['pfMode']);
		try {
			$gateway = new Merchant_Billing_Payflow( array(
				'login' => $config['pfUser'],
				'user' => $config['pfUser'],
				'password' => $config['pfPwd'],
				'partner' => $config['pfPartner'],
				'currency' => 'CAD'
			));
		} catch (Exception $e) {
			$payErrors[] = 'gateway';
			$logger->addEntry('gateway error: ' . $e->getMessage());
		}
		if (!count($payErrors)) {
			$orderID = (isset($formData['orderID']) ? $formData['orderID'] : false);
			$options = array(
				'order_id' => ($orderID ? $formData['orderID'] : 'REF' . $gateway->generate_unique_id()),
				'description' => ($orderID ? 'Payment on order #' . $orderID : 'Account payment')
				/*'address' => array(
					'address1' => '1234 Street',
					'zip' => '98004',
					'state' => 'WA'
				)*/
			);
			# Authorize transaction
			//$response = $gateway->authorize($totals['gross'], $credit_card, $options);
			$response = $gateway->authorize($amount, $credit_card, $options);
			if ( $response->success() ) {
				$logger->addEntry('Success Authorize');
				if (isset($_POST['rememberCC']) && $_POST['rememberCC'] && $_POST['CardNum']) {
					$person->cc = $_POST['CardNum'];
					$person->txnID = $response->PNRef;
					$person->payTypeID = PAY_CC;
					$person->save();
				}
				$person->pad = (isset($_POST['pad']) && $_POST['pad']);
				$person->save();
			} else $payErrors = array_merge($payErrors, getPayFlowErrors($response->Result));
		}
	}
	return array ($response, $payErrors);
}

function getPayFlowErrors ($code) {
	$payErrors = array ();
	switch ((int) $code) {
		case 1: // user auth failed
		case 2: // invalid merchant info
		case 8: // nt a transaction server
			$payErrors[] = 'gateway';
			$payErrors[] = 'userauth';
			break;
		case 7: // field format info
		case 0: // malformed XML
			$payErrors[] = 'gateway';
			$payErrors[] = 'data';
			break;
		case 12: // declined; check credentials
			$payErrors[] = 'gateway';
			$payErrors[] = 'declined';
			break;
		case 13: // declined; referral needed
			$payErrors[] = 'gateway';
			$payErrors[] = 'referral';
			break;
		case 19: // origID not found
			$payErrors[] = 'gateway';
			$payErrors[] = 'origID';
		case 23: // invalid CC number
			$payErrors[] = 'gateway';
			$payErrors[] = 'CardNum';
			break;
		case 24: // invalid expiry date
			$payErrors[] = 'gateway';
			$payErrors[] = 'ExpDate';
			break;
		case 114: // CVC incorrect
			$payErrors[] = 'gateway';
			$payErrors[] = 'CVNum';
			break;
		case 30: // duplicate transaction
			$payErrors[] = 'gateway';
			$payErrors[] = 'duplicate';
			break;
		case 50: // NSF
			$payErrors[] = 'gateway';
			$payErrors[] = 'nsf';
			break;
		case 51: // exceeds per-transaction limit
			$payErrors[] = 'gateway';
			$payErrors[] = 'txlimit';
			break;
		case -1: // host connection failed
		case -2: // hostname unresolved
		case 11: // server busy
		case 102: // processor not available
		case 104: // processor timeout
		case 115: // system busy
		case 150: // bank timed out
		case 151: // bank unavailable
			$payErrors[] = 'gateway';
			$payErrors[] = 'unavailable';
			break;
		default:
			$payErrors[] = 'gateway';
	}
	return $payErrors;
}

function createRandomPassword ($length = 8) {
	$length = abs((int) $length);
	if (!$length) $length = 8;
	$chars = "abcdefghijkmnopqrstuvwxyz023456789";
	srand((double)microtime()*1000000);
	$i = 0;
	$pass = '' ;
	while ($i < $length) {
		$num = rand() % 33;
		$tmp = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i++;
	}
	return $pass;
}

function checkDuplicateEmail ($email) {
	$email = trim ($email);
	global $db;
	if (!$db->query('SELECT personID FROM person WHERE email = \'' . $db->cleanString($email) . '\'', true)) return false;
	if ($r = $db->getRow(F_ASSOC)) return true;
	else return false;
}

function munge ($address) {
	$address = strtolower(htmlspecialchars(html_entity_decode($address)));
	$coded = "";
	$unmixedkey = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.@";
	$inprogresskey = $unmixedkey;
	$mixedkey = "";
	$unshuffled = strlen($unmixedkey);
	for ($i = 0; $i < strlen($unmixedkey); $i ++) {
		$ranpos = rand(0, $unshuffled - 1);
		$nextchar = (string)$inprogresskey{(int)$ranpos};
		$mixedkey .= $nextchar;
		$before = substr($inprogresskey, 0, $ranpos);
		$after = substr($inprogresskey, $ranpos + 1, $unshuffled - ($ranpos + 1));
		$inprogresskey = $before . '' . $after;
		$unshuffled -= 1;
	}
	$cipher = $mixedkey;
	$shift = strlen($address);
	$txt = "<script type=\"text/javascript\" language=\"javascript\">\n" .
		"<!--\n" .
		"// Email obfuscator script 2.1 by Tim Williams, University of Arizona\n" .
		"// Random encryption key feature by Andrew Moulden, Site Engineering Ltd\n" .
		"// PHP version coded by Ross Killen, Celtic Productions Ltd\n" .
		"// This code is freeware provided these six comment lines remain intact\n" .
		"// A wizard to generate this code is at http://www.jottings.com/obfuscator/\n" .
		"// The PHP code may be obtained from http://www.celticproductions.net/\n\n";
	for ($j = 0; $j < strlen($address); $j ++) {
		if (strpos($cipher, $address{$j}) === false ) {
			$chr = $address{$j};
			$coded .= $address{$j};
		} else {
			$chr = (strpos($cipher, $address{$j}) + $shift) % strlen($cipher);
			$coded .= $cipher{$chr};
		}
	}
	$txt .= "\ncoded = \"" . $coded . "\"\n  key = \"" . $cipher . "\"\n" .
		"  shift=coded.length\n  link=\"\"\n" .
		"  for (i=0; i<coded.length; i++) {\n" .
		"    if (key.indexOf(coded.charAt(i))==-1) {\n" .
		"      ltr = coded.charAt(i)\n" .
		"      link += (ltr)\n" .
		"    }\n" .
		"    else {     \n".
		"      ltr = (key.indexOf(coded.charAt(i)) - shift+key.length) % key.length\n".
		"      link += (key.charAt(ltr))\n".
		"    }\n".
		"  }\n".
		"document.write(\"<a href='mailto:\"+link+\"'>\"+link+\"</a>\")\n\n//-->\n" .
		"</script><noscript>N/A" .
		"</noscript>";
	return $txt;
}

function outputExtras ($extras) {
	if (is_array($extras)) {
		echo ', ' . substr(json_encode($extras), 1, -1);
		/*foreach ($extras as $k => $v) {
			echo ",\n\t\"" . $k . '": ';
			switch (gettype($v)) {
				case 'integer':
				case 'float':
					echo (int) $v;
					break;
				case 'boolean':
					echo ($v ? 'true' : 'false');
					break;
				case 'string':
					echo '"' . addslashes($v) . '"';
					break;
				case 'array':
					echo json_encode($v);
					break;
				default:
					echo 'null';
			}
		}*/
	} else if (is_string($extras)) echo $extras;
}

function createPeople ($personIDs) {
	if (!is_array($personIDs)) return false;
	$personIDs = array_unique($personIDs);
	$personIDsCleaned = array ();
	foreach ($personIDs as $thisPersonID) {
		if ((int) $thisPersonID) $personIDsCleaned[] = (int) $thisPersonID;
	}
	$personIDs = $personIDsCleaned;
	unset($personIDsCleaned);
	global $db;
	if (!$db->query('SELECT * FROM person WHERE personID IN (' . implode(', ', $personIDs) . ')')) {
		dbError($db);
		die ();
	}
	$people = array ();
	while ($r = $db->getRow(F_RECORD)) {
		$people[$r->v('personID')] = new Person ($r);
	}
	return $people;
}

function dateFormToTimestamp ($year, $month, $day) {
	global $monthNames;
	$timestamp = strtotime((int) $day . ' ' . $monthNames[(int) $month] . ' ' . (int) $year);
	return $timestamp;
}

function jsSafeString ($unsafeString) {
	return str_replace(array('"', "\r", "\n", "\0"), array('\"', '\r', '\n', '\0'), $unsafeString);
}

abstract class MarketPrototype {
	protected $error;
	protected $errorDetail;

	abstract public function validate ();

	public function getError () {
		return $this->error;
	}

	public function setError ($errorCode, $errorDetail = null, $errorSource = null) {
		if ($errorCode == E_DATABASE) {
			global $db;
			$this->error = E_DATABASE;
			list ($errorMessage, $q) = $db->getError();
			$this->errorDetail = $errorDetail . ' || ' . $errorMessage . ' || ' . $q;
		} else if ($errorCode == E_INVALID_DATA && is_array($errorDetail)) {
			$errorFields = array ();
			foreach ($this as $thisKey => $thisValue) {
				$thisKeyError = (in_array($thisKey, $errorDetail) ? '*' : null);
				$errorFields[$thisKey . $thisKeyError] = $thisValue;
			}
			// does this make sense? if we have cascading errors, this routine will get rid of any valid details
			$this->errorDetail = ($errorDetail ? $errorDetail : null);
			$errorDetail = $errorFields;
		} else $this->errorDetail = ($errorDetail ? $errorDetail : null);
		$this->error = $errorCode;
		// print_r($this->error);
		global $logger;
		if ($this->error) $logger->addEntry($this->errorDetail, $errorCode, $errorSource);
		/* if ($this->error) {
			switch ($config['debug']) {
				case LOG_FILE:
					$logfile = fopen($config['debugLogfile'], 'a');
					fwrite($logfile, '[' . strftime('%Y/%m/%d %H:%M:%S') . '] ' . $_SERVER['PHP_SELF'] . ': ' . $errorCodes[$this->error] . ' (' . (is_array($this->errorDetail) || is_object($this->errorDetail) ? serialize($this->errorDetail) : $this->errorDetail) . ")\n");
					fclose($logfile);
					break;
				case LOG_OUTPUT:
					if (!$GLOBALS['ajax']) {
						echo '<span style="color: #f30;">Error code: ' . $errorCodes[$this->error] . "<br/>\n";
						echo "Error detail: ";
						print_r($this->errorDetail);
						echo "</span><br/>\n";
					}
			}
		} */
	}

	public function clearError () {
		$this->error = false;
		$this->errorDetail = null;
	}

	public function getErrorDetail () {
		if (!$this->error) return false;
		return $this->errorDetail;
	}

	public function checkDate ($dateString) {
		return myCheckDate($dateString);
	}

	public function roundDate ($date, $roundTo = NULL) {
		return roundDate($date, $roundTo);
	}
}

abstract class MarketTree extends MarketPrototype {
	//protected $lft;
	//protected $rgt;
	protected $nodePath = array ();
	protected $sortOrder;
	protected $sortFields;
	protected $tempSortField;
	public $depth;
	protected $propsC = array ();

	public function getLft () {
		return null;
	}

	public function getRgt () {
		return null;
	}

	public function getNodePath ($includeThis) {
		return array_merge($this->nodePath, $includeThis ? array($this->getObjectID()) : array ());
	}

	public function isLeafNode () {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		global $db;
		if (!$db->query('SELECT * FROM '.$objectType.' WHERE nodePath = "'.$this->getPathString(true).'" AND '.$objectType.'ID != '.(int) $objectID) {
			$this->setError(E_DATABASE, 'on attempt to find children of this node', $objectType . '::isLeafNode()');
			return false;
		}
		return !($db->getNumRows());
	}

	public function isInTree () {
		$objectID = $this->getObjectID();
		return (bool) (is_array($this->nodePath) && count($this->nodePath));
	}

	public function getObjectType () {
		$objectType = $this->getObjectType();
		if ($objectType == 'orderitem') $objectType = 'item';
		return $objectType;
	}

	public function getObjectID () {
		$objectID = $this->getObjectType().'ID';
		return (int) $this->$objectID;
	}

	public function getPathString ($includeThis = false) {
		if (is_null($this->nodePath)) {
			return null;
		}
		$nodePath = $this->nodePath;
		if ($includeThis) {
			array_push($nodePath, $this->getObjectID());
		}
		$pathString = '/'.implode('/', $this->nodePath);
		if (strlen($pathString) > 1) {
			$pathString .= '/'; // add a trailing slash only if the path has depth > 0, cuz we don't want the path string to be '//'
		}
		return $pathString;
	}

	public function toNodePath ($pathString) {
		if (is_null($pathString)) return null; // if it has no place in the tree, return null
		if ($pathString == '/') {
			return array (); // if it's a root node, return an empty array
		}
		$pathString = substr($pathString, 1, -1);
		return explode('/', $pathString); // otherwise, split path string into array
	}

	// inheritance-safe object constructor, for children et al
	abstract public function newObject ($objectData);

	abstract protected function matchCriteria ($object, $criteria);

	public function getChildren ($sortField = 'contactName', $directOnly = true, $criteria = null) {
		// because this function is inherited by both Person and Item, first we have to get the object type for use in queries and references to properties
		$objectType = $this->getObjectType();
		$objectID = $this-getObjectID();
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'no objectID', $objectType . '::getChildren()');
			return false;
		}
		if (!is_array($criteria)) $criteria = array ();
		if (!in_array($sortField, $this->sortFields)) $sortField = 'sortOrder';
		global $db;
		$nodePath = $this->getPathString(true);
		if (!$db->query('
			SELECT * FROM '.$objectType.' WHERE nodePath '.($directOnly ? '- "'.$nodePath.'"' : 'LIKE "'.$nodePath.'%"'))) {
			$this->setError(E_DATABASE, 'on getting children', $objectType . '::getChildren()');
			return false;
		}
		$children = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$children[$r->v($objectType.'ID')] = $r;
		}
		if ($objectType == 'item') {
			if (!$db->query('SELECT * FROM prices WHERE itemID IN ('.implode(',', array_keys($children)).') ORDER BY '.$sortField)) {
				$this->setError(E_DATABASE, 'on getting prices for children', $objectType.'::getChildren()');
				return false;
			}
			$prices = array ();
			while ($r = $db->getRow(F_RECORD)) {
				$itemID = $r->v('itemID');
				if (!isset($prices[$itemID])) {
					$prices[$itemID] = array();
				}
				$price = new Price ($r);
				$prices[$itemID][$r->v('personID')] = $price;
			}
			foreach ($prices as $k => $v) {
				$children[$k]->s('prices', $v);
			}
		}
		foreach ($children as $k => $v) {
			$v = $this->newObject($v);
			if (is_array($criteria) && $this->matchCriteria($v, $criteria)) {
				$children[$k] = $v;
			} else {
				unset($children[$k]);
			}
		}
		$this->clearError();
		return $children;
	}

	public function getTree ($sortField = 'sortOrder', $format = 'list', $criteria = null) {
		if (!is_array($criteria)) $criteria = array ();
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID;
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'No objectID, or object has no tree sequence', $objectType . '::getTree()');
			return false;
		}
		if (!in_array($sortField, $this->sortFields)) $sortField = 'sortOrder';
		$children = $this->getChildren(null, false, $criteria);
		$this->tempSortField = $sortField;
		$tree = $this->treeify($children, $this->nodePath, $sortField);
		if ($format == 'objects') {
			$tree = $this->flatten($tree);
		}
		$this->tempSortField = null;
		return $tree;
	}

	public function treeify ($flat, $startPath) {
		$tree = array ();
		foreach ($flat as $k => $v) {
			$nodePath = array_diff($startPath, $v->getNodePath());
			if (count($nodePath) == 1) {
				unset($flat[$k]);
				$tree[$k] = array ();
				$tree[$k]['node'] = $v;
				$tree[$k]['children'] = $this->treeify($flat, $v->getNodePath());
			}
		}
		if ($this->tempSortField) {
			uasort($tree, array($this, 'sortBranch');
		}
		return $tree;
	}

	public function flatten ($tree) {
		$flat = array ();
		foreach ($tree as $k => $v) {
			$flat[$k] = $v['node'];
			if (count($tree['children'])) {
				$flat = array_merge($flat, $this->flatten($v['children']);
			}
		}
		return $flat;
	}

	public function traverseBranch (&$tree, &$treeNew, $nodeID) {

	}

	public function getSpotInTree ($sortOrder, $rootNodeID = 1, $trimLeafNodes = false) {
		// TODO: PERFORMANCE: pretty intensive; doesn't need to create the entire tree in an array. But for now, I'm just getting it created.
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$objectID || !$this->isInTree()) {
			$this->setError(E_NO_OBJECT_ID, 'No objectID, or object has no tree sequence', $objectType . '::getSpotInTree()');
			return false;
		}
		$rootNode = $this->newObject((int) $rootNodeID);
		$tree = $rootNode->getTree($sortOrder, null, array('isLeafNode' => !$trimLeafNodes));
		global $logger;
		$treeKeys = array_flip(array_keys($tree)); // take entire tree, in order, and turn the keys into a numeric array
		$logger->addEntry('getting spot for ' . $objectID . ' in tree... root node is ' . $rootNode->$objectID . ' (should be ' . $rootNodeID . '), root node tree is ' . count($tree) . ' nodes long');
		$logger->addEntry('spot in tree ' . (isset($treeKeys[$objectID]) ? 'exists and is ' . $treeKeys[$objectID] : 'does not exist'));
		return $treeKeys[$objectID];
	}

	public function getPath ($sortOrder = SORT_FORWARD) {
		switch ($sortOrder) {
			case SORT_FORWARD:
				return $this->nodePath;
			case SORT_REVERSE:
				return array_reverse($this->nodePath);
		}
	}

	public function getToken ($rel = 1) {
		$objectType = $this->getObjectType();
		if (is_object($rel)) {
			if ($rel->getObjectType() == $objectType) $rel = $rel->getObjectID();
			else {
				$this->setError(E_INVALID_DATA, 'relative object passed is not a proper type of object', $objectType . '::getDepth()');
				return false;
			}
		}
		$objectID = $this->getObjectID();
		if (!$this->isInTree() || !$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'No objectID (' . $objectID . '), or object has no tree sequence ', $objectType . '::getToken()');
			return false;
		}
		if ($path = $this->getPath()) {
			$path = array_reverse($path);
			foreach ($path as $i => $v) {
				$path[$i] = sprintf('%05s', $v);
				if ($v == $rel) break;
			}
			return 'node0_' . implode('_', array_reverse($path));
		} else return false;
	}

	public function getDepth ($rel = 1) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (is_object($rel) && $rel->getObjectType() != $objectType) {
			else {
				$this->setError(E_INVALID_DATA, 'relative object passed is not a proper type of object', $objectType . '::getDepth()');
				return false;
			}
		}
		if (!is_object($rel)) {
			if ($rel == $objectID) {
				$rel = $this;
			} else {
				$rel = $this->newObject($rel);
			}
		}
		if (!$this->isInTree() || !$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'No objectID (' . $objectID . '), or object has no tree sequence', $objectType . '::getDepth()');
			return false;
		}
		if (!$this->isIn($rel)) {
			$this->setError(E_INVALID_DATA, 'This object is not inside the passed object', $objectType.'::getDepth()');
			return false;
		}
		if (!$this->depth) {
			$this->depth = count($this->nodePath);
		}
		return $rel->getDepth() - $this->depth;
	}

	public function setParent ($parentID, $sortOrder = 0) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'no object ID; has to be saved before you can put it in the tree.', $objectType.'::setParent()');
			return false;
		}
		$parent = $this->newObject((int) $parentID);
		if ($parent->isIn($this)) {
			$this->setError(E_INVALID_DATA, 'Cannot set self or child to parent', $objectType.'::setParent()');
			return false;
		}
		global $logger, $db;
		$oldNodePath = $this->getNodePath();
		$oldPath = $this->getPathString(true);
		$newParent = $parent->getPathString(true);
		$isInTree = $this->isInTree();
		$db->startLogging();
		$db->start('setParent' . $parentID);
		if (!$db->query('UPDATE '.$objectType.' SET nodePath = "'.$newParent.'" WHERE '.$objectType.'ID = '.$objectID)) {
			$this->setError(E_DATABASE, 'on changing node paths for current node', $objectType.'::setParent()');
			return false;
		}
		$this->nodePath = $parent->getNodePath(true);
		if ($isInTree) {
			$newPath = $this->getPathString(true);
			if (!$db->query('UPDATE '.$objectType.' SET nodePath = REPLACE(nodePath, "'.$oldPath.'", "'.$newPath.'"')) {
				$this->setError(E_DATABASE, 'on changing node paths for children', $objectType.'::setParent()');
				$this->nodePath = $oldNodePath;
				return false;
			}
		}
		$db->commit('setParent' . $parentID);
		$db->stopLogging();
		return true;
	}

	public function isChildOf ($parent) {
		return $this->isIn($parent, false);
	}

	public function isParentOf ($child) {
		return $this->contains($child, false);
	}

	public function isIn ($parent, $includeThis = true) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!is_object($parent)) {
			$this->setError(E_INVALID_DATA, '$parent is not an object', $objectType . '::isIn()');
			return false;
		}
		if (get_class($parent) != get_class($this)) {
			$this->setError(E_INVALID_DATA, '$parent is a ' . get_class($parent) . ' but $this is a ' . get_class($this), $objectType . '::isIn()');
			return false;
		}
		$parentPath = $parent->getNodePath($includeThis);
		return in_array($objectID, $parentPath);
	}

	public function contains ($child, $includeThis = true) {
		$objectType = $this->getObjectType();
		if (!is_object($child)) {
			$this->setError(E_INVALID_DATA, '$child is not an object', $objectType . '::contains()');
			return false;
		}
		if (get_class($child) != get_class($this)) {
			$this->setError(E_INVALID_DATA, '$child is a ' . get_class($child) . ' but $this is a ' . get_class($this), $objectType . '::contains()');
			return false;
		}
		$childID = $child->getObjectID();
		return in_array($childID, $this->getNodePath($includeThis));
	}

	public function getActiveStates () {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'no objectID', $objectType . '::getActiveStates()');
			return false;
		}
		$tree = $this->getTree();
		$activeStates = array ();
		$isActive = $this->isActive();
		if (is_array($tree)) {
			foreach ($tree as $thisNode) {
				$thisPath = $thisNode->getPath();
				foreach ($thisPath as $i => $thisID) {
					$thisPath[$i] = sprintf('%05s', $thisID);
				}
				$path = 'node0_' . implode('_', $thisPath);
				if ($isActive) {
					$thisNodeState = (int) $thisNode->isActive();
				} else $thisNodeState = 0;
				$activeStates[$path] = $thisNodeState;
			}
		}
		return $activeStates;
	}

	private function moveOne ($direction) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'no objectID', $objectType . '::moveOne()');
			return false;
		}
		if ($this->lft == 1) {
			$this->setError(E_NO_OBJECT, 'can\'t move root object', $objectType . '::moveOne()');
			return false;
		}
		if (!in_array($direction, array (D_LFT, D_RGT))) {
			$this->setError(E_INVALID_DATA, '$direction is not D_LFT or D_RGT', $objectType . '::moveOne()');
			return false;
		}
		global $db;
		switch ($direction) {
			// 'Nbr' stands for 'neighbour' in the tree. If we want to move left, we want to grab the sibling whose rgt is this lft - 1, and vice versa
			case D_LFT:
				$sibNbr = 'rgt';
				$thisNbr = 'lft';
				break;
			case D_RGT:
				$sibNbr = 'lft';
				$thisNbr = 'rgt';
		}
		$t = 'move' . $objectID;
		$db->start($t);
		if (!$db->query('SELECT ' . $objectType . 'ID, lft, rgt FROM ' . $objectType . ' WHERE ' . $sibNbr . ' = ' . ($this->$thisNbr + $direction))) {
			$this->setError(E_DATABASE, 'on selecting neighbour\'s lfr and rgt values', $objectType . '::moveOne()');
			$db->rollback($t);
			return false;
		}
		if (!$sibling = $db->getRow(F_ASSOC)) {
			$this->setError(E_NO_OBJECT, 'object is already in ' . $thisNbr . 'most slot', $objectType . '::moveOne()');
			$db->rollback($t);
			return false;
		}
		if (!$db->query('UPDATE ' . $objectType . ' SET lft = lft * -1, rgt = rgt * -1 WHERE lft BETWEEN ' . $this->lft . ' AND ' . $this->rgt)) {
			$this->setError(E_DATABASE, 'on removing branch from tree', $objectType . '::moveOne()');
			$db->rollback($t);
			return false;
		}
		$shiftSibling = ($this->rgt - $this->lft + 1) * -$direction;
		if (!$db->query('UPDATE ' . $objectType . ' SET lft = lft + ' . $shiftSibling . ', rgt = rgt + ' . $shiftSibling . ' WHERE lft BETWEEN ' . $sibling['lft'] . ' AND ' . $sibling['rgt'])) {
			$this->setError(E_DATABASE, 'on moving sibling', $objectType . '::moveOne()');
			$db->rollback($t);
			return false;
		}
		$shiftThis = ($sibling['rgt'] - $sibling['lft'] + 1) * $direction;
		if (!$db->query('UPDATE ' . $objectType . ' SET lft = (lft - ' . $shiftThis . ') * -1, rgt = (rgt - ' . $shiftThis . ') * -1 WHERE lft < 0')) {
			$this->setError(E_DATABASE, 'on moving branch back into tree', $objectType . '::moveOne()');
			$db->rollback($t);
			return false;
		}
		$oldLft = $this->lft;
		$oldRgt = $this->rgt;
		$this->lft += $shiftThis;
		$this->rgt += $shiftThis;
		$db->commit($t);
		global $logger;
		$logger->addEntry('Moved ' . $objectType . ' ' . $objectID . ' from (' . $oldLft . ' - ' . $oldRgt . ') to (' . $this->lft . ' - ' . $this->rgt . ')', null, $objectType . '::moveOne()');
		$this->clearError();
		return $shiftThis / 2;
	}

	public function moveLeft () {
		return $this->moveOne(D_LFT);
	}

	public function moveRight () {
		return $this->moveOne(D_RGT);
	}

	public function getParentID () {
		return end($this->nodePath);
	}

	function getNeighbour ($dir = D_LFT, $field = 'lft') {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'no objectID', $objectType . '::getNeighbour()');
			return false;
		}
		if ($objectID == 1) return false;
		if (!$this->lft || !$this->rgt) {
			$this->setError(E_INVALID_DATA, 'object ' . $objectID . ' has no tree sequence', $objectType . '::getNeighbour()');
			return false;
		}
		global $db;
		switch ($field) {
			case 'lft':
				switch ($dir) {
					case D_LFT:
						$edge = 'lft';
						break;
					case D_RGT:
						$edge = 'rgt';
				}
				if (!$db->query('SELECT * FROM ' . $objectType . ' WHERE lft = ' . ($this->$edge + $dir) . ' OR rgt = ' . ($this->$edge + $dir))) {
					$this->setError(E_DATABASE, 'on query', $objectType . '::getNeighbour()');
					return false;
				}
				if (!$r = $db->getRow(F_RECORD)) {
					$this->setError(E_NO_OBJECT, 'no adjacent object ' . $dir . ' of ' . $objectID, $objectType . '::getNeighbour()');
					return false;
				}
				$neighbour = $this->newObject($r);
				break;
			default:
				$rootNode = $this->newObject(1);
				$tree = $rootNode->getTree($field);
				$treeKeys = array_keys($tree);
				$treeIndex = array_flip($treeKeys);
				$neighbourID = (isset($treeKeys[$treeIndex[$objectID] + $dir]) ? $treeKeys[$treeIndex[$objectID] + $dir] : null);
				$neighbour = $tree[$neighbourID];
		}
		return $neighbour;
	}

	public function getNeighbourID ($dir = D_LFT, $field = 'lft') {
		$objectType = $this->getObjectType();
		if ($objectType == 'orderitem') $objectType = 'item';
		$objectID = $objectType . 'ID';
		$neighbour = $this->getNeighbour();
		if (is_object($neighbour)) return $neighbour->$objectID;
		else return false;
	}

	public function isActive ($includeThis = true) {
		return $this->getProperty('active', $includeThis, I_PARENT_TRUE) ? true : false;
	}

	/* valid $inherit values and what they do!
		I_CHILD:       (default) child values take priority over parent
		I_CHILD_NULL:  child takes priority only if parent is null
		I_PARENT:      parent values take priority over child.
		I_PARENT_NULL: parent value takes priority over child only if child is null
		I_PARENT_TRUE: TRUE only inherits when all elements in the path are true;
		               FALSE takes priority over TRUE.  So far, only used for isActive().
	*/
	protected function getPropertyList ($property, $includeThis = true, $inherit = I_CHILD) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'no objectID', $objectType . '::getProperty(\'' . $property . '\')');
			return false;
		}
		if (isset($this->propsC)) {
			if (isset($this->propsC[$property])) {
				$thisProp = $this->propsC[$property];
				if ((bool) $thisProp['includeThis'] == (bool) $includeThis && $thisProp['inherit'] == $inherit) return $thisProp['v'];
			}
		}
		global $db;
		if (!$db->query('SELECT '.$objectType.'ID, '.$property.' FROM '.$objectType.' WHERE '.$objectType.'ID IN ('.implode(',', $this->getNodePath($includeThis)).') ORDER BY nodePath' . (($inherit == I_CHILD || $inherit == I_CHILD_NULL) ? null : ' DESC'))) {
			$this->setError(E_DATABASE, 'on query', $objectType . '::getProperty(\'' . $property . '\')');
			return false;
		}
		$propertyList = array ();
		while ($r = $db->getRow(F_RECORD)) {
			$v = $r->v($property);
			if ($v === 't') $v = true;
			if ($v === 'f') $v = false;
			$propertyList[$r->v($objectID)] = $v;
		}
		if (!isset($this->propsC)) $this->propsC = array ();
		$this->propsC[$property] = array ('includeThis' => (bool) $includeThis, 'inherit' => $inherit, 'v' => $propertyList);
		return $propertyList;
	}

	protected function getProperty ($property, $includeThis = true, $inherit = I_CHILD, $returnID = false) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$propertyList = $this->getPropertyList($property, $includeThis, $inherit)) {
			return false;
		}
		$propertyValue = null;
		$propertyID = null;
		$values = false;
		global $logger;
		foreach ($propertyList as $k => $v) {
			// inherits by default; only replaces parent's value if parent is null (no value yet) or true (inactive parents cannot have active children).
			switch ($inherit) {
				case I_PARENT_TRUE:
					if (!is_null($v) && ($propertyValue || is_null($propertyValue))){
						$propertyValue = $v;
						$propertyID = $k;
					}
					break;
				case I_PARENT:
				case I_CHILD:
					if (!is_null($v)){
						$propertyValue = $v;
						$propertyID = $k;
					}
					break;
				case I_PARENT_NULL:
				case I_CHILD_NULL:
					if (!is_null($v) && is_null($propertyValue)) {
						$propertyValue = $v;
						$propertyID = $k;
					}
			}
		}
		if ($returnID) return array ($propertyValue, $propertyID);
		else return $propertyValue;
	}

	public function getParent ($parentType = null) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		// TODO: Should I put an error handler here?
		if ($objectType != 'person') $parentType = null;
		if (!$objectID) {
			$this->setError(E_NO_OBJECT_ID, 'No '.$objectType.'ID', $object . '::getParent()');
			return false;
		}
		if ($objectType == 'person' && (int) $parentType && !array_key_exists($parentType, $GLOBALS['personTypes'])) {
			$this->setError(E_INVALID_DATA, '\'' . (string) $parentType . '\' is not a valid ' . $objectType . ' type', $objectType . '::getParent()');
			return false;
		}
		if ($objectID == 1) return false;
		if (!(int) $parentType) {
			return $this->newObject($this->getParentID());
		}
		global $db;
		if (!$db->query('SELECT * FROM ' . $objectType . ' WHERE '.$objectType.'ID IN ('.implode(',', $this->getNodePath()).')'.((int) $parentType ? ' AND ('.$objectType.'Type & '.(int) $parentType.' > 0)' : null).' ORDER BY nodePath DESC LIMIT 1', true)) {
			$this->setError(E_DATABASE, 'On retrieval of path', $object . '::getParent()');
			return false;
		}
		if ($r = $db->getRow(F_RECORD)) {
			$parent = $this->newObject($r);
			return $parent;
		} else return false;
	}

	public function getParentOfType ($parentType) {
		return $this->getParent($parentType);
	}

	protected function deleteFromTree ($deleteChildren = false) {
		$objectType = $this->getObjectType();
		$objectID = $this->getObjectID();
		if (!$this->isInTree()) {
			return true;
		}
		global $db;
		$db->start('deleteFromTree' . $objectID);
		$oldPath = $this->getPathString(true);
		$newPath = $this->getPathString();
		if (!$db->query('UPDATE ' . $objectType . ' SET nodePath = ' . ($deleteChildren ? 'NULL' : 'REPLACE(nodePath, "'.$oldPath.'", "'.$newPath.'")').' WHERE nodePath LIKE "'.$oldPath.'%"')) {
			$this->setError(E_DATABASE, 'on update of child nodes', $objectType . '::deleteFromTree()');
			$db->rollback('deleteFromTree' . $objectID);
			return false;
		}
		if (!$db->query('UPDATE ' . $objectType . ' SET nodePath = NULL WHERE '.$objectType.'ID = '.$objectID)) {
			$this->setError(E_DATABASE, 'on removal of node from tree', $objectType . '::deleteFromTree()');
			$db->rollback('deleteFromTree' . $objectID);
			return false;
		}
		$this->nodePath = null;
		$this->depth = null;
		$db->commit('deleteFromTree' . $objectID);
		global $logger;
		$logger->addEntry('Deleted ' . $objectType . ' ' . $objectID . ' from tree', null, $objectType . '::delete()');
		return true;
	}
}

abstract class DatabaseConnection {
	protected $dbType;
	public $host;
	public $username;
	public $database;
	protected $rsrc; // connection resource
	protected $q;
	protected $qResult; // result resource
	protected $error;
	protected $transactionState = false;
	protected $t;
	protected $trackTime;
	protected $log = false;
	protected $time;
	protected $lastID;

	abstract protected function dbPconnect ($host, $username, $password, $database);

	abstract protected function dbConnect ($host, $username, $password, $database);

	public function __construct ($host, $username, $password, $database, $persistent = false) {
		$time = microtime(true);
		switch ($persistent) {
			case true:
				$dbh = $this->dbPconnect($host, $username, $password, $database);
				break;
			case false:
				$dbh = $this->dbConnect($host, $username, $password, $database);
		}
		//if ($dbh) {
			if ($this->trackTime) $this->addTime(microtime(true) - $time);
			$this->host = $host;
			$this->username = $username;
			$this->rsrc = $dbh;
			$this->database = $database;
			$this->error = false;
			$this->lastID = null;
			//return true;
		//} else {
			// $this->error = @mysql_error($dbh);
			if ($this->trackTime) $this->addTime(microtime(true) - $time);
			global $config;
			if ($config['logType'] && !$dbh) {
				if (isset($GLOBALS['logger'])) $GLOBALS['logger']->addEntry('Can\'t connect to database ' . $host, E_DATABASE, 'DatabaseConnection::__construct()');
			}
			if ($dbh) return true;
			else {
				$this->error = 'Can\'t connect to database';
				return false;
			}
		// }
	}

	public function __destruct () {
		global $config;
		if ($this->transactionState) {
			if ($config['logType']) {
				if ($GLOBALS['logger']) {
					global $logger;
					$logger->addEntry('Warning! finishing script with a running transaction ' . $this->t . '. Rolling back transaction.', E_DATABASE, 'DatabaseConnection::__destruct()');
				}
			}
			$this->rollback($this->t);
		}
	}

	abstract protected function checkConnection ();

	abstract protected function dbCheckResult ();

	/* public function checkConnection () {
		if (substr(@get_resource_type($this->rsrc), 0, 10) == 'mysql link') return true;
		else return false;
	} */

	public function startLogging () {
		$this->log = true;
	}

	public function stopLogging () {
		$this->log = false;
	}

	abstract protected function dbQuery ($q);

	abstract protected function dbLastID ();

	abstract protected function dbError ();

	abstract protected function dbNumRows ();

	public function query ($q, $logQuery = null, $updateLastID = true) {
		$this->error = null;
		$this->queryResult = null;
		if (is_null($logQuery)) $logQuery = $this->log;
		$q = trim($q);
		$isInsert = (strtolower(substr($q, 0, 6)) == 'insert');
		if ($this->checkConnection()) {
			$this->query = $q;
			global $config;
			$time = microtime(true);
			if ($result = $this->dbQuery($q)) {
				$this->queryResult = $result;
				if ($updateLastID && $isInsert) $this->lastID = $this->dbLastID();
				if ($config['logType'] && $logQuery) {
					if ($GLOBALS['logger']) {
						global $logger;
						$logger->addEntry('Successful query: ' . $this->query);
					}
				}
				if ($this->trackTime) $this->addTime(microtime(true) - $time);
				return true;
			} else {
				if ($updateLastID) $this->lastID = null;
				if ($config['logType'] && $logQuery) {
					if ($GLOBALS['logger']) {
						global $logger;
						$logger->addEntry('Failed query: ' . $this->query, E_DATABASE);
					}
				}
				if ($this->trackTime) $this->addTime(microtime(true) - $time);
				$this->error = $this->dbError();
				return false;
			}
		} else return false;
	}

	public function getQueryString () {
		return $this->query;
	}

	public function getNumRows () {
		$this->error = null;
		if ($this->checkConnection()) {
			if ($this->dbCheckResult()) {
				$time = microtime(true);
				$numRows = $this->dbNumRows();
				if ($this->trackTime) $this->addTime(microtime(true) - $time);
				return $numRows;
			} else {
				$this->error = $this->dbError();
				return false;
			}
		} else return false;
	}

	abstract protected function dbFetchRow ();

	abstract protected function dbFetchAssoc ();

	public function getRow ($fetchMode = F_ASSOC) {
		if ($fetchMode != F_NUM && $fetchMode != F_RECORD) $fetchMode = F_ASSOC;
		$this->error = null;
		if ($this->checkConnection()) {
			if ($this->dbCheckResult()) {
				$time = microtime(true);
				switch ($fetchMode) {
					case F_NUM:
						$success = ($row = $this->dbFetchRow());
						break;
					case F_ASSOC:
					case F_RECORD:
						$success = ($row = $this->dbFetchAssoc());
				}
				if ($success) {
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					switch ($fetchMode) {
						case F_NUM:
						case F_ASSOC:
							return $row;
							break;
						case F_RECORD:
							return new Record ($row);
					}
				} else {
					$this->error = $this->dbError();
					return false;
				}
			} else return false;
		} else return false;
	}

	public function dbBegin () {
		return $this->dbStart();
	}

	abstract protected function dbStart ();

	abstract protected function dbCommit ();

	abstract protected function dbRollback ();

	public function begin ($t = null, $logQuery = null) {
		return $this->start($t, $logQuery);
	}

	// TODO: Should I have a stack rather than just one variable? That way, it'll make sure transactions are 'nested' properly.
	public function start ($t = null, $logQuery = null) {
		global $config;
		if (is_null($logQuery)) $logQuery = $this->log;
		// $this->error = null;
		// $this->query = 'START TRANSACTION';
		if ($this->checkConnection()) {
			if (!$this->transactionState) {
				$time = microtime(true);
				global $logger;
				if ($this->dbStart()) {
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					if ($config['logType'] && $logQuery) {
						if ($GLOBALS['logger']) {
							$logger->addEntry('Starting transaction ' . $t);
						}
					}
					$this->transactionState = true;
					$this->t = ($this->t ? $this->t : $t);
					return true;
				} else {
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					if ($config['logType'] && $logQuery) {
						if ($GLOBALS['logger']) {
							global $logger;
							$logger->addEntry('Failed transaction start ' . $t, E_DATABASE);
						}
					}
					$this->error = $this->dbError();
					return false;
				}
			} else {
				/* if ($config['logType'] && $logQuery) {
					global $logger;
					$logger->addEntry('Cannot start transaction ' . $t . ', ' . $this->t . ' already started');
				} */
				return true;
			}
		} else return false;
	}

	public function commit ($t = null, $logQuery = null) {
		global $config;
		if (is_null($logQuery)) $logQuery = $this->log;
		// $this->error = null;
		// $this->query = 'COMMIT';
		if ($this->checkConnection()) {
			if ($this->transactionState && $t == $this->t) {
				$time = microtime(true);
				if ($this->dbCommit()) {
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					$this->t = null;
					$this->transactionState = false;
					if ($config['logType'] && $logQuery) {
						if (isset($GLOBALS['logger'])) {
							global $logger;
							$logger->clearBuffer();
							$logger->addEntry('Committing transaction ' . $t);
						}
					}
					return true;
				} else {
					if ($config['logType']) {
						if (isset($GLOBALS['logger'])) {
							global $logger;
							$logger->clearBuffer();
							if ($logQuery) $logger->addEntry('Failed transaction commit ' . $t, E_DATABASE);
						}
					}
					$this->error = $this->dbError();
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					return false;
				}
			} else return true;
		} else return false;
	}

	public function rollback ($t = null, $logQuery = null) {
		global $config;
		if (is_null($logQuery)) $logQuery = $this->log;
		// $this->error = null;
		// $this->query = 'ROLLBACK';
		if ($this->checkConnection()) {
			if ($this->transactionState && $t == $this->t) {
				$time = microtime(true);
				if ($this->dbRollback()) {
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					$this->t = null;
					$this->transactionState = false;
					if ($config['logType']) {
						if (isset($GLOBALS['logger'])) {
							global $logger;
							$logger->flushBuffer();
							if ($logQuery) $logger->addEntry('Rolling back transaction ' . $t);
						}
					}
					return true;
				} else {
					if ($config['logType'] && $logQuery) {
						if (isset($GLOBALS['logger'])) {
							global $logger;
							$logger->addEntry('Failed transaction rollback ' . $t, E_DATABASE);
						}
					}
					$this->error = $this->dbError();
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					return false;
				}
			} else return true;
		} else return false;
	}

	public function abort () {
		global $config;
		if (is_null($logQuery)) $logQuery = $this->log;
		// $this->error = null;
		// $this->query = 'ROLLBACK';
		if ($this->checkConnection()) {
			if ($this->transactionState) {
				$time = microtime(true);
				if ($this->dbRollback()) {
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					$this->t = null;
					$this->transactionState = false;
					if ($config['logType']) {
						if (isset($GLOBALS['logger'])) {
							global $logger;
							$logger->flushBuffer();
							if ($logQuery) $logger->addEntry('Rolling back transaction ' . $t);
						}
					}
					return true;
				} else {
					if ($config['logType'] && $logQuery) {
						if (isset($GLOBALS['logger'])) {
							global $logger;
							$logger->addEntry('Failed transaction rollback ' . $t, E_DATABASE);
						}
					}
					$this->error = $this->dbError();
					if ($this->trackTime) $this->addTime(microtime(true) - $time);
					return false;
				}
			} else return true;
		} else return false;
	}

	public function getKey () {
		if ($this->transactionState) {
			return ($this->t ? $this->t : true);
		} else return false;
	}

	public function getError () {
		if ($this->error) return array ($this->error, $this->query);
		else return false;
	}

	// changed functionality -- database logger was screwing things up, because it was generating its own auto_inc
	public function getLastID () {
		return $this->lastID;
	}

	public function startProfiling () {
		if (!$this->trackTime) {
			$this->trackTime = true;
			$this->time = 0;
		}
	}

	public function getTime () {
		if ($this->trackTime) return $this->time;
		else return false;
	}

	public function addTime ($time) {
		if ($this->trackTime) $this->time += $time;
	}

	public function pauseProfiling () {
		if ($this->trackTime) $this->trackTime = false;
	}

	public function stopProfiling () {
		$this->trackTime = false;
		$this->time = null;
	}

	abstract public function cleanString ($string);

	public function cleanDate ($date) {
		if ((int) $date) return strftime('%Y-%m-%d %H:%M:%S', (int) $date);
		else return false;
	}

	abstract public function getDBType ();
}

class DatabaseConnectionMySQL extends DatabaseConnection {
	protected $dbType = DB_MYSQL;

	protected function dbPconnect ($host, $username, $password, $database) {
		// TODO: HOLY INJECTION, BATMAN! security hole here; no checking!
		if ($dbh = mysql_pconnect($host, $username, $password)) {
			$this->rsrc = $dbh;
			if (!$this->selectDatabase($database, $dbh)) return false;
		} else {
			global $config;
			if ($config['logType']) {
				global $logger;
				$logger->addEntry('Could not connect to database ' . $database . ' on server ' . $host, E_DATABASE, 'DatabaseConnection::dbPconnect()');
			}
			$this->error = 'Could not connect to database';
			return false;
		}
		return $dbh;
	}

	protected function dbConnect ($host, $username, $password, $database) {
		// TODO: HOLY INJECTION, BATMAN! security hole here; no checking!
		if ($dbh = mysql_connect($host, $username, $password)) {
			$this->rsrc = $dbh;
			if (!$this->selectDatabase($database, $dbh)) return false;
		} else {
			global $config;
			if ($config['logType']) {
				global $logger;
				$logger->addEntry('Could not connect to database ' . $database . ' on server ' . $host, E_DATABASE, 'DatabaseConnection::dbPconnect()');
			}
			$this->error = 'Could not connect to database';
			return false;
		}
		return $dbh;
	}

	protected function selectDatabase ($database, $dbh) {
		$this->error = null;
		if ($this->checkConnection()) {
			$time = microtime(true);
			if (@mysql_select_db($database, $dbh)) {
				if ($this->trackTime) $this->addTime(microtime(true) - $time);
				// $this->database = $database;
				return true;
			} else {
				$this->error = mysql_error($dbh);
				echo $this->error;
				if ($this->trackTime) $this->addTime(microtime(true) - $time);
				return false;
			}
		} else {
			global $config;
			if ($config['logType']) {
				global $logger;
				$logger->addEntry('No database connection ' . $database . ' on server ' . $host, E_DATABASE, 'DatabaseConnection::selectDatabase()');
			}
			$this->error = 'No database connection';
			return false;
		}
	}

	protected function checkConnection () {
		if (!is_resource($this->rsrc)) return false;
		if (substr(get_resource_type($this->rsrc), 0, 10) == 'mysql link') return true;
		else return false;
	}

	protected function dbCheckResult () {
		if (!is_resource($this->queryResult)) return false;
		if (get_resource_type($this->queryResult) == 'mysql result') return true;
		else return false;
	}

	protected function dbQuery ($q) {
		return @mysql_query($q, $this->rsrc);
	}

	protected function dbLastID () {
		return @mysql_insert_id($this->rsrc);
	}

	protected function dbError () {
		return @mysql_error($this->rsrc);
	}

	protected function dbNumRows () {
		return @mysql_num_rows($this->queryResult);
	}

	protected function dbFetchRow () {
		return @mysql_fetch_row($this->queryResult);
	}

	protected function dbFetchAssoc () {
		return @mysql_fetch_assoc($this->queryResult);
	}

	protected function dbStart () {
		return @mysql_query('START TRANSACTION', $this->rsrc);
	}

	protected function dbCommit () {
		return @mysql_query('COMMIT', $this->rsrc);
	}

	protected function dbRollback () {
		return @mysql_query('ROLLBACK', $this->rsrc);
	}

	public function cleanString ($string) {
		$string = mysql_real_escape_string(get_magic_quotes_gpc() ? stripslashes($string) : $string);
		return $string;
	}

	public function getDBType () {
		return $this->dbType;
	}
}

class DatabaseConnectionPGSQL extends DatabaseConnection {
	protected $dbType = DB_PGSQL;

	protected function dbPconnect ($host, $username, $password, $database) {
		// TODO: HOLY INJECTION, BATMAN! security hole here; no checking!
		return pg_pconnect("host='$host' user='$username' password='$password' dbname='$database'");
	}

	protected function dbConnect ($host, $username, $password, $database) {
		// TODO: HOLY INJECTION, BATMAN! security hole here; no checking!
		return @pg_connect("host='$host' user='$username' password='$password' dbname='$database'");
	}

	protected function checkConnection () {
		if (substr(get_resource_type($this->rsrc), 0, 10) == 'pgsql link') return true;
		else return false;
	}

	protected function dbCheckResult () {
		if (is_resource($this->queryResult)) {
			if (get_resource_type($this->queryResult) == 'pgsql result') return true;
			else return false;
		} else return false;
	}

	protected function dbQuery ($q) {
		$this->pgError = null;
		$qs = @pg_send_query($this->rsrc, $q);
		if (!$qs) {
			$this->pgError = @pg_last_error($this->rsrc);
			return false;
		}
		while (@pg_connection_busy($this->rsrc)) { }
		$result = @pg_get_result($this->rsrc);
		if (!$result) {
			$this->pgError = @pg_last_error($this->rsrc);
			return false;
		}
		if ($pgError = @pg_result_error($result)) {
			$this->pgError = $pgError;
			return false;
		} else {
			$this->pgError = null;
			return $result;
		}
	}

	protected function dbLastID () {
		if (strtoupper(substr($this->query, 0, 6)) != 'INSERT') return false;
		if (eregi('into[[:space:]]+[[:alpha:]_][[:alnum:]_]*[[:space:]]+', $this->query, $tableName)) {
			$tableName = trim(eregi_replace('[[:space:]]+', ' ', strtolower($tableName[0])));
			list ($a, $tableName) = explode(' ', $tableName);
			$q = 'SELECT key_column_usage.column_name
FROM information_schema.table_constraints, information_schema.key_column_usage
WHERE table_constraints.table_name = \'' . $tableName . '\'
AND key_column_usage.table_name = \'' . $tableName . '\'
AND table_constraints.constraint_type = \'PRIMARY KEY\'
AND key_column_usage.constraint_name = table_constraints.constraint_name';
			if (!$result = @pg_query($this->rsrc, $q)) return false;
			while ($r = @pg_fetch_assoc($result)) {
				if (isset($tableInfo)) return false;
				$tableInfo = $r;
			}
			if (!isset($tableInfo)) return false;
			$seqName = $tableName . '_' . $tableInfo['column_name'] . '_seq';
			$q = 'SELECT CURRVAL (\'' . $seqName . '\') as lastID';
			if (!$result = @pg_query($this->rsrc, $q)) return false;
			if (!$lastID = @pg_fetch_assoc($result)) return false;
			return $lastID['lastid'];
		} else return false;
	}

	protected function dbError () {
		return $this->pgError;
	}

	protected function dbNumRows () {
		return @pg_num_rows($this->queryResult);
	}

	protected function dbFetchRow () {
		return @pg_fetch_row($this->queryResult);
	}

	protected function dbFetchAssoc () {
		return @pg_fetch_assoc($this->queryResult);
	}

	protected function dbStart () {
		global $logger;
		return @pg_query($this->rsrc, 'BEGIN');
	}

	protected function dbCommit () {
		global $logger;
		return @pg_query($this->rsrc, 'COMMIT');
	}

	protected function dbRollback () {
		global $logger;
		return @pg_query($this->rsrc, 'ROLLBACK');
	}

	public function cleanString ($string) {
		$string = pg_escape_string(get_magic_quotes_gpc() ? stripslashes($string) : $string);
		return $string;
	}

	public function getDBType () {
		return $this->dbType;
	}
}

// generic class for turning an array into an object that can be
// manipulated with more versatility. Primary reason was to deal with
// case-sensitivity disagreement between results in MySQL and Postgres
class Record {
	public $r = array ();

	public function __construct ($r) {
		if (!is_array($r)) return false;
		$this->r = $r;
		return true;
	}

	// retrieve the value
	public function v ($k) {
		if (!array_key_exists($k, $this->r) && array_key_exists(strtolower($k), $this->r)) $k = strtolower($k);
		if (!array_key_exists($k, $this->r)) return null;
		global $logger;
		if (is_numeric($this->r[$k])) {
			if ((int) $this->r[$k] == $this->r[$k]) return (int) $this->r[$k];
			else if ((float) $this->r[$k] == $this->r[$k]) return (float) $this->r[$k];
		} else return $this->r[$k];
	}

	// retrieve a boolean of the value, with a 't'/'f' -> true/false line for Postgres
	public function b ($k) {
		$v = $this->v($k);
		if ($v === 't' || $v === 'f') {
			$v = ($v == 't' ? true : false);
		}
		else $v = (bool) (int) $v;
		return $v;
	}

	// check to see if value exists
	public function e ($k) {
		return isset($this->r[$k]);
	}

	// sets a value
	public function s ($k, $v) {
		if (is_object($k) || is_array($k) || is_resource($k)) return false;
		$this->r[$k] = $v;
		return true;
	}

	// deletes a value
	public function d ($k) {
		if (isset($this->r[$k])) {
			unset($this->r[$k]);
			return true;
		} else return false;
	}
}

class Logger {
	private $logType = null;
	private $logFile = null;
	private $buffer = array ();

	public function __construct ($logType = null, $logFile = null) {
		if (is_null($logType)) {
			global $config;
			$logType = $config['logType'];
		}
		$this->clearBuffer();
		switch ($logType) {
			case LOG_NONE:
				$this->logType = LOG_NONE;
				$this->logFile = null;
				return false;
			case LOG_FILE:
				if (!file_exists($logFile)) {
					// if (ini_get('display_errors')) echo "Logger: file doesn\'t exist\n";
					return false;
				}
				if (!$this->logFile = fopen($logFile, 'a')) {
					// if (ini_get('display_errors')) echo "Logger: could not open file for read/write\n";
					return false;
				}
				$this->logType = LOG_FILE;
				return true;
			case LOG_OUTPUT:
				$this->logType = LOG_OUTPUT;
				$this->logFile = null;
				return true;
			case LOG_DB:
				$this->logType = LOG_DB;
				$this->logFile = null;
				return true;
			case LOG_CONSOLE:
				$this->logType = LOG_CONSOLE;
				$this->logFile = null;
				return true;
			default:
				$this->logType = null;
				$this->logFile = null;
				return false;
		}
	}

	public function __destruct () {
		$this->flushBuffer();
	}

	public function addEntry ($entryText, $errorCode = 0, $source = null) {
		global $errorCodes;
		if (isset($GLOBALS['user'])) {
			global $user;
			if (isset($user)) {
				if (is_object($user)) $personID = $user->personID;
			}
		} else $personID = null;
		if (is_array($entryText)) $entryText = serialize($entryText);
		switch ($this->logType) {
			case LOG_FILE:
				if (@get_resource_type($this->logFile) != 'stream') {
					if (ini_get('display_errors')) echo "Logger: log file is not a valid file stream\n";
					return false;
				}
				fwrite($this->logFile, '[' . strftime('%Y/%m/%d %H:%M:%S') . '] ' . basename($_SERVER['SCRIPT_FILENAME']) . ': ' . ($source ? $source . ': ' : null) . ($errorCode ? $errorCodes[$errorCode] . ' - ' : null) . (is_array($entryText) || is_object($entryText) ? print_r($entryText, true) : $entryText) . "\n");
				return true;
			case LOG_OUTPUT:
				if (!$GLOBALS['ajax']) {
					echo '<span class="log' . ($errorCode ? 'Error">Error code: ' . $errorCodes[$errorCode] . "<br/>\n" : '">');
					echo "Detail: ".htmlEscape(print_r($entryText, true))."</span><br/>\n";
				}
				return true;
			case LOG_DB:
				global $db;
				$db->query('SELECT MIN(logEntryID) AS firstID FROM logEntry GROUP BY logEntryID ORDER BY logEntryID LIMIT ALL OFFSET 1000', false, false);
				if ($r = $db->getRow(F_RECORD)) {
					$db->query('DELETE FROM logEntry WHERE logEntryID < ' . (int) $r->v('firstID'), false, false);
				}
				$q = 'INSERT INTO logEntry (dateCreated, errorCode, page, source, requestVars, sessionVars, entryText, personID) VALUES (NOW(), ' . (int) $errorCode . ', \'' . $db->cleanString($_SERVER['PHP_SELF']) . '\', ' . ($source ? '\'' . $db->cleanString($source) . '\'' : 'null') . ', \'' . $db->cleanString(serialize($_REQUEST)) . '\', \'' . $db->cleanString(serialize($_SESSION)) . '\', \'' . $db->cleanString($entryText) . '\', ' . ($personID ? (int) $personID : 'null') . ')';
				if ($db->getKey()) $this->buffer[] = $q;
				else if (count($this->buffer)) $this->flushBuffer();
				$db->query($q, false, false);
				return true;
			case LOG_CONSOLE:
				$this->buffer[] = '[' . strftime('%Y/%m/%d %H:%M:%S') . '] ' . basename($_SERVER['SCRIPT_FILENAME']) . ': ' . ($source ? $source . ': ' : null) . ($errorCode ? $errorCodes[$errorCode] . ' - ' : null) . (is_array($entryText) || is_object($entryText) ? print_r($entryText, true) : $entryText) . "\n";
				return true;
			case LOG_NONE:
			default:
				return false;
		}
	}

	public function getEntries ($dateStart = null, $dateEnd = null, $errorCode = null) {
		$dateStart = myCheckDate($dateStart);
		$dateEnd = myCheckDate($dateEnd);
		if (!$dateEnd) $dateEnd = $dateStart + T_DAY - 1;
		switch ($this->logType) {
			case LOG_DB:
				global $db;
				$q = 'SELECT * FROM logEntry WHERE ';
				if (is_array($errorCode)) {
					foreach ($errorCode as $i => $thisCode) {
						$errorCode[$i] = (int) $thisCode;
					}
					$errorCode = array_unique($errorCode);
					$q .= 'errorCode IN (' . implode(', ', $errorCode) . ') AND';
				} else if (!is_null($errorCode)) {
					$q .= 'errorCode = ' . (int) $errorCode . ' AND';
				}
				$q .= ' dateCreated BETWEEN';
				if ($dateStart < $dateEnd) $q .= ' \'' . $db->cleanDate($dateStart) . '\' AND \'' . $db->cleanDate($dateEnd) . '\'';
				else $q .= ' \'' . $db->cleanDate($dateEnd) . '\' AND \'' . $db->cleanDate($dateStart) . '\'';
				$q .= ' ORDER BY dateCreated';
				if ($db->query($q)) {
					$entries = array ();
					while ($r = $db->getRow(F_RECORD)) {
						$entries[$r->v('logEntryID')] = $r;
					}
					return $entries;
				} else return false;
			case LOG_FILE:
				if (@getResourceType($this->logFile) != 'file') return false;
			default:
				return false;
		}
	}

	public function clearBuffer () {
		$this->buffer = array ();
	}

	public function flushBuffer () {
		if (!count($this->buffer)) return false;
		switch ($this->logType) {
			case LOG_DB:
				global $db;
				if (!$db->getKey()) {
					foreach ($this->buffer as $thisQuery) {
						$db->query($thisQuery, false, false);
					}
					break;
				}
				return false;
			case LOG_CONSOLE:
				global $json, $ajax;
				if ($json || $ajax) {
					$this->clearBuffer();
					return false;
				}
				echo "<script type=\"text/javascript\">\n";
				foreach ($this->buffer as $v) {
					echo 'console.log(' . json_encode($v) . ");\n";
				}
				echo "</script>\n";
		}
		$this->clearBuffer();
		return true;
	}

	public function getLogType () {
		return $this->logType;
	}
}

class Image extends MarketPrototype {
	public $filename;
	public $path;
	public $mimetype;

	public function __construct ($filename, $fromUpload = true) {
		global $config;
		if (!file_exists($filename)) {
			$this->setError(E_IMAGE_FAILED, 'Image file ' . $filename . ' doesn\'t exist', 'Image::__construct()');
			return false;
		}
		$this->filename = $filename;
		if ($fromUpload) {
			$tmpname = md5sum(time());
			if (!move_uploaded_file($filename, 'tmp/' . $tmpname)) {
				$this->setError(E_IMAGE_FAILED, 'Image file ' . $filename . ' couldn\'t be moved to tmp location tmp/' . $tmpname, 'Image::__construct()');
				return false;
			}
		}
	}

	public function validate () { }

	public function addImage ($tempFileLocation) {
		// TODO: flesh out error checking routines for each step
		global $config;
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::addImage()');
			return false;
		}
		if (!move_uploaded_file($tempFileLocation, 'productImages/' . $this->itemID . '_t.jpg')) {
			$this->setError(E_IMAGE_FAILED, 'no such file in location ' . $tempFileLocation, 'Item::addImage()');
			return false;
		} else $tempFileLocation = 'productImages/' . $this->itemID . '_t.jpg';
		$src = @imagecreatefromjpeg($tempFileLocation);
		if (!$src) {
			$this->setError(E_INVALID_DATA, 'Invalid image!', 'Image::addImage()');
			return false;
		}
		$t_max_w = 150;
		$t_max_h = 110;
		$m_max = 600;
		$orig_w = imagesx($src);
		$orig_h = imagesy($src);
		if ($orig_w > $orig_h ) {//landscape
			$crop_w = round($orig_w * ($t_max_h / $orig_h));
			$crop_h = $t_max_h;
			$src_x = ceil(($orig_w - $orig_h) / 2);
			$src_y = 0;
			$new_w = $m_max;
			$new_h = ceil($orig_h * ($m_max / $orig_h));
		} elseif ($orig_w < $orig_h ) {//portrait
			$crop_h = round($orig_h * ($t_max_w / $orig_w));
			$crop_w = $t_max_w;
			$src_y = ceil(($orig_h - $orig_w) / 2);
			$src_x = 0;
			$new_h = $m_max;
			$new_w = ceil($orig_w * ($m_max / $orig_w));
		} else {//square
			$crop_w = $crop_h = $t_max;
			$src_x = $src_y = 0;
		}
		if (!$dest_t = imagecreatetruecolor($t_max_w, $t_max_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create thumbnail canvas', 'Image::addImage()');
			return false;
		}
		if (!imagecopyresampled($dest_t, $src, 0 , 0 , $src_x, $src_y, $crop_w, $crop_h, $orig_w, $orig_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt resample thumbnail', 'Image::addImage()');
			return false;
		}
		if (!imagejpeg($dest_t, 'productImages/' . (int) $this->itemID . '_s.jpg')) {
			$this->setError(E_IMAGE_FAILED, 'Didnt save thumbnail', 'Image::addImage()');
			return false;
		}
		if (!$dest_m = imagecreatetruecolor($new_w, $new_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create med canvas', 'Image::addImage()');
			return false;
		}
		if (!imagecopyresampled($dest_m, $src, 0 , 0 , 0, 0, $new_w, $new_h, $orig_w, $orig_h)) {
			$this->setError(E_IMAGE_FAILED, 'Didnt resample med img', 'Image::addImage()');
			return false;
		}
		if (!imagejpeg($dest_m, 'productImages/' . (int) $this->itemID . '_m.jpg')) {
			$this->setError(E_IMAGE_FAILED, 'Didnt create thumbnail canvas', 'Image::addImage()');
			return false;
		}
		unlink($tempFileLocation);
		$this->image = true;
		global $db;
		if (!$db->query('UPDATE item SET image = TRUE WHERE itemID = ' . (int) $this->itemID)) {
			$this->setError(E_DATABASE, 'on update of item ' . $this->itemID, 'Item::addImage()');
			return false;
		}
		global $logger;
		$logger->addEntry('Added image and thumbnail for item ' . $this->itemID, null, 'Item::addImage()');
		$this->clearError();
		return true;
	}

	function removeImage () {
		if (!$this->itemID) {
			$this->setError(E_NO_OBJECT_ID, 'no itemID', 'Item::removeImage()');
			return false;
		}
		if (file_exists('productImages/' . $this->itemID . '_s.jpg')) unlink ('productImages/' . $this->itemID . '_s.jpg');
		if (file_exists('productImages/' . $this->itemID . '_m.jpg')) unlink ('productImages/' . $this->itemID . '_m.jpg');
		$this->image = false;
		global $db;
		if (!$db->query('UPDATE item SET image = FALSE WHERE itemID = ' . (int) $this->itemID)) {
			$this->setError(E_DATABASE, 'on update of item ' . $this->itemID, 'Item::removeImage()');
			return false;
		}
		global $logger;
		$logger->addEntry('Removed image from item ' . $this->itemID, null, 'Item::addImage()');
		$this->clearError();
		return true;
	}
}

class Date {
	public static function check ($ts) {
		if ((string) $ts == (string) (int) $ts && $ts) $ts = (int) $ts;
		if ($ts && !is_int($ts)) {
			return strtotime($ts);
		} else if (is_int($ts)) return $ts;
		else if (!$ts) return null;
		else return false;
	}

	public static function round ($ts = null, $period = T_DAY) {
		$ts = self::check($ts);
		if (!is_int($ts)) return $ts;
		switch ((int) $period) {
			case T_DAY:
				$ts = strtotime(strftime('%d %B %Y', $ts));
				break;
			case T_WEEK:
				$ts = strtotime('last Sunday', $ts + T_DAY);
				break;
			case T_MONTH:
				$ts = getdate($ts);
				$ts = strtotime($ts['year'] . '-' . $ts['month'] . '-01');
				break;
			case T_YEAR:
				$ts = getdate($ts);
				$ts = strtotime($ts['year'] . '-01-01');
		}
		return $ts;
	}

	public static function addMonths ($d, $ts) {
		$ts = self::check($ts);
		if (!is_int($ts)) return $ts;
		$d = (int) $d;
		if (!$d) return $ts;
		global $logger;
		$tsD = getdate($ts);
		if ($tsD['mday'] > 28) {
			$tsD['mdayD'] = $tsD['mday'] - 28;
			$tsD['mday'] = 28;
		}
		while ($tsD['mon'] + $d > 12) {
			$tsD['year'] ++;
			$d -= 12;
		}
		$ts2 = strtotime($tsD['year'] . '-' . ($tsD['mon'] + $d) . '-' . $tsD['mday']);
		$ts2D = getdate($ts2);
		if (isset($tsD['mdayD'])) {
			$lastDay = (int) date('t', $ts2);
			$dayD = $lastDay - $ts2D['mday'];
			$ts2D['mday'] = min(array($ts2D['mday'] + $dayD, $ts2D['mday'] + $tsD['mdayD']));
		}
		return strtotime($ts2D['year'] . '-' . $ts2D['mon'] . '-' . $ts2D['mday']);
	}

	public static function subMonths ($d, $ts) {
		return self::addMonths(0 - $d, $ts);
	}

	public static function human ($ts = 0, $period = 0, $includeStart = false) {
		$ts = self::round($ts);
		$period = (int) $period;
		if (!$ts && !$period) return false;
		global $config;
		if ($period) {
			if (!($period % T_WEEK)) {
				$mult = $period / T_WEEK;
				$period = T_WEEK;
			} else if (!($period % T_DAY)) {
				$mult = $period / T_DAY;
				$period = T_DAY;
			} else if ($period < 0) {
				if (!($period % T_YEAR)) {
					$mult = $period / T_YEAR;
					$period = T_YEAR;
				} else if (!($period % T_MONTH)) {
					$mult = $period / T_MONTH;
					$period = T_MONTH;
				} else {
					$mult = null;
					$period = null;
					return false;
				}
			} else {
				$mult = null;
				$period = null;
				return false;
			}
			$periodTypes = array (T_DAY => 'day', T_WEEK => 'week', T_MONTH => 'month', T_YEAR => 'year');
			// $periodStr = 'every ' . ($mult != 1 ? $mult : null) . ' ' . $periodTypes[$period] . ($mult != 1 ? 's' : null);
		}
		$humanDate = '';
		if ($period) {
			switch ($period) {
				case T_WEEK:
					$dayStr = ($ts ? strftime('%A', $ts) : 'week');
					$humanDate .= 'every ';
					switch ($mult) {
						case 1:
							$humanDate .= $dayStr;
							break;
						case 2:
							$humanDate .= 'other ' . $dayStr;
							break;
						default:
							$humanDate .= numToStr($mult) . ' ' . $dayStr . 's';
					}
					break;
				case T_DAY:
					$humanDate = 'every ' . (($mult > 1) ? numToStr($mult) . ' days' : 'day');
					break;
				case T_YEAR:
					$humanDate = ($ts ? strftime($config['dateFmtMonth'], $ts) : null) . ' every ' . (($mult > 1) ? numToStr($mult) . ' years' : 'year');
					break;
				case T_MONTH:
					$dayOfMonth = ($ts ? (int) strftime('%e', $ts) : false);
					$humanDate = ($dayOfMonth ? 'the ' . $dayOfMonth . ordinal($dayOfMonth) . ($mult > 1 ? ' day ' : ' of ') : null) . 'every ' . (($mult > 1) ? numToStr($mult) . ' months' : ' $month');
			}
			if ($ts && $includeStart) $humanDate .= ', starting ';
		}
		if ((($period && $includeStart) || !$period) && $ts) {
			$today = roundDate(time());
			$diff = ceil(($ts - $today) / T_DAY);
			switch ($diff) {
				case -7:
					$humanDate .= 'one week ago';
					break;
				case -6:
				case -5:
				case -4:
				case -3:
					$humanDate .= $diff . ' days ago';
					break;
				case -2:
					$humanDate .= 'the day before yesterday';
					break;
				case -1:
					$humanDate .= 'yesterday';
					break;
				case 0:
					$humanDate .= 'today';
					break;
				case 1:
					$humanDate .= 'tomorrow';
					break;
				case 2:
					$humanDate .= 'the day after tomorrow';
					break;
				case 3:
				case 4:
				case 5:
				case 6:
					$humanDate .= 'in ' . $diff . ' days';
					break;
				case 7:
					$humanDate .= 'in one week';
					break;
				default:
					$humanDate .= strftime(TF_HUMAN, $ts);
			}
		}
		return $humanDate;
	}
}

class DateObj extends MarketPrototype {
	private $_ts;
	private $_period;

	public function __construct ($ts = null, $period = null) {
		if (is_null($ts)) $ts = time();
		if (!$ts = self::check($ts)) return false;
		else $this->_ts = $ts;
		$this->_period = ((int) $period ? (int) $period : null);
		return true;
	}

	public function validate () {
		return $this->_ts = Date::check($this->_ts);
	}

	public function round () {
		return $this->_ts = Date::round($this->_ts);
	}

	public static function dMonths ($d, $ts = null) {
		if (is_null($ts) && isset($this)) $ts = &$this->_ts;
		else $ts = self::check($ts);
		if (!is_int($ts)) return $ts;
		$d = (int) $d;
		if (!$d) return $ts;
	}
}

class TextCaptcha {
	public static function get () {
		ini_set('zend.ze1_compatibility_mode', 0);
		global $config;
		$url = "http://textcaptcha.com/api/$config[textCAPTCHAkey]";
		try {
		    $xml = @new SimpleXMLElement($url,null,true);
		} catch (Exception $e) {
			// if there is a problem, use static fallback..
			$fallback = '<captcha>'.
				'<question>Is ice hot or cold?</question>'.
				'<answer>'.md5('cold').'</answer></captcha>';
			$xml = new SimpleXMLElement($fallback);
		}
		/* display question as part of form */
		$question = (string) $xml->question;
		/* store answers in session */
		$ans = array();
		foreach ($xml->answer as $hash) {
		    $ans[] = (string) $hash;
		}
		$_SESSION['captcha'] = $ans;
		return $question;
	}

	public static function verify ($ans) {
		$ans = md5(trim(strtolower($ans)));
		return in_array($ans, $_SESSION['captcha']);
	}
}
?>
