<script type="text/javascript" language="JavaScript">

function submitPersonInfo () {
	editPersonForm = document.getElementById('editPersonForm');
	params = 'ajax=1&action=editPerson&personID=' + editPersonForm.personID.value +
		'&contactName=' + encodeURIComponent(editPersonForm.contactName.value) +
		'&groupName=' + encodeURIComponent(editPersonForm.groupName.value) +
		'&customer=' + (editPersonForm.customer.checked ? '1' : '0') +
		'&supplier=' + (editPersonForm.supplier.checked ? '1' : '0') +
		'&depot=' + (editPersonForm.depot.checked ? '1' : '0') +
		'&category=' + (editPersonForm.category.checked ? '1' : '0') +
		'&address1=' + encodeURIComponent(editPersonForm.address1.value) +
		'&address2=' + encodeURIComponent(editPersonForm.address2.value) +
		'&city=' + encodeURIComponent(editPersonForm.city.value) +
		'&postalCode=' + encodeURIComponent(editPersonForm.postalCode.value) +
		'&directions=' + encodeURIComponent(editPersonForm.directions.value) +
		'&phone=' + encodeURIComponent(editPersonForm.phone.value) +
		'&email=' + encodeURIComponent(editPersonForm.email.value) +
		'&password=' + encodeURIComponent(editPersonForm.password.value) +
		'&payTypeID=' + editPersonForm.payTypeID.options[editPersonForm.payTypeID.selectedIndex].value +
		'&minOrder=' + encodeURIComponent(editPersonForm.minOrder.value) +
		'&bulkDiscount=' + encodeURIComponent(editPersonForm.bulkDiscount.value) +
		'&bulkDiscountQuantity=' + encodeURIComponent(editPersonForm.bulkDiscountQuantity.value) +
		'&maxStars=' + encodeURIComponent(editPersonForm.maxStars.value) +
		'&deposit=' + encodeURIComponent(editPersonForm.deposit.value) +
		'&stars=' + encodeURIComponent(editPersonForm.stars.value) +
		'&recent=' + (editPersonForm.recent.checked ? '1' : '0') +
		'&notes=' + encodeURIComponent(editPersonForm.notes.value) +
		'&description=' + encodeURIComponent(editPersonForm.description.value) +
		'&website=' + encodeURIComponent(editPersonForm.website.value) +
		'&customCancelsRecurring=' + (editPersonForm.customCancelsRecurring.checked ? '1' : '0') +
		'&compost=' + (editPersonForm.compost.checked ? '1' : '0') +
		'&active=' + (editPersonForm.active.checked ? '1' : '0') +
		'&parentID=' + encodeURIComponent(editPersonForm.parentID.value);
	xmlHttp = getXmlHttpObject ();
	xmlHttp.onreadystatechange = validatePersonInfo;
	xmlHttp.open('POST', 'managePeople.php', true);
	xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   xmlHttp.setRequestHeader("Content-length", params.length);
   xmlHttp.setRequestHeader("Connection", "close");
   xmlHttp.send(params);
}

function validatePersonInfo () {
	if (xmlHttp.readyState == 4) {
		result = xmlHttp.responseText;
		// document.getElementById('errors').innerHTML = '<pre>' + result + '</pre>';
		try { result = eval('(' + result + ')'); }
		catch (err) { alert (err.description + "\n" + err.message + "\n" + err.lineNumber + "\n" + err.name + "\n" + err.number + "\n"); }
		clearFormFields('editPersonForm');
		editPersonForm = document.getElementById('editPersonForm');
		personFormLabel = document.getElementById('personFormLabel');
		personID = Number(result['personID']);
		active = result['active'];
		editPersonForm.personID.value = personID;
		editPersonForm.contactName.value = result['contactName'];
		editPersonForm.groupName.value = result['groupName'];
		editPersonForm.customer.checked = (result['personType'] & <? echo P_CUSTOMER; ?> ? true : false);
		editPersonForm.supplier.checked = (result['personType'] & <? echo P_SUPPLIER; ?> ? true : false);
		editPersonForm.depot.checked = (result['personType'] & <? echo P_DEPOT; ?> ? true : false);
		editPersonForm.category.checked = (result['personType'] & <? echo P_CATEGORY; ?> ? true : false);
		editPersonForm.deliverer.checked = (result['personType'] & <? echo P_DELIVERER; ?> ? true : false);
		editPersonForm.address1.value = result['address1'];
		editPersonForm.address2.value = result['address2'];
		editPersonForm.city.value = result['city'];
		editPersonForm.postalCode.value = result['postalCode'];
		editPersonForm.directions.value = result['directions'];
		editPersonForm.phone.value = result['phone'];
		editPersonForm.email.value = result['email'];
		editPersonForm.payTypeID.selectedIndex = result['payTypeID'];
		editPersonForm.minOrder.value = result['minOrder'];
		editPersonForm.bulkDiscount.value = result['bulkDiscount'];
		editPersonForm.bulkDiscountQuantity.value = result['bulkDiscountQuantity'];
		editPersonForm.maxStars.value = result['maxStars'];
		editPersonForm.deposit.value = result['deposit'];
		editPersonForm.stars.value = result['stars'];
		editPersonForm.recent.checked = result['recent'];
		editPersonForm.notes.value = result['notes'];
		editPersonForm.description.value = result['description'];
		editPersonForm.website.value = result['website'];
		editPersonForm.customCancelsRecurring.checked = result['customCancelsRecurring'];
		editPersonForm.compost.checked = result['compost'];
		editPersonForm.active.checked = result['active'];
		editPersonForm.parentID.value = result['parentID'];
		switch (result['status']) {
			case -1:
				alert ('You have some errors in your data. Please check the red boxes.');
				personFormLabel.innerHTML = 'You have some errors in your data. Please check the red boxes.';
				for (i = 0; i < result['errorFields'].length; i ++) {
					editPersonForm[result['errorFields'][i]].className = 'invalid';
				}
				break;
			case 0:
				alert ('An error has occured. Please try pressing \'save\' again.');
				personFormLabel.innerHTML = 'An error has occured. Please try pressing \'save\' again.';
				break;
			case 1:
			case 2:
				if (confirm ('Saved! You may now return to the main menu.')) window.location = 'index.php';
				personFormLabel.innerHTML = 'Saved!';
		}
	}
}

var routes = new Array ();
<?php
$i = 0;
foreach ($routes as $thisRoute) {
	$thisRouteID = (int) $thisRoute->routeID;
	echo 'routes[' . $thisRouteID . "] = { 'routeID': " . $thisRouteID . ", 'routeIndex': " . $i . ", 'label': '" . $thisRoute->label . "', 'active': " . ($thisRoute->active ? 'true' : 'false' ) . " };\n";
	echo 'routes[' . $thisRouteID . "]['days'] = new Array ();\n";
	foreach ($thisRoute->routeDays as $thisRouteDay) {
		echo 'routes[' . $thisRouteID . "]['days'][" . (int) $thisRouteDay->deliveryDayID . '] = ' . $thisRouteDay->deliverySlot . ";\n";
	}
	$i ++;
} ?>


</script>

<h2>Create person in <? echo $parent->getLabel(); ?></h2>
<h3 id="personFormLabel" class="formLabel"></h3>
<form id="editPersonForm"><input type="hidden" name="personID"/><input type="hidden" name="parentID" value="<? echo $parent->personID; ?>"/><input type="hidden" name="action" value="editPerson"/>
<? $newPerson = true;
include ($path . '/market/templates/editPersonTable.tpl.php'); ?>
</form>
