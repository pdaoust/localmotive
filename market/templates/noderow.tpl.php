<?
function outputPersonRow ($person, $treeToken) {
	global $node; ?>
	<td>
		<? outputNewIcon ($person->personID, 'nper');
		$personType = 'person';
		$personType = ($person->personType & P_CATEGORY ? 'category' : $personType);
		$personType = ($person->personType & P_SUPPLIER ? 'person' : $personType);
		$personType = ($person->personType & P_CUSTOMER ? 'person' : $personType);
		$personType = ($person->personType & P_DEPOT ? 'depot' : $personType);
		outputDeleteIcon ($person->personID, $personType);
		outputMoveIcon ($person->personID); ?>
		<a href="javascript:spawnActivity(<?= $person->personID ?>)">
			<img src="img/act.png" class="icon" alt="Account activity"/>
		</a>
		<a href="javascript:spawnOrderHistory(<?= $person->personID ?>)">
			<img src="img/ordh.png" class="icon" alt="Order history" />
		</a>
		<? if (!$person->isLeafNode() || $person->email) { ?>
			<a href="javascript:email(<?= $person->personID ?>)" title="E-mail <?= htmlEscape($person->email) . (($person->email && !$person->isLeafNode()) ? ' and' : null) . (!$person->isLeafNode() ? ' all members' : null) ?>">
				<img src="img/mail.png" class="icon" alt="Send e-mail to person or group"/>
			</a>
		<? } else { ?>
			<img src="img/_.png" class="icon" alt=""/>
		<? } ?>
		<span id="n<?= $person->personID ?>_o"<?= (($person->isActive() && $person->personType & P_CUSTOMER) ? null : ' style="display: none;"') ?>>
			<a href="javascript:loadOrders(<?= $person->personID ?>)">
				<img src="img/ord.png" class="icon" alt="view order(s)"/>
			</a>
		</span>
	</td>
	<? $class = ($person->personType & P_CATEGORY ? 'category' : null) . ($person->personType & P_DEPOT ? ' depot' : null);
	outputLabel ($person->personID, $person->getLabel(), $class, $treeToken, $person->getDepth($node), false, $person->isLeafNode()); ?>
	</td>
<? }

function outputItemRow ($item, $treeToken) {
	global $category; ?>
	<td>
		<? outputNewIcon ($item->itemID, 'nitm');
		outputDeleteIcon ($item->itemID, ($item->isLeafNode() ? 'item' : 'category'));
		outputMoveIcon ($item->itemID); ?>
		</td>
		<?
		$showMoveControls = (bool) (($category->itemID != $item->itemID) && $item->hasSiblings());
		outputLabel ($item->itemID, $item->label, (($item->itemType & I_CATEGORY) ? 'category' : null), $treeToken, $item->getDepth($category), $showMoveControls, $item->isLeafNode());
		// echo "\t\t<td>" . $item->supplierID . "</td>"; ?>
		<td class="number" id="n<?= $item->itemID ?>_q"<?= (($item->quantity <= $item->reorderQuantity) ? ' class="notice"' : null) ?>>
			<?= ($item->getTrackInventory() && !is_null($item->quantity) && $item->isLeafNode() ? $item->quantity : null) ?>
		</td>
		<td class="number" id="n<?= $item->itemID ?>_pr">
		<? $prices = $item->getPrices();
		$priceVals = array ();
		foreach ($prices as $thisPrice) {
			$priceVal = money_format(NF_MONEY, $thisPrice->price) . ($thisPrice->tax ? '+' . (($thisPrice->tax & TAX_HST) ? 'H' : null) . (($thisPrice->tax & TAX_PST) ? 'P' : null) : null) . ($thisPrice->multiple == 1 ? ' ea' : ' per ' . $thisPrice->multiple);
			if (!in_array($priceVal, $priceVals)) $priceVals[] = $priceVal;
		}
		echo implode(', ', $priceVals); ?>
	</td>
<? }

function outputNewIcon ($nodeID, $icon) { // TODO: change to HTML and add JavaScript ?>
	<a href="javascript:newNode(<?= $nodeID ?>)">
		<img src="img/<?= $icon ?>.png" class="icon" alt="New"/>
	</a>
<? }

function outputDeleteIcon ($nodeID, $label) { // TODO: possible injection? Change to HTML and add JavaScript ?>
	<a href="javascript:deleteNode(<?= $nodeID ?>,'<?= htmlEscape($label) ?>')">
		<img src="img/del.png" class="icon" alt="Delete"/>
	</a>
<? }

function outputMoveIcon ($nodeID) { // TODO: change to HTML and add JavaScript ?>
	<a href="javascript:setParent(<?= (int) $nodeID ?>)">
		<img src="img/mv.png" class="icon" alt="Move"/></a>
	</a>
<? }

function outputLabel ($nodeID, $label, $class, $treeToken, $depth, $showMoveControls, $isLeaf) {
	global $logger;
	$logger->log('depth', $depth); ?>
	<td>
		<span id="depth<?= $nodeID ?>">
			<?= str_repeat('&nbsp;&nbsp;&nbsp;', $depth) ?>
		</span>
		<span id="exp<?= $treeToken ?>" data-exp="1"><!-- data-exp stores a record of whether this branch is expanded -->
			<?= (!$isLeaf ? '<a href="javascript:expandTree(\'' . $treeToken . '\')"><img src="img/e_d.png" id="' . $treeToken . 'expander" class="icon" alt="-"/></a>' : '<img src="img/_.png" class="icon" alt=" "/>') ?>
		</span>
		<? if ($showMoveControls) { ?>
			<span class="nav moveControls">
				<a href="javascript:moveNode(<?= $nodeID ?>, 'up')"><img src="img/n_u.png" class="n_u" alt="up" title="move up"/></a>
				<a href="javascript:moveNode(<?= $nodeID ?>, 'down')"><img src="img/n_d.png" class="n_d" alt="down" title="move down"/></a>
			</span>
		<? } ?>
		<a href="javascript:editNode(<?= $nodeID ?>)" class="<?= $class ?>" id="n<?= $nodeID ?>_l">
			<?= htmlEscape($label) ?>
		</a>
	</td>
<? } ?>
