<? /* if (!isset($_REQUEST['confirm'])) { ?>
<h2>Create recurring orders for the next set of routes?</h2>
<p><a href="createRecurringOrders.php?confirm">Yes</a> | <a href="index.php">No</a></p>
<? } else { */
$i = 0; ?>

<h2>Creating recurring orders...</h2>
<table class="listing">
	<tr class="even">
		<th class="objectID">Order</th>
		<th>Name</th>
		<th class="figure">Amt</th>
		<th class="figTiny">Paid</th>
	</tr>
	<? foreach ($orders as $v) {
		$emf = array ();
		$emp = array ();
		if (isset($errors[$v->orderID])) {
			foreach ($errors[$v->orderID] as $ve) {
				switch ($ve) {
					case 'nocredit':
						$emf[] = 'Insufficient account credit';
						break;
					case 'database':
						$emf[] = 'Database error';
						break;
					case 'noroute':
						$emf[] = 'Person is not in a route';
						break;
					case 'empty':
						$emf[] = 'Order is empty';
						break;
					case 'toosmall':
						$emf[] = 'Order is too small';
						break;
					case 'nocsa':
						$emf[] = 'No CSA item on this order';
						break;
					case 'userauth':
					case 'data':
						$emp[] = 'PayFlow is not configured properly';
						break;
					case 'declined':
						$emp[] = 'Credit card declined';
						break;
					case 'referral':
						$emp[] = 'Credit card declined; phone referral needed';
						break;
					case 'origID':
						$emp[] = 'Credit card is not on file, or it\'s been too long since last transaction';
						break;
					case 'duplicate':
						$emp[] = 'Duplicate transaction warning';
						break;
					case 'nsf':
						$emp[] = 'Insufficient funds on credit card';
						break;
					case 'txlimit':
						$emp[] = 'Amount is over customer\'s per-transaction limit';
						break;
					case 'unavailable':
						$emp[] = 'PayFlow is unavailable';
						break;
					case 'inactive':
						$emf[] = 'Person\'s account isn\'t active';
						break;
				}
			}
		} ?>
	<tr class="<?= ($i % 2 ? 'even' : 'odd') . (count($emf) ? ' notice' : ' okay') ?>" title="<?= htmlEscape(implode(" · ", $emf)) ?>">
		<td><?= $v->orderID ?></td>
		<td><?= ($v->label ? ' (' . htmlEscape($v->label) . ')' : null) ?> for <? echo htmlEscape($people[$v->personID]->getLabel()); ?></td>
		<td class="number"><?
			$totals = $v->getTotal();
			echo money_format(NF_MONEY, $totals['net']);
			if (count($emp)) $logger->addEntry('payment errors '.print_r($emp, true));
		?></td>
		<td<?= (
			count($emp) ?
				' class="notice"' :
				null
			) . (
				($balances[$v->personID]['payTypeID'] == PAY_CC) ? (
					' title="' .
					(count($emp) ?
						htmlEscape(implode(" · ", $emp)) :
						'Transaction ID ' . $balances[$v->personID]['txnID']
					) . '"'
				) :
				null
			) ?>><?= ($balances[$v->personID]['payTypeID'] == PAY_CC) ? 'CC' : null ?></td>
	</tr>
	<? $i ++;
} ?>
</table>
<? /* } */ ?>
