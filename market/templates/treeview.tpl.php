<script type="text/javascript" language="JavaScript">
function expandTree (treeKey) {
	tableRows = document.getElementById('nodeTree').rows;
	$expander = $('#exp'+treeKey);
	nodeState = parseInt($expander.attr('data-exp')); // whether this node is expanded
	newNodeState = Number(!nodeState);
	$expander.attr('data-exp', newNodeState);
	for (i = 0; i < tableRows.length; i ++) {
		tableRow = tableRows[i];
		if (tableRow.id) {
			if (tableRow.id.length > treeKey.length && tableRow.id.substr(0, treeKey.length) == treeKey) {
				if (newNodeState) {
					tableRow.style.display = 'table-row';
				} else {
					tableRow.style.display = 'none';
				}
			}
		}
	}
	if (newNodeState) {
		document.getElementById(treeKey + 'expander').src = 'img/e_d.png';
	} else {
		document.getElementById(treeKey + 'expander').src = 'img/e_r.png';
	}
}

function toggleExpanded (columnName) {
	column = $('#col'+columnName);
	switch (true) {
		case column.hasClass('notExpanded'):
			getStyleClass(columnName).style.width = 'auto';
			getStyleClass(columnName).style.overflow = 'visible';
			column.addClass('expanded').removeClass('expanded');
			for (i = 0; i < <? echo floor(count($tree)); ?>; i ++) {
				document.getElementById(columnName + 'Expander' + i).elements[0].src = 'img/e_l.png';
			}
			break;
		case 'expanded':
			getStyleClass(columnName).style.width = getStyleClass(columnName + 'Contracted').style.width;
			getStyleClass(columnName).style.overflow = 'hidden';
			column.addClass('notExpanded').removeClass('expanded');
			for (i = 0; i < <? echo floor(count($tree)); ?>; i ++) {
				document.getElementById(columnName + 'Expander' + i).elements[0].src = 'img/e_r.png';
			}
	}
}

function toggleActive (treeActiveStates, nodeType) {
	console.log('toggling active...');
	// treeActiveStates = treeActiveStates.split('-');
	tableRows = document.getElementById('nodeTree').rows;
	$.each(treeActiveStates, function (k, v) {
		if (thisRow = $('#'+k)) {
			if (v) thisRow.removeClass('inactive');
			else thisRow.addClass('inactive');
			switch (nodeType) {
				case 'person':
					pref = 'p' + Number(k.substr(k.length - 5), 5);
					if (v) $('#'+pref+'_o').show();
					else $('#'+pref+'_o').hide();
					break;
			}
		}
	});
}

function moveNode (nodeID, direction) {
	$.ajax({
		data: {
			ajax: 1,
			action: 'moveNode',
			nodeID: nodeID,
			direction: direction
		},
		url: '<?= $thisPage ?>',
		type: 'post',
		dataType: 'json',
		error: function () {
			console.log('failed to move node');
		},
		success: function (r) {
			if (r.status) {
				nodeRow = $('#'+r.nodeID);
				section = nodeRow.parent('tbody');
				rowStart = nodeRow[0].sectionRowIndex;
				rowEnd = rowStart + r.size;
				if (r.d < 0) {
					for (i = rowStart; i < rowEnd; i ++) {
						moveRow(section[0].rows[i], r.d, true)
					}
				}
			}
		}
	});
}

/*function confirmMoveNode () {
	if (xmlHttp.readyState == 4) {
		result = xmlHttp.responseText;
		if (result == '0') return false;
		result = result.split("\n");
		d = Number(result[0]);
		nodeObj = document.getElementById(result[2]);
		rowStart = nodeObj.sectionRowIndex;
		rowEnd = nodeObj.sectionRowIndex + Number(result[1]);
		if (d < 0) {
			for (i = rowStart; i < rowEnd; i ++) {
				thisRow = document.getElementById('nodeTree').rows[i];
				moveRow(thisRow, d, true);
			}
		} else {
			for (i = rowEnd - 1; i >= rowStart; i --) {
				thisRow = document.getElementById('nodeTree').rows[i];
				moveRow(thisRow, d, true);
			}
		}
	}
}*/

function confirmDeleteNode (r) {
	if (!r.status) {
		alert('This record couldn\'t be deleted for some reason. Either it has journal entries or orders associated with it, or you need to tell Paul to fix his program.');
		return false;
	}
	nodeObj = $('#'+r.nodeID);
	section = nodeObj.parent('tbody');
	rowStart = nodeObj[0].sectionRowIndex;
	rowEnd = section[0].rows.length;
	for (i = rowStart; i < rowEnd; i ++) {
		// TODO: doesn't change active state of any node yet
		thisRow = section[0].rows[i];
		rowID = $(thisRow).attr('id');
		if (rowID.substr(0, r.nodeID.length) == r.nodeID && rowID.length > r.nodeID.length) {
			nodeID = Number(rowID.substr(-5, 5));
			$('#depth'+nodeID).text($('#depth'+nodeID).text.substr(18));
			$(thisRow).attr('id', rowID.substr(0, r.nodeID.length - 6) + rowID.substr(r.nodeID.length));
		}
		swapRowColour(thisRow);
	}
	deleteRow(nodeObj[0]);
	if (r.parentIsLeaf) {
		$('#exp'+r.parentID).html('<img src="img/_.png" class="icon" alt=" "/>');
	}
}

function addNode (rowHtml, rowToken, isActive, position) {
	// alert(rowHtml);
	newRow = $('<tr>');
	newRow
		.addClass((position % 2 ? 'even' : 'odd') + (isActive ? '' : 'inactive'))
		.attr('id', rowToken)
		.html(rowHtml);
	nodeTreeObj = $('#nodeTree');
	prevRow = $('tr:nth-child('+position+')', nodeTreeObj);
	prevRow.after(newRow);
	console.log('position '+position+', nodeTreeObj '+prevRow.attr('id'));
	for (i = position + 1; i < nodeTreeObj[0].rows.length; i ++) {
		swapRowColour(nodeTreeObj[0].rows[i]);
	}
	parentToken = rowToken.substr(0, rowToken.length - 6);
	$('#exp'+parentToken).html('<a href="javascript:expandTree(\'' + parentToken + '\')"><img src="img/e_d.png" id="' + parentToken + 'expander" class="icon" alt="-"/></a>');
}

</script>
<table class="listing" id="nodeTree">
	<tr>
		<th class="<?= $nodeType ?>Icons"> </th>
		<? switch ($nodeType) {
			case 'person': ?>
		<th>Name</th>
			<?php break;
			case 'item': ?>
		<th>Item</th>
		<th class="qty">Qty</th>
		<th class="prices">Price</th>
		<? } ?>
	</tr>
<?php
$rootPath = $node->getNodePath(false);
foreach ($rootPath as $i => $thisID) {
	$rootPath[$i] = sprintf('%05s', $thisID);
}
$treeStack = array ('node0' . (count($rootPath) ? '_' . implode('_', $rootPath) : null));
$pathDepth = 0;

function renderTreeView ($tree) {
	global $treeStack, $pathDepth, $nodeType;
	foreach ($tree as $v) {
		$thisNode = $v['node'];
		$objectID = $thisNode->getObjectID();
		array_push($treeStack, sprintf('%05s', $objectID));
		$pathDepth ++;
		$treeToken = implode('_', $treeStack); ?>
		<tr id="<?= $treeToken ?>"<?= $thisNode->isActive() ? null : ' class="inactive"' ?>>
			<? switch ($nodeType) {
				case 'person':
					outputPersonRow($thisNode, $treeToken);
					break;
				case 'item':
					outputItemRow($thisNode, $treeToken);
			} ?>
		</tr>
		<? if ($v['children'] && count($v['children'])) {
			renderTreeView($v['children']);
		}
		array_pop($treeStack);
		$pathDepth --;
	}
}

renderTreeView($tree);

?>
</table>
