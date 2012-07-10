<h2>Account activity for <?= htmlEscape($person->getLabel()) ?></h2>
<? if (is_object($currBal)) {
	$currBalV = $currBal->calculateBalance(); ?><div class="<?= $currBalV < 0 ? 'debit' : 'credit' ?>"><p>Current balance: <?= money_format(NF_MONEY, $currBalV) ?> &middot; <a href="<?= $secureUrlPrefix  . $config['docRoot'] ?>/market/payment.php"><?= $currBalV < 0 ? 'Make a payment' : 'Add a prepayment' ?></a></p><? if (!count($journalEntries)) echo '<p>There are no journal entries for the above time period.</p>'; ?></div>
<? } ?>

<form action="activity.php" id="changeDates">
	<input type="hidden" name="style" value="<?= $style ?>"/>
	<input type="hidden" name="personID" value="<?= $person->personID ?>"/>
	<p>View account activity <label for="dateStart">from</label> <input type="text" name="dateStart" id="dateStart" value="<?= strftime(TF_PICKER_VAL, $dateStart) ?>" class="datepicker"/> <label for="dateEnd">to</label> <input type="text" name="dateEnd" id="dateEnd" value="<?= strftime(TF_PICKER_VAL, $dateEnd) ?>" class="datepicker"/> <input type="submit" value="Go"/></p>
</form>

<? if (count($journalEntries)) { ?>
<table class="listing">
	<tr class="even">
		<th class="date">Date</th>
		<th>Description</th>
		<th class="figTiny">Type</th>
		<th class="figure">Amount</th>
		<th class="figure">Balance</th>
	</tr>
<?php
$i = 0;
$payTypes = getPayTypes();
foreach ($journalEntries as $thisJournalEntry) {
	$i ++;
	echo "\t<tr class=\"" . ($i % 2 ? 'odd' : 'even') . "\">\n";
	echo "\t\t<td>" . strftime('%x', $thisJournalEntry->dateCreated) . "</td>\n";
	echo "\t\t<td>" . ($thisJournalEntry->orderID ? '<a href="javascript:spawnOrder(' . $thisJournalEntry->orderID . ')"><img src="img/ord.png" class="icon" alt="View the order associated with this journal entry" title="View the order associated with this journal entry"/></a> ': null) . htmlEscape($thisJournalEntry->notes) . "</td>\n";
	if (isset($payTypes[$thisJournalEntry->payTypeID])) $thisPayType = $payTypes[$thisJournalEntry->payTypeID];
	else $thisPayType = null;
	echo "\t\t<td>" . ($thisPayType ? '<span title="' . htmlEscape($thisPayType->label) . (($user->personID == 1 && $thisJournalEntry->txnID) ? ', txn ID ' . htmlEscape($thisJournalEntry->txnID) : null) . '">' . htmlEscape($thisPayType->labelShort) : null) . "</td>\n";
	echo "\t\t<td class=\"number " . ($thisJournalEntry->amount < 0 ? 'debit' : 'credit') . '">' . money_format(NF_MONEY_ACCT, $thisJournalEntry->amount) . "</td>\n";
	echo "\t\t<td class=\"number " . ($thisJournalEntry->balance < 0 ? 'debit' : 'credit') . '">' . money_format(NF_MONEY_ACCT, $thisJournalEntry->balance) . "</td>\n";
	echo "\t</tr>\n";
}
?>
</table>
<? } ?>
<script type="text/javascript" language="JavaScript">
$(function () {
	$('#dateStart').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true, defaultDate: new Date(<?= strftime(TF_JSDATE, $dateStart) ?>)});
	$('#dateEnd').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true, defaultDate: new Date(<?= strftime(TF_JSDATE, $dateEnd) ?>)});
});
</script>
