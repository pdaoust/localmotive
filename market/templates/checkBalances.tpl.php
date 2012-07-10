<h2>Check integrity of balances</h2>

<form action="checkBalances.php" method="GET">
	<input type="hidden" name="nodeID" value="<? echo $node->personID; ?>"/>
	<input type="hidden" name="action" value="reconcile"/>
	<input type="submit" value="Reconcile"/>
	<table class="listing"><?php
	$i = 0;
	foreach ($tree as $thisPerson) {
		if (isset($bals[$thisPerson->personID])) $thisBal = $bals[$thisPerson->personID];
		else $thisBal = 0;
		if (!($i % 20)) { ?><tr class="even">
			<th>Name</th>
			<th class="figure">Acct</th>
			<th class="figure">Jrnl</th>
			<th class="figure">Diff</th>
		</tr><? } ?>
		<tr class="<? echo ($i % 2 ? 'even' : 'odd'); ?>">
			<td class="odd<?php echo ($thisPerson->personType & P_CATEGORY ? ' category' : null) . ($thisPerson->personType & P_DEPOT ? ' depot' : null) . '">';
			echo str_repeat('&nbsp;&nbsp;&nbsp;', $thisPerson->getDepth($node)) . $thisPerson->getLabel();
			?></td>
			<td class="number"><? echo money_format(NF_MONEY, $thisPerson->getBalance()); ?></td>
			<td class="number"><? echo money_format(NF_MONEY, $thisBal); ?></td>
			<td class="number"><? if ($thisPerson->getBalance() == $thisBal) echo '<img src="img/y.png" class="icon" alt="okay!"/>'; else echo '<span class="notice">' . money_format(NF_MONEY, $thisPerson->getBalance() - $thisBal) . '</span>'; ?></td>
		<? $i ++;
	}
	?>
	</table>
	<input type="submit" value="Reconcile"/>
</form>
