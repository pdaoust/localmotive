<?php

require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/item.inc.php');
require_once ($path . '/market/classes/price.inc.php');

$stations = array (
	'dairy' => 3,
	'meats' => 4,
	'bulk' => 5,
	'produce' => 6,
	'baked' => 7,
	'extras' => 8
);

$itemStack = array ();
foreach ($stations as $thisStationName => $thisItemID) {
	echo "loading station<br/>\n";
	$station = new Item ($thisItemID);
	$itemStack = array ($station);
	$itemslist = file('market_station_' . $thisStationName . '_list.csv');
	$db->start('add' . $thisStationName);
	foreach ($itemslist as $thisLine) {
		echo '<div style="background-color: #fec; border: 1px dotted #dba; margin: 1em;">';
		$ts = microtime();
		echo '<span style="color: #09f;">time: 0</span><br/>';
		$thisLine = explode(',', $thisLine);
		if (substr($thisLine[0], 4, 4) == '0000' && count($itemStack) > 2) {
			echo "it's another second-order subcategory<br/>\n";
			array_pop($itemStack);
		}
		if ($thisLine[0] != 'list_end') {
			echo "creating item<br/>\n";
			$thisItem = new Item;
			$thisItem->active = true;
			$label = explode('#', $thisLine[4]);
			$thisItem->label = $label[0];
			$thisItem->quantity = (int) $thisLine[5];
			echo "setting parent " . $itemStack[count($itemStack) - 1]->label . "<br/>\n";
			$thisItem->setParent($itemStack[count($itemStack) - 1]->itemID);
			echo "saving item<br/>\n";
			if ($thisItem->save()) {
				echo "saved<br/>\n";
				if ((int) substr($thisLine[0], 4, 4)) {
					echo "setting price<br/>\n";
					$thisItem->setPrice(27, (float) $thisLine[1], ($thisLine[2] == 'yes' ? true : false), ($thisLine[3] == 'yes' ? true : false), 1);
				}
				echo "checking to see if this is a category<br/>\n";
				if ($thisLine[0] == 'list_beg' || substr($thisLine[0], 4, 4) == '0000') {
					echo "it's a first- or second-order subcategory<br/>\n";
					array_push($itemStack, $thisItem);
				}
			} else echo "didn't save<br/>\n";
		} else {
			echo "it's the end of a first-order category";
			if (count($itemStack) > 1) {
				echo "went up to category<br/>\n";
				$itemStack = array ($itemStack[0]);
			} else echo "didn't go up to category<br/>\n";
		}
		echo "</div>\n";
	}
	$db->commit('add' . $thisStationName);
}

?>