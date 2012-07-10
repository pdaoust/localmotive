<h2>Order history for <?= htmlEscape($person->getLabel()) ?></h2>
<form action="orderHistory.php">
	<input type="hidden" name="style" value="<?= $style; ?>"/>
	<? if (isset($_REQUEST['personID'])) { ?>
		<input type="hidden" name="personID" value="<?= $person->personID ?>"/>
	<? }
	if ($recursive) { ?>
		<input type="hidden" name="recursive" value="1"/>
	<? } ?>
	<input type="hidden" name="orderBy" value="<?= $orderBy ?>"/>
	<p>View orders
		<select name="orderBy">
			<option value="dateCompleted"<?= $orderBy == 'dateCompleted' ? ' selected="selected"' : null ?>>completed</option>
			<option value="dateToDeliver"<?= $orderBy == 'dateToDeliver' ? ' selected="selected"' : null ?>>to be delivered</option>
		</select>
		<label for="dateStart">between</label> <input type="text" name="dateStart" id="dateStart" value="<?= strftime(TF_PICKER_VAL, $dateStart) ?>" class="datepicker"/> <label for="dateEnd">and</label> <input type="text" name="dateEnd" id="dateEnd" value="<?= strftime(TF_PICKER_VAL, $dateEnd) ?>" class="datepicker"/> <input type="submit" value="Go"/>
	</p>
</form>
<table class="listing">
	<tr class="even">
		<?php $uri = 'orderHistory.php?' . (isset($_REQUEST['personID']) ? 'personID=' . $person->personID : null) . sprintf('&dateStart=%s&dateEnd=%s', $dateStart, $dateEnd) . ($recursive ? '&recursive=1' : null); ?>
		<th class="objectID">#</th>
		<th>Description</th>
		<th class="date">Completed</th>
		<th class="date">Deliver on</th>
		<th class="figure">Total</th>
		<? if ($recursive) { ?><th>Customer</th><? } ?>
	</tr>
<?php
$i = 0;
foreach ($orders as $thisOrder) {
	// TODO: add actions to orderHistory
	$i ++;
	$dateStarted = $thisOrder->getDateStarted();
	$dateToDeliver = $thisOrder->getDateToDeliver();
	$dateCompleted = $thisOrder->getDateCompleted();
	$dateDelivered = $thisOrder->getDateDelivered();
	$totals = $thisOrder->getTotal();
	echo "\t<tr class=\"" . ($i % 2 ? 'odd' : 'even') . ($thisOrder->getDateCanceled() ? ' canceled' : null) . "\">\n";
	echo "\t\t<td><a href=\"javascript:spawnOrder(" . $thisOrder->orderID . ')">' . $thisOrder->orderID . "</a></td>\n";
	echo '<td>' . ($thisOrder->label ? '<a href="javascript:spawnOrder(' . $thisOrder->orderID . ')">' . htmlEscape($thisOrder->label) : null) . '</td>';
	echo "\t\t<td>" . ($dateCompleted ? strftime('%x', $dateCompleted) : '&nbsp;') . "</td>\n";
	echo "\t\t<td" . ($dateDelivered ? ' class="okay" title="delivered on ' . strftime('%x', $dateDelivered) . '"' : null) . ">" . ($dateToDeliver ? strftime('%x', $dateToDeliver) : '&nbsp;') . "</td>\n";
	echo "\t\t<td class=\"number\">" . money_format(NF_MONEY, $totals['gross']) . "</td>\n";
	if ($recursive) echo "\t\t<td>" . htmlEscape($people[$thisOrder->personID]) . "</td>\n";
	echo "\t</tr>\n";
}
?>
</table>
<script type="text/javascript" language="JavaScript">
$(function () {
	$('#dateStart').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true, defaultDate: new Date(<?= strftime(TF_JSDATE, $dateStart) ?>)});
	$('#dateEnd').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true, defaultDate: new Date(<?= strftime(TF_JSDATE, $dateEnd) ?>)});
});
</script>
