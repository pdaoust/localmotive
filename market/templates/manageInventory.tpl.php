<script src="templates/bsn.AutoSuggest.js" type="text/javascript"></script>
<script type="text/javascript" language="JavaScript">

var categoryIDs = [<?php
$categoryIDs = array ();
foreach ($categories as $thisCategory) {
	$categoryIDs[] = $thisCategory->personID;
}
echo implode(', ', $categoryIDs); ?>];

$(function () {
	$('#editItemBox').dialog({'autoOpen': false, 'width': 600});
	$('#moveList').dialog({'autoOpen': false, 'width': 400});

	$('#editItemForm').ajaxForm({
		data: { ajax: 1 },
		timeout: <?= (int) $config['ajaxTimeout'] * 20 ?>,
		beforeSubmit: function (r) {
			$('#submitEditItemForm').val('Saving...');
		},
		error: function (r, desc, nature) {
			$('#submitEditItemForm').val('Save');
			console.log('failed to submit data on save: ' + desc);
		},
		success: function (r) {
			validateItemInfo(r);
		},
		dataType: 'json',
		iframe: true
	});
	$('#personText').autocomplete({
		'source': 'getPersonID.php?parentID=<?= $user->personID ?>',
		'minLength': 3,
		'select': function (e, u) {
			$('#newPersonID').val(u.item.id);
			return true;
		}
	});
});

function editNode (itemID) {
	$('#editItemBox').dialog('option', 'title', (itemID ? 'Edit' : 'New') + ' item')
		.removeClass('valid')
		.removeClass('invalid');
	$('#submitEditItemForm').text('Save');
	$('#editItemErrors').hide();
	$.ajax({
		data: {
			ajax: 1,
			action: 'loadItem',
			itemID: itemID,
			parentID: (document.getElementById('editItemForm').parentID.value ? document.getElementById('editItemForm').parentID.value : null)
		},
		dataType: 'json',
		type: 'post',
		url: 'manageInventory.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function (r) {
			alert('Could not load item\'s data, error ' + r);
		},
		success: function (r) {
			if (r.status) {
				populateEditItemForm(r);
				h = window.innerHeight - 50;
				$('#editItemBox').dialog('option', 'height', h).dialog('open');
			}
		}
	});
}

function populateEditItemForm (r) {
	clearFormFields('editItemForm');
	editItemForm = document.getElementById('editItemForm');
	editItemForm.itemID.value = r.itemID;
	imageUploadField = $('#imageUploadControl');
	imageUploadField.html(imageUploadField.html());
	// TODO: why aren't these auto-populating?
	if (r.itemID == 1) {
		$('#canOrderPastZeroI').attr('disabled', true);
		$('#trackInventoryI').attr('disabled', true);
		$('#specialPackingI').attr('disabled', true);
		$('#availableToRecurringI').attr('disabled', true);
		$('#csaRequiredI').attr('disabled', true);
		$('#organicI').attr('disabled', true);
		$('#canBePermanentI').attr('disabled', true);
		$('[name=itemType]').attr('disabled', true);
		$('#itemTypeCategory').attr('checked', true);
	} else {
		$('#canOrderPastZeroI').attr('disabled', false);
		$('#canOrderPastZeroParent').html('inherit (' + (r.canOrderPastZeroParent ? 'yes' : 'no') + ')');
		$('#trackInventoryI').attr('disabled', false);
		$('#trackInventoryParent').html('inherit (' + (r.trackInventoryParent ? 'yes' : 'no') + ')');
		$('#specialPackingI').attr('disabled', false);
		$('#specialPackingParent').html('inherit (' + (r.specialPackingParent ? 'yes' : 'no') + ')');
		/*$('#availableToRecurringI').attr('disabled', false);
		$('#availableToRecurringParent').html('inherit (' + (r.availableToRecurringParent ? 'yes' : 'no') + ')');*/
		$('#csaRequiredI').attr('disabled', false);
		$('#csaRequiredParent').html('inherit (' + (r.csaRequiredParent ? 'yes' : 'no') + ')');
		$('#organicI').attr('disabled', false);
		$('#organicParent').html('inherit (' + (r.organicParent ? 'yes' : 'no') + ')');
		$('#canBePermanentI').attr('disabled', false);
		$('#canBePermanentParent').html('inherit (' + (r.canBePermanentParent ? 'yes' : 'no') + ')');
		$('[name=itemType]').attr('disabled', false);
	}
	/*$(editItemForm.canOrderPastZero).each(function () {
		if ($(this).val() == r.canOrderPastZero) $(this).attr('checked', 'checked');
	});
	$(editItemForm.trackInventory).each(function () {
		if ($(this).val() == r.trackInventory) $(this).attr('checked', 'checked');
	});
	$(editItemForm.specialPacking).each(function () {
		if ($(this).val() == r.specialPacking) $(this).attr('checked', 'checked');
	});
	$(editItemForm.availableToRecurring).each(function () {
		if ($(this).val() == r.availableToRecurring) $(this).attr('checked', 'checked');
	});
	$(editItemForm.csaRequired).each(function () {
		if ($(this).val() == r.csaRequired) $(this).attr('checked', 'checked');
	});
	$(editItemForm.organic).each(function () {
		if ($(this).val() == r.organic) $(this).attr('checked', 'checked');
	});
	$(editItemForm.canBePermanent).each(function () {
		if ($(this).val() == r.canBePermanent) $(this).attr('checked', 'checked');
	});*/
	$('.triple').each(function () {
		switch ($(this).val()) {
			case '1':
				if (r[$(this).attr('name')]) $(this).attr('checked', true);
				break;
			case '0':
				if (!r[$(this).attr('name')] && !(r[$(this).attr('name')] === null)) $(this).attr('checked', true);
				break;
			case '-1':
				if (r[$(this).attr('name')] === null) $(this).attr('checked', true);
		}
	});
	editItemForm.itemID.value = r.itemID;
	editItemForm.parentID.value = r.parentID;
	editItemForm.label.value = r.label;
	editItemForm.location.value = r.location;
	editItemForm.distance.value = r.distance;
	editItemForm.description.value = r.description;
	editItemForm.quantity.value = r.quantity;
	editItemForm.reorderQuantity.value = r.reorderQuantity;
	editItemForm.runningOutQuantity.value = r.runningOutQuantity;
	if (r.itemType & <?= I_CATEGORY ?>) {
		$('#itemTypeCategory').attr('checked', true);
	} else {
		$('#itemTypeItem').attr('checked', true);
	}
	// -----
	$('#deleteImage').attr('checked', false);
	$('#imageUpload').attr('disabled', false);
	if (r.image) {
		now = new Date();
		$('#imagePreview').html('<img src="productImages/' + r.itemID + '_s.jpg?' + now.getTime() + '" alt="photo of item" class="alignleft product"/>');
		$('#deleteImageControl').show();
	} else {
		$('#imagePreview').html('');
		$('#deleteImageControl').hide();
	}
	if (r.active) editItemForm.active.checked = true;
	else editItemForm.active.checked = false;
	var keepPrices = new Array ();
	for (var priceID in categoryIDs) {
		keepPrices.push(categoryIDs[priceID]);
	}
	for (var priceID in r.prices) {
		if (!grep(priceID, keepPrices)) keepPrices.push(priceID);
	}
	clearExtraPrices(keepPrices);
	for (var i in r.prices) {
		thisPrice = r.prices[i];
		if (!document.getElementById('price' + i)) createPriceRow(i, thisPrice.label);
		editItemForm['price[' + i + '][price]'].value = thisPrice.price;
		editItemForm['price[' + i + '][multiple]'].value = thisPrice.multiple;
		editItemForm['price[' + i + '][pst]'].checked = Boolean(thisPrice.pst);
		editItemForm['price[' + i + '][hst]'].checked = Boolean(thisPrice.hst);
	}
}

function validateItemInfo (itemInfo) {
	clearFormFields('editItemForm');
	$('#submitEditItemForm').val('Save');
	populateEditItemForm(itemInfo);
	editItemForm = document.getElementById('editItemForm');
	/* if (itemInfo.image) {
		document.getElementById('imagePreview').innerHTML = '<img src="productImages/' + itemID + '_s.jpg" alt="photo of item" class="alignleft"/>';
		document.getElementById('deleteImageControl').style.display = 'inline';
	} else {
		document.getElementById('imagePreview').innerHTML = '';
		document.getElementById('deleteImageControl').style.display = 'none';
		editItemForm.image.disabled = false;
	} */
	switch (itemInfo.status) {
		case -1:
			$('#editItemErrors').text('You have some errors in your data. Please check the red boxes.').show('fast');
			$('#editItemBox').removeClass('valid').addClass('invalid');
			for (i = 0; i < itemInfo.errorFields.length; i ++) {
				$(editItemForm[itemInfo.errorFields[i]]).addClass('invalid');
			}
			break;
		case 0:
			$('#editItemErrors').text('An error has occured. Please try pressing \'save\' again.').show('fast');
			$('#editItemBox').removeClass('valid').addClass('invalid');
			break;
		case 1:
			$('#editItemErrors').hide('fast');
			$('#editItemBox').removeClass('invalid').addClass('valid');
			toggleActive(itemInfo.activeStates);
			$('#n' + itemInfo.itemID + '_l').text(itemInfo.label);
			$('#n' + itemInfo.itemID + '_n').text(itemInfo.description);
			$('#n' + itemInfo.itemID + '_pr').text(itemInfo.priceInfo);
			$('#editItemBox').dialog('close');
			break;
		case 2:
			$('#editItemErrors').hide('fast');
			addNode(itemInfo.newRow, itemInfo.path, itemInfo.isActive, itemInfo.position);
			$('#editItemBox').dialog('close');
	}
}

function newNode (parentID) {
	document.getElementById('editItemForm').parentID.value = parentID;
	editNode(0);
}

function toggleImageUpload () {
	editItemForm = document.getElementById('editItemForm');
	if (editItemForm.deleteImage.checked) {
		editItemForm.image.disabled = true;
		editItemForm.image.value = null;
	} else {
		editItemForm.image.disabled = false;
	}
}

function deleteNode (itemID, itemType) {
	if (confirm('Are you sure you want to delete this ' + itemType + '? (Note: if this item has been ordered in the past, it will be deactivated instead.)')) {
		$.ajax({
			data: {
				ajax: 1,
				action: 'deleteItem',
				itemID: itemID
			},
			action: 'manageInventory.php',
			type: 'post',
			dataType: 'json',
			error: function () {
				alert('Couldn\'t delete item');
			},
			success: function (r) {
				confirmDeleteNode(r);
			}
		});
	}
}

function clearExtraPrices (keepPrices) {
	deletePrices = new Array ();
	$('#priceList li').each(function () {
		priceID = $(this).attr('id');
		regex = /\d+/;
		priceID = Number(regex.exec(priceID)[0]);
		try {
			if ($.inArray(priceID, keepPrices) == -1) deletePrices.push(priceID);
			else $('input', this).each(function () {
				switch ($(this).attr('type')) {
					case 'text':
						$(this).val('');
						break;
					case 'checkbox':
						$(this).attr('checked', false);
				}
			});
		} catch (err) {
			console.log('soemthing went south: ' + err);
		}
	});
	for (i in deletePrices) {
		$('#price' + deletePrices[i]).remove();
	}
	$('#personText').val();
	$('#newPersonID').val();
}

function setParent (itemID) {
	$.ajax({
		data: {
			ajax: 1,
			action: 'loadMoveTree',
			itemID: itemID
		},
		url: 'manageInventory.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			console.log('failed to load the moving dialogue box');
		},
		success: function (r) {
			$('#moveList').html(r).dialog('open');
		},
		type: 'POST'
	});
}

</script>
<h2>Manage inventory</h2>
<?php
$nodeType = 'item';
$objectID = 'itemID';
$thisPage = 'manageInventory.php';
include($path . '/market/templates/treeview.tpl.php');
?>
<div id="editItemBox" title="Edit item" style="display: none;">
	<p class="errorBox" id="editItemErrors" style="display: none;"></p>
	<form id="editItemForm" action="manageInventory.php" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="itemID"/>
		<input type="hidden" name="parentID"/>
		<input type="hidden" name="action" value="editItem"/>
		<input type="hidden" name="nodeID" value="<?= $category->itemID ?>"/>
		<fieldset>
			<legend>Item info</legend>
			<ul class="form">
				<li>
					<label for="label">Label</label>
					<input type="text" name="label" size="50"/>
				</li>
				<li>
					<label for="itemType">Item type</label>
					<span class="widget">
						<label><input type="radio" name="itemType" id="itemTypeItem" value="<?= I_ITEM ?>"/> item</label>
						<label><input type="radio" name="itemType" id="itemTypeCategory" value="<?= I_CATEGORY ?>"/> category</label>
					</span>
				</li>
				<li>
					<label for="sku">SKU</label>
					<input type="text" name="sku" size="20"/>
				</li>
				<li>
					<label for="location">Location</label>
					<span class="widget"><input type="text" name="location"/> (<input type="text" name="distance" size="3" maxlength="5"/> <label for="distance">km away)</label></span>
				</li>
				<li>
					<label for="description">Description</label>
					<textarea name="description" rows="5" cols="30"></textarea>
				</li>
				<li>
					<label for="image">Image</label>
					<span class="widget">
						<span id="imagePreview"></span><br/>
						<span id="deleteImageControl" style="display: none;"><input type="checkbox" name="deleteImage" id="deleteImage" onchange="toggleImageUpload()"/> delete</span><br/>
						<span id="imageUploadControl"><input type="file" name="image" id="imageUpload"/></span>
					</span>
				</li>
				<li>
					<label for="organic">Organic <img src="img/o.png" class="icon"/></label>
					<span class="widget"><input type="radio" name="organic" id="organicY" value="1" class="triple"/> <label for="organicY">yes</label> <input type="radio" name="organic" id="organicNo" value="0" class="triple"/> <label for="organicNo">no</label> <input type="radio" name="organic" id="organicI" value="-1" class="triple"/> <label for="organicI" id="organicParent">inherit</label></span>
				</li>
				<li>
					<label for="specialPacking">Perishable <img src="img/cold.png" class="icon"/></label>
					<span class="widget"><input type="radio" name="specialPacking" id="specialPackingY" value="1" class="triple"/> <label for="specialPackingY">yes</label> <input type="radio" name="specialPacking" id="specialPackingNo" value="0" class="triple"/> <label for="specialPackingNo">no</label> <input type="radio" name="specialPacking" id="specialPackingI" value="-1" class="triple"/> <label for="specialPackingI" id="specialPackingParent">inherit</label></span>
				</li>
				<li>
					<label for="active">Active</label>
					<input type="checkbox" name="active"/>
				</li>
			</ul>
		</fieldset>
		<fieldset>
			<legend>Inventory</legend>
			<ul class="form">
				<li>
					<span class="label">Track inventory</span>
					<span class="widget"><input type="radio" name="trackInventory" id="trackInventoryY" value="1" class="triple"/> <label for="trackInventoryY">yes</label> <input type="radio" name="trackInventory" id="trackInventoryNo" value="0" class="triple"/> <label for="trackInventoryNo">no</label> <input type="radio" name="trackInventory" id="trackInventoryI" value="-1" class="triple"/> <label for="trackInventoryI" id="trackInventoryParent">inherit</label></span>
				</li>
				<li>
					<span class="label">Orderable when empty</span>
					<span class="widget"><input type="radio" name="canOrderPastZero" id="canOrderPastZeroY" value="1" class="triple"/> <label for="canOrderPastZeroY">yes</label> <input type="radio" name="canOrderPastZero" id="canOrderPastZeroNo" value="0" class="triple"/> <label for="canOrderPastZeroNo">no</label> <input type="radio" name="canOrderPastZero" id="canOrderPastZeroI" value="-1" class="triple"/> <label for="canOrderPastZeroI" id="canOrderPastZeroParent">inherit</label></span>
				</li>
				<li>
					<label for="quantity">Quantity</label>
					<span class="widget"><input type="text" name="quantity" size="3"/></span>
				</li>
				<li>
					<label for="reorderQuantity">Reorder warning at</label>
					<span class="widget"><input type="text" name="reorderQuantity" size="3"/></span>
					<span class="hint">should be greater than 0</span>
				</li>
				<li>
					<label for="runningOutQuantity">Running-out warning at</label>
					<span class="widget"><input type="text" name="runningOutQuantity" size="3"/></span>
					<span class="hint">should be greater than 0</span>
				</li>
			</ul>
		</fieldset>
		<fieldset>
			<legend>Ordering</legend>
			<ul class="form">
				<li>
					<label for="cutoffDay">Cutoff day</label>
					<span class="widget"><input type="text" name="cutoffDay" size="2"/> (added to route's cutoff day)</span>
				</li>
				<li>
					<span class="label">Regular stock</span>
					<span class="widget"><input type="radio" name="canBePermanent" id="canBePermanentY" value="1" class="triple"/> <label for="canBePermanentY">yes</label> <input type="radio" name="canBePermanent" id="canBePermanentNo" value="0" class="triple"/> <label for="canBePermanentNo">no</label> <input type="radio" name="canBePermanent" id="canBePermanentI" value="-1" class="triple"/> <label for="canBePermanentI" id="canBePermanentParent">inherit</label></span>
					<span class="hint">regular stock items can be put on recurring orders</span>
				</li>
				<li>
					<span class="label">CSA item</span>
					<span class="widget"><input type="radio" name="csaRequired" id="csaRequiredY" value="1" class="triple"/> <label for="csaRequiredY">yes</label> <input type="radio" name="csaRequired" id="csaRequiredNo" value="0" class="triple"/> <label for="csaRequiredNo">no</label> <input type="radio" name="csaRequired" id="csaRequiredI" value="-1" class="triple"/> <label for="csaRequiredI" id="csaRequiredParent">inherit</label></span>
					<span class="hint">at least one CSA item must be on a CSA subscriber's order</span>
				</li>
			</ul>
		</fieldset>
		<fieldset>
			<legend>Prices</legend>
			<ul class="form nohints" id="priceList">
				<? foreach ($categories as $thisCategory) { ?>
				<li id="price<?= $thisCategory->personID ?>">
					<span class="label"><?= htmlEscape($thisCategory->getLabel()) ?></span>
					<span class="widget"><label>$<input type="text" name="price[<?= $thisCategory->personID ?>][price]" size="6"/></label> <label>per <input type="text" name="price[<?= $thisCategory->personID ?>][multiple]" size="3"/></label> <label>PST <input type="checkbox" name="price[<?= $thisCategory->personID ?>][pst]"/></label> <label>HST <input type="checkbox" name="price[<?= $thisCategory->personID ?>][hst]"/></label></span>
				</li>
				<? } ?>
			</ul>
			<p>Special price for <input type="text" id="personText"/> <input type="hidden" id="newPersonID"/><input type="button" value="+" onclick="addSpecialPrice()"/></p>
		</fieldset>
		<input type="submit" value="Save" id="submitEditItemForm"/>
	</form>
</div>

<script type="text/javascript" language="JavaScript">

function addSpecialPrice () {
	personName = $('#personText').val();
	personID = $('#newPersonID').val();
	existingPrice = $('#price' + personID);
	if (existingPrice.attr('id')) {
		funcName = 'flashPrice(\'price' + personID + '\')';
		intervalID = setInterval(funcName, 500);
		setTimeout('clearInterval(' + intervalID + ')', 3000);
		return false;
	}
	createPriceRow (personID, personName);
}

function createPriceRow (personID, personName) {
	console.log('personID '+personID+', personName '+personName);
	newPriceRow = $('<li>');
	newPriceRow.html('<span class="label">' + htmlEscape(personName) + '</span><span class="widget"><label>$<input type="text" name="price[' + personID + '][price]" size="6"/></label> <label>per <input type="text" name="price[' + personID + '][multiple]" size="3"/></label> <label>PST <input type="checkbox" name="price[' + personID + '][pst]"/></label> <label>HST <input type="checkbox" name="price[' + personID + '][hst]"/></label></span>');
	newPriceRow.attr('id', 'price' + personID);
	$('#priceList').append(newPriceRow);
}

function deletePriceRow (priceRowID) {
	if (priceRow = document.getElementById(priceRowID)) {
		pricesTableObj = document.getElementById('pricesTable');
		pricesTableObj.removeChild(priceRow);
	}
}

function flashPrice (priceRowID) {
	$('#' + priceRowID).toggleClass('notice');
}
</script>
<div id="moveList" title="Move item/category to" style="white-space: nowrap;"></div>
