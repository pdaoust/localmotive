<script type="text/javascript" language="JavaScript">
function disableSubmit() {
	document.getElementById('submit1').disabled = true;
	document.getElementById('submit1').value = 'Submitting...';
	document.getElementById('submit2').disabled = true;
	document.getElementById('submit2').value = 'Submitting...';
}
</script>
<h2>Manage accounts</h2>
<p><?php

switch ($viewBy) {
	case 'customers': ?>all customers | <a href="manageAccounts.php?viewBy=suppliers">all suppliers</a><?php
		break;
	case 'suppliers': ?><a href="manageAccounts.php?viewBy=customers">all customers</a> | all suppliers<?php
		break;
	case 'deliveryDay': ?>Date of <?= strftime(TF_HUMAN, $deliveryDay) ?> | <a href="manageAccounts.php?viewBy=customers">all customers</a> | <a href="manageAccounts.php?viewBy=suppliers">all suppliers</a><?php
} ?></p>
<form action="manageAccounts.php" method="POST" onsubmit="disableSubmit()">
<div style="float: right;">
	<input type="submit" id="submit1" name="submit1" value="Adjust accounts"/>
	<input type="hidden" name="viewBy" value="<?= $viewBy ?>"/>
	<input type="hidden" name="action" value="1"/>
	<? if (isset($deliveryDay)) { ?>
		<input type="hidden" name="deliveryDay" value="<?= $deliveryDay ?>"/>
	<? } ?>
</div>
<table class="listing acctg<?= $viewBy == 'deliveryDay' ? ' horizRules' : null ?>" style="clear: both;">
<?php

insertHeaderRow(true);
switch ($viewBy) {
	case 'customers':
	case 'suppliers':
	default:
		displayPeople($people);
		break;
	case 'deliveryDay': ?>
		<p class="notice">All orders are checked as delivered by default, to save time. If a particular order hasn't been delivered, un-check the 'delivered' checkbox in its row.</p><?
		displayPeople($entries[0], $orders);
}

function displayPeople ($people, $orders = null) {
	$payTypes = getPayTypes();
	unset($payTypes[PAY_ACCT]);
	$i = 0;
	// $i2 is a counter because next seems to always return true
	$i2 = 0;
	global $logger;
	if (is_array($orders)) $people2 = $GLOBALS['people'];
	$canAcct = $GLOBALS['canAcct'];
	foreach ($people as $thisPerson) {
		$i2 ++;
		if (gettype($thisPerson) == 'array') {
			$ent = $thisPerson;
			if (isset($ent['personID'])) {
				if (isset($people2[$ent['personID']])) $thisPerson = $people2[$ent['personID']];
			}
			$personType = $ent['type'];
			if ($personType & P_DEPOT) $personType = P_DEPOT;
			else if ($personType & P_CUSTOMER) $personType = P_CUSTOMER; ?>
			<tr class="<?= ($i % 2 ? 'even' : 'odd') . ($personType == 0 ? ' categoryHeader' : null) ?>"><?
		} else {
			if (!($i % 20)) insertHeaderRow(); ?>
			<tr class="<?= ($i % 2 ? 'even' : 'odd') ?>"><?
			$personType = P_CUSTOMER;
		}
		switch ($personType) {
			case -1:
				echo "\t\t<td colspan=\"6\">No entries today</td>\n";
				break;
			case 0:
				echo "\t\t<th colspan=\"6\">" . htmlEscape($ent['label']) . "</th>\n";
				if ($nextRow = next($people)) {
					if ($i2 == count($people)) echo "\t\t</tr><tr class=\"odd\"><td colspan=\"6\">No entries today</td>\n";
					else if (!($nextRow['type'] & P_DEPOT) && $nextRow['type'] > 0) insertHeaderRow();
					else if ($nextRow['type'] == -1) echo "\t\t<td colspan=\"6\">No entries today</td>\n";
					prev($people);
				} else echo "\t\t<tr class=\"odd\"><td colspan=\"6\">No entries today</td></tr>\n";
				break;
			case P_DEPOT:
				echo '<th colspan="6">' . htmlEscape($ent['label']) . "</th>";
				insertHeaderRow();
				break;
			case P_CUSTOMER:
				$payTypeIDs = $thisPerson->getPayTypeIDs();
				//if (isset($payTypes[PAY_ACCT])) unset($payTypes[PAY_ACCT]);
				$payTypeID = $thisPerson->getPayTypeID();
				// TODO: make the next few lines HTML and add Javascript handlers ?>
			<td>
				<a href="javascript:spawnActivity(<?= $thisPerson->personID ?>)" title="Account activity"><img src="img/act.png" class="icon" alt="Account activity"/></a>
				<a href="javascript:spawnOrderHistory(<?= $thisPerson->personID ?>)" title="Order history"><img src="img/ordh.png" class="icon" alt="Order history"/></a>
				<a href="javascript:loadOrders(<?= $thisPerson->personID ?>)" title="View orders"><img src="img/ord.png" class="icon" alt="View orders"/></a>
				<a href="javascript:editPerson(<?= $thisPerson->personID ?>)" title="Edit person" id="n<?= $thisPerson->personID ?>_l"><?= htmlEscape($thisPerson->getLabel()) ?>
			</td>
			<!--<td class="bins"><? echo $thisPerson->bins; ?> <label>&rarr;<input type="text" name="personAdj[<? echo $thisPerson->personID; ?>][binsOut]" maxlength="2"/></label> <label>&larr;<input type="text" name="personAdj[<? echo $thisPerson->personID; ?>][binsIn]" maxlength="2"/></label></td>
			<td class="bottles"><input type="text" name="personAdj[<? echo $thisPerson->personID; ?>][bottlesIn]" maxlength="2"/></td>-->
			<td class="number<?= ($thisPerson->getBalance() < 0 ? ' debit' : ($thisPerson->getBalance() >= 0 ? ' credit' : null)) . '">' . money_format(NF_MONEY, $thisPerson->getBalance()) ?></td>
			<td>
				$<input type="text" name="personAdj[<?= $thisPerson->personID ?>][payment]" size="3" maxlength="8"<? if (!$canAcct) echo ' disabled="disabled"'; ?>/>
				<select name="personAdj[<?= $thisPerson->personID ?>][payTypeID]">
				<? foreach ($payTypes as $k => $v) {
					if (in_array($k, $payTypeIDs)) { ?>
						<option value="<?= $v->payTypeID ?>"<?= ($v->payTypeID == $payTypeID) ? ' selected' : null ?>/><?= htmlEscape($v->labelShort) ?></option>
					<? }
				} ?>
			</td>
			<td>$<input type="text" name="personAdj[<?= $thisPerson->personID ?>][credit]" size="3" maxlength="8"<? if (!$canAcct) echo ' disabled="disabled"'; ?>/> <label>why? <input type="text" name="personAdj[<?= $thisPerson->personID ?>][why]" size="5" <? if (!$canAcct) echo ' disabled="disabled"'; ?>/></label></td>
		<? } ?>
		</tr>
	<?	if (is_array($orders) && isset($ent['personID'])) {
			// TODO: remove JavaScriptiness in each of the buttons and use a jQuery binding instead
			if (isset($orders[$ent['personID']])) {
				foreach ($orders[$thisPerson->personID] as $thisOrder) { ?>
			<tr class="details <?= ($i % 2 ? 'even' : 'odd') . ($thisOrder->getDateDelivered() ? ' inactive' : null) ?>" style="font-style: italic;"> 
				<td style="padding-left: 2em;"><a href="javascript:spawnOrder(<?= $thisOrder->orderID ?>)"><img src="img/<? echo ($thisOrder->isFromRecurringOrder() ? 'std' : 'ord') . '.png" class="icon" alt="Order"/></a> #' . $thisOrder->orderID . ($thisOrder->label ? ' (' . htmlEscape($thisOrder->label) . ')' : null) . ', ' . strftime(TF_HUMAN, $thisOrder->getDateCompleted()); ?></td>
				<td colspan="2"><label><input type="checkbox" name="orderAdj[<?= $thisOrder->orderID ?>][delivered]" checked="checked"<?= $thisOrder->getDateDelivered() ? ' disabled="disabled"' : null ?>/> delivered</label></td>
				<? $balance = $thisOrder->getBalance(); ?>
				<td class="number<?= $balance < 0 ? ' debit' : ' credit' ?>"><?= money_format(NF_MONEY, $balance) ?></td>
				<td>$<input type="text" name="orderAdj[<?= $thisOrder->orderID ?>][payment]" size="3" maxlength="8"<?= ($balance < 0 ? 'value="' . (0 - $balance) : null) . '"' . (!$canAcct ? ' disabled="disabled"' : null) ?>/></td>
				<td>$<input type="text" name="orderAdj[<?= $thisOrder->orderID ?>][credit]" size="3" maxlength="8"<? if (!$canAcct) echo ' disabled="disabled"'; ?>/> <label>why? <input type="text" name="orderAdj[<?= $thisPerson->personID ?>][why]" size="5" <? if (!$canAcct) echo ' disabled="disabled"'; ?>/></label></td>
			</tr>
		<?		}
			}
		}
		$i ++;
	}
}

function insertHeaderRow ($hide = false) { ?>
		<tr class="even<?= $hide ? ' spacer' : null ?>">
			<th>Name</th>
			<!--<th><img src="img/bin.png" class="icon" alt="bins"/></th>
			<th><img src="img/bot.png" class="icon" alt="bottles"/></th>-->
			<!--<th class="bins">Bins</th>
			<th class="bottles">Bottles</th>-->
			<th class="figure">Bal</th>
			<th class="payment">Pmt</th>
			<th class="creditDetails">Credit</th>
		</tr>
<? } ?>
</table>
<div style="float: right; clear: both;"><input type="submit" id="submit2" name="submit2" value="Adjust accounts"/></div>
</form>
<? $node = &$user;
$payTypes = getPayTypes();
include ('editPersonBox.tpl.php'); ?>
<div id="orderList" title="View orders"></div>
<script type="text/javascript" language="JavaScript">

$(function () {
	$('#orderList').dialog({'autoOpen': false});
	$('#editPersonBox').dialog({'autoOpen': false, 'width': 700, 'height': 'auto'});
});
</script>
