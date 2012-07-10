<? header('Content-type: text/javascript');
require_once ('../marketInit.inc.php');
require_once('../classes/base.inc.php');
require_once('../config.inc.php');
require_once('../classes/misc.inc.php');
?>

if (! ("console" in window) || !("firebug" in console)) {
	var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml", "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];
	window.console = {};
	for (var i = 0; i <names.length; ++i) window.console[names[i]] = function() {};
}

function htmlEscape (unsafe) {
  return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}


var T_MINUTE = 60;
var T_HOUR = 3600;
var T_DAY = 86400;
var T_WEEK = 604800;
// special case; month and cannot be consistently defined as periods
var T_MONTH = -1;
var T_YEAR = -12;

function grep (needle, haystack) {
	return $.inArray(needle, haystack);
}

// Initialize upon load to let all browsers establish content objects
function spawnActivity (personID) {
	window.open('activity.php?style=dialogue&personID=' + Number(personID), 'activity' + Number(personID), 'location=0,directories=0,height=400,width=600,menubar=1,resizable=1,scrollbars=1');
}

function spawnOrderHistory (personID) {
	window.open('orderHistory.php?style=dialogue&personID=' + Number(personID), 'orderHistory' + Number(personID), 'location=0,directories=0,height=400,width=600,menubar=1,resizable=1,scrollbars=1');
}

function spawnOrder (orderID) {
	window.open('orderView.php?style=dialogue&orderID=' + Number(orderID), 'orderView' + Number(orderID), 'location=0,directories=0,height=600,width=1100,menubar=1,resizable=1,scrollbars=1');
}

function spawnRouteMap () {
	window.open('routeMap.php', 'routeMap', 'location=0,directories=0,height=600,width=800,menubar=1,resizable=1,scrollbars=1');
}

function newOrder (personID, orderType) {
	window.open('order.php?customerID=' + Number(personID) + '&orderType=' + orderType, 'order' + Number(personID), 'location=0,directories=0,height=600,width=1100,menubar=1,resizable=1,scrollbars=1');
}

var xmlHttp;

function getXmlHttpObject () {
	var xmlHttp;
	try {
		// Firefox, Opera 8.0+, Safari
		xmlHttp=new XMLHttpRequest();
	} catch (e) {
		// Internet Explorer
		try {
			xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {
				return false;
			}
		}
	}
	return xmlHttp;
}

function getParentRow (el) {
   while (el.parentNode && 'tr' != el.nodeName.toLowerCase()) {
     el = el.parentNode;
   }
   return el;
}

function moveRow (el, x) {
	el = getParentRow(el);
   var t = el.parentNode;
   var j = el.sectionRowIndex;
   var i = (j + Number(x)) % t.rows.length;
   if ((i - j) % 2) {
   	if (i > j) {
   		var rowStart = j;
   		var rowEnd = i;
   	} else {
   		var rowStart = i;
   		var rowEnd = j;
   	}
   	for (i2 = rowStart; i2 <= rowEnd; i2 ++) {
   		swapRowColour(t.rows[i2]);
   	}
	}
   t.replaceChild(t.removeChild(el), t.insertRow(i));
}

function deleteRow (el) {
	el = getParentRow (el);
   var t = el.parentNode;
   t.removeChild(el);
}

function clearFormFields (formID) {
	editForm = document.getElementById(formID);
	$(editForm.elements).each(function () {
		$(this).removeClass('errorField');
	});
}

function submitComment () {
	commentFormObj = document.getElementById('commentForm');
	commentStatusObj = document.getElementById('commentStatus');
	if (!commentFormObj.nature.selectedIndex) {
		commentStatusObj.innerHTML = 'Please choose whether this is a service- or technical-related comment.';
		commentFormObj.nature.className = 'invalid';
		commentStatusObj.className = 'notice';
		commentStatusObj.style.display = 'inline';
		return false;
	}
	xmlHttp = getXmlHttpObject ();
	xmlHttp.onreadystatechange = confirmComment;
	xmlHttp.open('GET', 'submitComment.php?nature=' + commentFormObj.nature.options[commentFormObj.nature.selectedIndex].value + '&personID=' + commentFormObj.personID.value + '&page=' + commentFormObj.page.value + '&comments=' + encodeURIComponent(commentFormObj.comments.value), true);
	xmlHttp.send(null);
	commentStatusObj.innerHTML = 'Sending comment...';
	commentStatusObj.className = null;
	commentFormObj.nature.className = null;
	commentStatusObj.style.display = 'inline';
}

function confirmComment () {
	if (xmlHttp.readyState == 4) {
		result = xmlHttp.responseText;
		commentStatusObj = document.getElementById('commentStatus');
		if (result == '1') {
			commentStatusObj.innerHTML = 'Thank you for your comment!';
			commentStatusObj.className = 'okay';
		} else {
			commentStatusObj.innerHTML = 'We have disabled comments for now.';
			commentStatusObj.className = 'notice';
		}
	}
}

function swapRowColour (rowObj) {
	if (!rowObj instanceof jQuery) rowObj = $(rowObj);
	if ($(rowObj).hasClass('odd')) $(rowObj).addClass('even').removeClass('odd');
	else $(rowObj).addClass('odd').removeClass('even');
}

function toggleDiv (divID, statusBox) {
	thisDiv = document.getElementById(divID);
	if (typeof statusBox == 'object') {
		if (statusBox.length == 3) {
			try { thisStatus = document.getElementById(statusBox[0]); }
			catch (err) { thisStatus = false; }
		}
	}
	if (thisDiv.style.display == 'none') {
		thisDiv.style.display = 'block';
		if (thisStatus) thisStatus.innerHTML = statusBox[2];
	} else {
		thisDiv.style.display = 'none';
		if (thisStatus) thisStatus.innerHTML = statusBox[1];
	}
}

function addAddress (addressData, fromNewAddress) {
	fromNewAddress = typeof(fromNewAddress) != 'undefined' ? fromNewAddress : false;
	lastNewAddyID = Number($('#newAddyQty').val());
	newAddy = false;
	if (addressData == null) {
		newAddy = true;
		newAddyID = lastNewAddyID + 1;
		addressData = {
			'addressID': newAddyID,
			'addressType': 0,
			'careOf': '',
			'address1': '',
			'address2': '',
			'city': '',
			'prov': '',
			'postalCode': '',
			'phone': '',
			'directions': ''
		};
	} else if (fromNewAddress) newAddy = true;
	if (addressData.errorFields) errorFields = addressData.errorFields;
	else errorFields = new Array ();
	thisToken = (newAddy ? 'new' : '') + 'addresses[' + addressData.addressID + ']';
	thisAddy = $('<ul>');
	thisAddy.addClass('form');
	thisAddy.attr('id', thisToken);
	// address type
	addyType = $('<li>');
	addyType.html('<label>Address type</label> <input type="checkbox" name="' + thisToken + '[ship]"' + (addressData.addressType & <?= AD_SHIP ?> ? ' checked="checked"' : '') + '> <label for="' + thisToken + '[ship]" class="noform">shipping</label> &nbsp; <input type="checkbox" name="' + thisToken + '[mail]"' + (addressData.addressType & <?=  AD_MAIL ?> ? ' checked="checked"' : '') + '> <label for="' + thisToken + '[mail]" class="noform">mailing</label> &nbsp; <input type="checkbox" name="' + thisToken + '[pay]"' + (addressData.addressType & <?= AD_PAY ?> ? ' checked="checked"' : '') + '> <label for="' + thisToken + '[pay]" class="noform">payment drop-off</label>');
	if ($.inArray('addressType', errorFields) > -1) addyType.addClass('errorField');
	thisAddy.append(addyType);
	// care of
	careOf = $('<li>');
	careOf.html('<label for="' + thisToken + '[careOf]">Care of</label> <input type="text" name="' + thisToken + '[careOf]" value="' + addressData.careOf + '"/>');
	if ($.inArray('careOf', errorFields) > -1) careOf.addClass('errorField');
	thisAddy.append(careOf);
	// address
	addy1 = $('<li>');
	addy1.html('<label for="' + thisToken + '[address1]">Address</label> <input type="text" name="' + thisToken + '[address1]" value="' + addressData.address1 + '"/>');
	if ($.inArray('address1', errorFields) > -1) addy1.addClass('errorField');
	thisAddy.append(addy1);
	addy2 = $('<li>');
	addy2.html('<label for="' + thisToken + '[address2]">(line 2)</label> <input type="text" name="' + thisToken + '[address2]" value="' + addressData.address2 + '"/>');
	if ($.inArray('address2', errorFields) > -1) addy1.addClass('errorField');
	thisAddy.append(addy2);
	// city
	city = $('<li>');
	city.html('<label for="' + thisToken + '[city]">City</label> <span class="widget"><input type="text" name="' + thisToken + '[city]" value="' + addressData.city + '"/>, <input type="text" name="' + thisToken + '[prov]" value="' + (addressData.prov ? addressData.prov : '<?= addslashes($config['provDefault']) ?>') + '" size="2" maxlength="4"' + ($.inArray('prov', errorFields) > -1 ? ' class="errorField"' : '') + '/></span>');
	if ($.inArray('city', errorFields) > -1) city.addClass('errorField');
	thisAddy.append(city);
	// postal code
	postalCode = $('<li>');
	postalCode.html('<label for="' + thisToken + '[postalCode]">Postal code</label> <input type="text" name="' + thisToken + '[postalCode]" value="' + addressData.postalCode + '"/>');
	if ($.inArray('postalCode', errorFields) > -1) postalCode.addClass('errorField');
	thisAddy.append(postalCode);
	// phone
	phone = $('<li>');
	phone.html('<label for="' + thisToken + '[phone]">Phone</label> <input type="text" name="' + thisToken + '[phone]" value="' + addressData.phone + '"/>');
	if ($.inArray('phone', errorFields) > -1) phone.addClass('errorField');
	thisAddy.append(phone);
	// directions
	directions = $('<li>');
	directions.html('<label for="' + thisToken + '[directions]">Directions</label> <textarea name="' + thisToken + '[directions]">' + addressData.directions + '</textarea>');
	if ($.inArray('directions', errorFields) > -1) directions.addClass('errorField');
	thisAddy.append(directions);
	// delete
	del = $('<li>');
	del.html('<label for="' + thisToken + '[del]">Delete</label> <input type="checkbox" name="' + thisToken + '[del]" value="1"/>');
	$('input', del).change(function () {
		if ($(this).is(':checked')) $('#' + thisToken).addClass('inactive');
		else $('#' + thisToken).removeClass('inactive');
	});
	thisAddy.append(del);
	$('#addresses').append(thisAddy);
	if (newAddy & !fromNewAddress) $('#newAddyQty').val(newAddyID);
}

function editPerson (personID) {
	editPersonBox = $('#editPersonBox');
	editPersonBox.scrollTo(0);
	editPersonBox.dialog('option', 'title', (personID ? 'Edit' : 'New') + ' person');
	editPersonBox.removeClass('valid');
	editPersonBox.removeClass('invalid');
	$('#editPersonErrors').hide();
	$.ajax({'data': 'ajax=1&action=loadPerson&personID=' + escape(personID) + (document.getElementById('editPersonForm').parentID.value ? '&parentID=' + document.getElementById('editPersonForm').parentID.value : ''),
		'dataType': 'json',
		'type': 'get',
		'url': 'managePeople.php',
		'timeout': <?= (int) $config['ajaxTimeout'] ?>,
		'error': function (a, b, c) {
			alert('Could not load person\'s data cuzza ' + b);
		},
		'success': function (r) {
			if (r.status) {
				populateEditPersonForm(r);
				$('#customer, #supplier, #category, #depot, #private').change();
				if (r.cc) {
					$('#storedCC').show();
				} else {
					$('#storedCC').hide();
				}
				h = window.innerHeight - 50;
				editPersonBox.dialog('option', 'height', h);
				editPersonBox.dialog('open');
			}
		}
	});
}

function populateEditPersonForm (r) {
	clearFormFields('editPersonForm');
	console.log('cleared form fields');
	editPersonForm = document.getElementById('editPersonForm');
	$(editPersonForm.personID).val(r.personID);
	$(editPersonForm.contactName).val(r.contactName);
	$(editPersonForm.groupName).val(r.groupName);
	$(editPersonForm.customer).attr('checked', Boolean(r.personType & <?= P_CUSTOMER ?>));
	$(editPersonForm.supplier).attr('checked', Boolean(r.personType & <?= P_SUPPLIER ?>));
	$(editPersonForm.depot).attr('checked', Boolean(r.personType & <?= P_DEPOT ?>));
	$(editPersonForm.category).attr('checked', Boolean(r.personType & <?= P_CATEGORY ?>));
	$(editPersonForm.deliverer).attr('checked', Boolean(r.personType & <?= P_DELIVERER ?>));
	$(editPersonForm.private).attr('checked', Boolean(r.personType & <?= P_PRIVATE ?>));
	$(editPersonForm.csa).attr('checked', Boolean(r.personType & <?= P_CSA ?>));
	$(editPersonForm.member).attr('checked', Boolean(r.personType & <?= P_MEMBER ?>));
	$(editPersonForm.volunteer).attr('checked', Boolean(r.personType & <?= P_VOLUNTEER ?>));
	$(editPersonForm.phone).val(r.phone);
	$(editPersonForm.email).val(r.email);
	$(editPersonForm.privateKey).val(r.privateKey);
	if (r.payTypeID) $(editPersonForm.payTypeID).val(r.payTypeID);
	else $(editPersonForm.payTypeID).val('');
	if (r.personID == 1) {
		$(editPersonForm['payTypeIDs[inherit]']).attr('disabled', true);
		$('#payTypeIDInherit').hide();
		$('.payTypeIDs').attr('disabled', false);
	} else {
		$(editPersonForm['payTypeIDs[inherit]']).attr('disabled', false);
		$('#payTypeIDInherit').show();
	}
	<? $payTypes = getPayTypes(); ?>
	if (r.payTypeIDsParent != null) {
		<? foreach ($payTypes as $v) {
			if ($v->isActive()) { ?>
				if ($.inArray(<?= $v->payTypeID ?>, r.payTypeIDsParent) > -1) {
					$('#payTypeLabel<?= $v->payTypeID ?>').show();
					//$(editPersonForm['payTypeIDs[<?= $v->payTypeID ?>]']).attr('disabled', false);
				} else {
					$('#payTypeLabel<?= $v->payTypeID ?>').hide();
					//$(editPersonForm['payTypeIDs[<?= $v->payTypeID ?>]']).attr('disabled', true);
				}
			<? }
		} ?>
	}
	if (r.payTypeIDs != null) {
		<? foreach ($payTypes as $v) {
			if ($v->isActive()) { ?>
				$(editPersonForm['payTypeIDs[<?= $v->payTypeID ?>]']).attr('checked', $.inArray(<?= $v->payTypeID ?>, r.payTypeIDs) > -1);
			<? }
		} ?>
	} else {
		if (r.personID != 1) $(editPersonForm['payTypeIDs[inherit]']).attr('checked', true).change();
	}
	$(editPersonForm.minOrder).val(r.minOrder);
	$(editPersonForm.minOrderDeliver).val(r.minOrderDeliver);
	$(editPersonForm.bulkDiscount).val(r.bulkDiscount);
	$(editPersonForm.bulkDiscountQuantity).val(r.bulkDiscountQuantity);
	$(editPersonForm.maxStars).val(r.maxStars);
	$(editPersonForm.deposit).val(r.deposit);
	$(editPersonForm.depositParent).html((r.personID == 1) ? '' : 'default: ' + r.depositParent);
	$(editPersonForm.stars).val(r.stars);
	$(editPersonForm.recent.checked = r.recent);
	$(editPersonForm.notes).val(r.notes);
	$(editPersonForm.description).val(r.description);
	$(editPersonForm.website).val(r.website);
	$(editPersonForm.forgetCC).removeAttr('checked');
	if (r.cc) {
		$('#storedCCnum').html(r.cc);
		$('#storedCC').show().removeClass('ccDisabled');
		console.log('has cc');
	} else {
		$('#storedCC').hide().addClass('ccDisabled');
		$('storedCCnum').html('');
		$(editPersonForm.pad).removeAttr('checked');
		console.log('has no cc');
	}
	if (r.pad) $(editPersonForm.pad).attr('checked', 'checked');
	else $(editPersonForm.pad).removeAttr('checked');
	$(editPersonForm.credit).val(r.credit);
	$(editPersonForm.creditParent).html((r.personID == 1) ? '' : 'default: ' + r.creditParent);
	console.log('Populated fiddly bits');
	// editPersonForm.customCancelsRecurring.checked = r.customCancelsRecurring;
	/* if (r.personID == 1) $('#customCancelsRecurringI').attr('disabled', true);
	else {
		$('#customCancelsRecurringI').attr('disabled', false);
		$('#customCancelsRecurringParent').html('inherit (' + (r.customCancelsRecurringParent ? 'yes' : 'no') + ')');
	} */
	if (r.personID == 1) {
		$('#canCustomOrderI').attr('disabled', true);
		$('#payTypeIDParent').text('none');
	} else {
		$('#canCustomOrderI').attr('disabled', false);
		$('#canCustomOrderParent').html('inherit (' + (r.canCustomOrderParent ? 'yes' : 'no') + ')');
	}
	$(editPersonForm.canCustomOrder).each(function () {
		switch ($(this).val()) {
			case '1':
				if (r.canCustomOrder) $(this).attr('checked', true);
				break;
			case '0':
				if (!r.canCustomOrder && !(r.canCustomOrder === null)) $(this).attr('checked', true);
				break;
			case '-1':
				if (r.canCustomOrder === null) $(this).attr('checked', true);
		}
	});
	$('#addresses').html('');
	for (thisAddress in r.addresses) {
		thisAddress = r.addresses[thisAddress];
		addAddress (thisAddress);
	}
	$('#newAddyQty').val(0);
	// editPersonForm.compost.checked = result['compost'];
	editPersonForm.active.checked = r.active;
	$('#routeID').val(r.routeID);
	$('#dateCreated').html(r.dateCreated);
	console.log('done populating');
}

function validatePersonInfo (r) {
	populateEditPersonForm(r);
	editPersonForm.parentID.value = r.parentID;
	if (typeof r.newaddresses == 'object') {
		$('#newAddyQty').val(r.newaddresses.length);
		for (k in r.newaddresses) {
			thisAddress = r.newaddresses[k];
			thisAddress.addressID = k;
			addAddress (thisAddress, true);
		}
	}
	switch (r.status) {
		case -1:
			$('#editPersonErrors').html('You have some errors in your data. Please check the red boxes.');
			$('#editPersonErrors').show('fast');
			$('#editPersonBox').scrollTo(0);
			// document.getElementById('content').innerHTML = '<pre>' + resultOld + '</pre>';
			$(r.errorFields).each(function () {
				if (this == 'address') $('#editPersonErrors').append('<br/>This person must have at least one address. Please add an address below.');
				else if (this == 'addresses') $('#editPersonErrors').append('<br/>You have errors in some of your address details. Please review your addresses.')
				else $(editPersonForm[this]).addClass('errorField');
			});
			break;
		case 0:
			$('#editPersonErrors').html('A mysterious error has occured. Please try pressing \'save\' again.');
			$('#editPersonErrors').show('fast');
			$('#editPersonBox').scrollTo(0);
			break;
		case 1:
			$('#editPersonErrors').hide('fast');
			pref = 'n' + r.personID;
			$('#' + pref + '_l').text(r.label);
			try {
				orderButtons = $('#' + pref + '_o');
				if (r.active && r.personType & <?= P_CUSTOMER ?>) orderButtons.show();
				else orderButtons.hide();
			} catch (err) {
				alert(err.toString());
			}
			if (typeof toggleActive == 'function') toggleActive(r.activeStates, 'person');
			// document.getElementById(pref + '_a').innerHTML = result['address1'] + (result['address2'] ? ', ' + result['address2'] : '') + ', ' + result['city'] + ' ' + result['postalCode'];
			// document.getElementById(pref + '_e').innerHTML = result['email'];
			// document.getElementById(pref + '_p').innerHTML = result['phone'];
			// document.getElementById(pref + '_n').innerHTML = result['notes'];
			$('#editPersonBox').dialog('close');
			break;
		case 2:
			addNode(r.newRow, r.path, r.isActive, r.position);
			$('#editPersonBox').dialog('close');
	}
}

function loadOrders (personID) {
	$.ajax({'data': 'action=loadOrders&personID=' + escape(personID),
		'url': 'managePeople.php',
		'timeout': <?= (int) $config['ajaxTimeout'] ?>,
		'error': function () {
			console.log('failed to load orders for person');
		},
		'success': function (r) {
			$('#orderList').html(r);
			$('#orderList').dialog('open');
		},
		'type': 'POST'
	});
}

function deleteOrder (orderID) {
	$.ajax({'data': 'action=cancelOrder&ajax=1&orderID=' + Number(orderID),
		'url': 'order.php',
		'timeout': <?= (int) $config['ajaxTimeout'] ?>,
		'error': function () {
			console.log('failed to delete order ' + orderID);
		},
		'success': function (r) {
			if (Number(r)) {
				$('#order' + orderID).remove();
			} else {
				$('#orderStatus').html('Could not delete that order; it may be in use.');
			}
		},
		'type': 'POST'
	});
}

$(function () {
	$('#orderList').dialog({'autoOpen': false});
	$('#editPersonBox').dialog({'autoOpen': false, 'width': 700, 'height': 'auto'});
	$('#moveList').dialog({'autoOpen': false, 'width': 600, 'height': 'auto'});
	$('#emailBox').dialog({'autoOpen': false, 'width': 600, 'height': 'auto'});
	$('#customer, #supplier, #category, #depot, #private').change(function () {
		pType = this.id;
		pTypeClass = '.f' + pType.substr(0, 1).toUpperCase() + pType.substr(1);
		if ($(this).attr('checked')) {
			$(pTypeClass + ':not(.ccDisabled)').show();
		} else {
			$(pTypeClass).each(function () {
				hideThis = true;
				$($(this).attr('class').split(' ')).each (function () {
					pTypeOther = this.substr(1).toLowerCase();
					if ($('#' + pTypeOther).attr('checked')) hideThis = false;
				});
				if (hideThis) $(this).hide();
			});
		}
	});
	$('#editPersonForm input[name=\'payTypeIDs[inherit]\']').change(function () {
		if ($(this).attr('checked')) $('.payTypeIDs').attr('disabled', true).attr('checked', false);
		else $('.payTypeIDs').attr('disabled', false);
	});
	$('a.orderLink').click(function () {
		window.open(this.href, 'order');
		return false;
	});
	$('#editPersonForm').submit(function () {
		var inputs = {};
		$(':input', this).each(function () {
			if (this.name) {
				switch (this.type) {
					case 'checkbox':
					case 'radio':
						if (!this.checked) break;
					default:
						inputs[this.name] = this.value;
				}
			}
		});
		inputs['ajax'] = 1;
		$.ajax({
			'data': inputs,
			'url': this.getAttribute('action'),
			'timeout': <?= (int) $config['ajaxTimeout'] ?>,
			'dataType': 'json',
			'error': function () {
				console.log('failed to submit data');
			},
			'success': function (r) {
				validatePersonInfo(r);
			},
			'type': 'POST'
		});
		return false;
	});
});
