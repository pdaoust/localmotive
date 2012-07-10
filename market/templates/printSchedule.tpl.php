<?php
echo '<h2>Delivery/pickup schedule for ' . strftime('%a, %d %b %Y', $nextDeliveryDay) . '</h2>';
// TODO: mention routeDay name
echo "<div class=\"onePage\">\n";
echo '<h3>' . $sections[0] . ' (' . strftime('%a, %d %b %Y', $nextDeliveryDay) . ")</h3>\n";
echo "<table class=\"listing\">\n";
printListHeader(true);
printChart ($entries[0]);
echo "</table>\n";
echo "</div>\n";

foreach ($entries[0] as $ent) {
	switch ($ent['type']) {
		case P_DEPOT:
			echo "<div class=\"onePage\">\n";
			echo '<h3>' . $sections[$ent['personID']] . ' (' . strftime('%a, %d %b %Y', $nextDeliveryDay) . ")</h3>\n";
			echo "<table class=\"listing\">\n";
			printListHeader();
			printChart ($entries[$ent['personID']]);
			echo "</table>\n";
			echo "</div>\n";
			break;
		case P_CUSTOMER:
			foreach ($orders[$ent['personID']] as $thisOrder) {
				echo "<div class=\"onePage\">\n";
				$order = &$thisOrder;
				$customer = &$people[$ent['personID']];
				unset($journalEntries);
				include ($path . '/market/templates/invoice.tpl.php');
				echo "</div>\n";
			}
	}
}

function printChart ($entries) {
	$i = 0;
	foreach ($entries as $ent) {
		if ($ent['type'] == 0) $i = 0;
		echo "\t<tr class=\"" . ($i % 2 ? 'even' : 'odd') . "\">\n";
		switch ($ent['type']) {
			case -1:
				echo "\t\t<td colspan=\"7\">No entries today</td>\n";
				break;
			case 0:
				echo "\t\t<th class=\"categoryHeader\" colspan=\"7\">" . htmlEscape($ent['label']) . "</th>\n";
				printListHeader();
				break;
			case P_DEPOT:
				echo "\t\t<td class=\"category\" colspan=\"4\">" . htmlEscape($ent['label']) . "</td>";
				echo '<td class="category">' . $ent['bins'] . "</td>\n";
				echo '<td class="category">&nbsp;</td><td class="category">&nbsp;</td>';
				break;
			case P_CUSTOMER:
				echo "\t\t<td>" . htmlEscape($ent['label']) . '</td>';
				echo '<td class="number">' . money_format(NF_MONEY, $ent['balance']) . '</td>'; ?>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td class="deliveryBins">&nbsp;/&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<? }
		echo "</tr>\n";
		$i ++;
	}
}

function printListHeader ($invisible = false) { ?>
	<tr class="even<?= $invisible ? ' spacer' : null ?>">
		<th>Name</th>
		<th class="figure">Bal</th>
		<th class="figure">Pd</th>
		<th class="figure">Cr</th>
		<th class="deliveryBins">Bins</th>
		<th class="qty">Btls</th>
		<th>Notes</th>
	</tr>
<? } ?>
