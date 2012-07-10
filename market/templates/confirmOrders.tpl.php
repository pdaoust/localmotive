<h2>Confirm orders</h2>
<table class="listing">
	<tr class="even">
		<th class="odd">Order #</th>
		<th class="even">Date</th>
		<th class="odd">Customer</th>
		<th class="even">Order amt</th>
		<th class="odd">Status</th>
	</tr>
<? $i = 0;
foreach ($orders as $thisOrder) {
	if ($thisOrder->getError() != E_ORDER_COMPLETED) { ?>
	<tr class="<? echo $i % 2 ? 'even' : 'odd'; ?>">
		<td class="odd"><? echo $thisOrder->orderID; ?></td>
		<td class="even"><? echo strftime(TF_HUMAN, $thisOrder->getDateStarted()); // TODO: if I create a dateChecked out, this'll have to change ?></td>
		<td class="odd"><? echo $people[$thisOrder->personID]->getLabel(); ?></td>
		<td class="even number"><?
			$totals = $thisOrder->getTotal();
			echo money_format(NF_MONEY, $totals['gross']);
		?></td>
		<td class="odd"><?
			if ($thisOrder->getError()) {
				echo '<span class="notice"><img src="img/n.png" class="icon" alt="error"/> ';
				switch ($thisOrder->getError()) {
					case E_DATABASE:
						echo 'Database error';
						break;
					case E_NO_OBJECT_ID:
					case E_WRONG_ORDER_TYPE:
						echo 'Code bug! call Paul!';
						break;
					case E_OBJECT_NOT_ACTIVE:
						echo 'Person\'s account isn\'t active';
						break;
					case E_NO_OBJECT:
						echo 'Person isn\'t in a route';
						break;
					case E_ORDER_COMPLETED:
						echo 'Order already confirmed';
						break;
					case E_ORDER_EMPTY:
					case E_ORDER_TOO_SMALL:
						echo 'Order too small';
						break;
					default:
						echo 'Problem with order creation: ' . $errorCodes[$thisOrder->getError()];
				}
				echo '</span>';
			} else {
				echo '<img src="img/y.png" class="icon" alt="completed"/>';
			}
		?></td>
	</tr>
<?			$i ++;
		}
	} ?>
</table>
