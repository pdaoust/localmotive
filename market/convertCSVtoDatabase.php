<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/route.inc.php');
require_once ($path . '/market/classes/order.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/orderItem.inc.php');
require_once ($path . '/market/classes/price.inc.php');
require_once ($path . '/market/classes/journalEntry.inc.php');
require_once ($path . '/market/classes/deliveryDay.inc.php');


$accountslist = file('accounts_list.csv');
$towns = array (
	'okfalls' => array (1, 0, 'Okanagan Falls'),
	'eastsideroad' => array (2, 0, 'Penticton'),
	'penticton' => array (3, 0, 'Penticton'),
	'naramata' => array (4, 0, 'Naramata'),
	'summerland' => array (6, 0, 'Summerland'),
	'kaleden' => array (7, 0, 'Kaleden'),
	'oliver' => array (8, 0, 'Oliver'),
	'osoyoos' => array (9, 0, 'Osoyoos'),
	'keremeos' => array (10, 0, 'Keremeos'),
	'unsorted' => array (null, 0, 'Unknown')
);
$db->start('addPerson');
foreach ($accountslist as $thisAccount) {
	echo '<div style="background-color: #fec; border: 1px dotted #dba; margin: 1em;">';
	$ts = microtime();
	echo '<span style="color: #09f;">time: 0</span><br/>';
	$thisAccount = explode(',', $thisAccount);
	echo "creating person<br/>\n";
	$thisPerson = new Person;
	echo "choosing route<br/>\n";
	if (!array_key_exists($thisAccount[0], $towns)) $thisAccount[0] = 'unsorted';
	$thisPerson->personType = array (P_CUSTOMER);
	$towns[$thisAccount[0]][1] ++;
	$thisPerson->setRoute($towns[$thisAccount[0]][0]);
	$thisPerson->contactName = $thisAccount[1];
	$thisPerson->email = $thisAccount[2];
	$thisPerson->setPassword($thisAccount[3]);
	$thisPerson->address1 = $thisAccount[4];
	$thisPerson->city = $towns[$thisAccount[0]][2];
	$thisPerson->directions = $thisAccount[5];
	$thisPerson->phone = $thisAccount[6];
	$thisPerson->compost = ($thisAccount[10] == 'yes') ? true : false;
	if ($thisAccount[12] == 'visa') $thisPerson->defaultPaymentType = PAY_PAYPAL;
	else $thisPerson->defaultPaymentType = PAY_CHEQUE;
	$stars = (substr($thisAccount[14], 0, 1) ? substr($thisAccount[14], 0, 1) - 1 : 0);
	$thisPerson->stars = $stars;
	$thisPerson->active = ($thisAccount[15] == 'no') ? true : false;
	echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
	echo "putting into category 27<br/>\n";
	$thisPerson->setParent(27);
	echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
	echo "about to save<br/>\n";
	if ($thisPerson->save()) {
		echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
		echo "setting route<br/>\n";
		echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
		echo 'personID = ' . $thisPerson->personID . "<br/>\n";
		echo 'contactName = ' . $thisPerson->contactName . "<br/>\n";
		echo 'recurringOrder = ' . ($thisAccount[8] == 'none' ? 'false' : '$' . $thisAccount[8] . ', ' . $thisAccount[9]) . "<br/>\n";
		echo "creating opening balance<br/>\n";
		if ((float) $thisAccount[13]) $thisPerson->createJournalEntry($thisAccount[13], 'Starting balance (carried over from old accounting system)');
		echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
		if ($thisAccount[8] != 'none') {
			echo "starting recurring order<br/>\n";
			if ($recurringOrder = $thisPerson->startOrder(O_RECURRING, ($thisAccount[9] == 'biweekly') ? 2 : 1)) {
				echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
				switch ((int) $thisAccount[8]) {
					case 30:
						$itemID = 10;
						break;
					case 40:
						$itemID = 11;
						break;
					case 50:
						$itemID = 12;
					case 25:
					default:
						$itemID = 9;
						break;
				}
				echo "adding item $itemID to recurring order<br/>\n";
				$recurringOrder->addQuantity($itemID, 1);
				echo '<pre>';
				print_r($recurringOrder->orderItems);
				echo '</pre>';
				echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
				echo "saving recurring order<br/>\n";
				$recurringOrder->save();
				echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
			}
		}
	} else {
		$db->rollback('addPerson');
		echo 'Couldn\'t save ' . $thisPerson->contactName . "\n";
		echo '<pre style="border: 1px dotted #fff;">';
		print_r($thisPerson);
		echo '</pre>';
		die ();
	}
	echo '<span style="color: #09f;">time: ' . (round((microtime() - $ts), 4)) . '</span><br/>';
	echo '</div>';
}
		$db->commit('addPerson');
?>