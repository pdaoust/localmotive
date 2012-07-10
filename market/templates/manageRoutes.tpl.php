<script type="text/javascript" language="JavaScript">
$(function () {
	$('#dateStart').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true});
	$('#editDeliveryDayBox, #editRouteBox, #setRouteBox').dialog({'autoOpen': false, 'width': 400});
	$('#editDeliveryDayForm').ajaxForm({
		'data': {ajax: 1},
		'timeout': <?= (int) $config['ajaxTimeout'] ?>,
		'dataType': 'json',
		'error': function () {
			console.log('failed to submit data');
		},
		'success': function (r) {
			validateDeliveryDayInfo(r);
		},
		'type': 'POST'
	});
	$('#editRouteForm').ajaxForm({
		'data': {ajax: 1},
		'timeout': <?= (int) $config['ajaxTimeout'] ?>,
		'dataType': 'json',
		'error': function () {
			console.log('failed to submit data');
		},
		'success': function (r) {
			validateRouteInfo(r);
		},
		'type': 'POST'
	});
	$('#setRouteForm').ajaxForm({
		'data': {ajax: 1},
		'timeout': <?= (int) $config['ajaxTimeout'] ?>,
		'dataType': 'json',
		'error': function () {
			console.log('failed to submit data');
		},
		'success': function (r) {
			if (r.status) {
				$('#r' + r.routeID + ' > ul').append($('#p' + r.personID));
				$('#setRouteBox').dialog('close');
			}
		},
		'type': 'POST'
	});
	currentSlot = 0;
	$('ul.catList ul').sortable({
		axis: 'y',
		containment: 'parent',
		start: function (e, ui) {
			$(ui.item).addClass('selected');
			currentSlot = ui.item.prevAll().length + 1;
		},
		stop: function (e, ui) {
			$(ui.item).removeClass('selected');
			regex = /\d+/;
			nodeID = regex.exec($(ui.item).attr('id'))[0];
			newSlot = ui.item.prevAll().length + 1;
			nodeType = (ui.item.attr('id').substr(0, 1) == 'p' ? 'person' : 'route');
			if (nodeType == 'route') deliveryDayID = $(ui.item).parents('li').attr('id').replace(/[^\d]+/g, '');
			$.ajax({
				data: {
					ajax: 1,
					action: 'moveNode',
					nodeID: nodeID,
					newSlot: newSlot,
					nodeType: nodeType,
					deliveryDayID : ((nodeType == 'route') ? deliveryDayID : null)
				},
				url: 'manageRoutes.php',
				timeout: <?= (int) $config['ajaxTimeout'] ?>,
				dataType: 'json',
				error: function () {
					ui.item.parent().sortable('cancel');
					console.log('failed to submit data');
				},
				success: function (r) {
					if (!r.status) {
						ui.item.parent().sortable('cancel');
					}
				},
				type: 'POST'
			});
		},
		tolerance: 'pointer',
		cursor: 'move'
	});
	$('.addRouteForm').live('submit', function () {
		$(this).ajaxSubmit({
			data: { ajax: 1 },
			dataType: 'json',
			type: 'post',
			url: 'manageRoutes.php',
			timeout: <?= (int) $config['ajaxTimeout'] ?>,
			error: function () {
				alert('Could not add route');
			},
			success: function (r) {
				if (r.status) {
					ddRow = $('#dd' + r.deliveryDayID);
					routeRow = $('<li>');
					routeRow.addClass('r' + r.routeID + (r.active ? '' : ' inactive')).attr('id', 'r' + r.routeID + '.d' + r.deliveryDayID)
						.html('<a href="javascript:removeRoute(' + r.routeID + ',' + r.deliveryDayID + ')" class="del"><img src="img/del.png" class="icon" alt="remove"/></a> <a class="label rl' + r.routeID + '" href="#r' + r.routeID + '">' + htmlEscape(r.label) + '</a>');
					$('ul', ddRow).append(routeRow);
					$('.rs' + r.routeID, ddRow).attr('disabled', true);
				}
			}
		});
		return false;
	});
});

// Initialize upon load to let all browsers establish content objects
var deliveryDayIDs = Array (<?= implode(', ', array_keys($deliveryDays)) ?>);

function deleteDeliveryDay (deliveryDayID) {
	if (confirm('Are you sure you want to delete this delivery day? All routes in this day will be kept, but removed from the delivery schedule.')) {
		$.ajax({
			data: {
				ajax: 1,
				action: 'deleteDeliveryDay',
				deliveryDayID: deliveryDayID
			},
			dataType: 'json',
			type: 'post',
			url: 'manageRoutes.php',
			timeout: <?= (int) $config['ajaxTimeout'] ?>,
			error: function () {
				alert('Could not delete delivery day');
			},
			success: function (r) {
				if (r.status) {
					$('#dd' + deliveryDayID).fadeOut('fast', function () { $(this).remove(); });
				}
			}
		});
	}
}

function editDeliveryDay (deliveryDayID) {
	clearFormFields('editDeliveryDayForm');
	editDeliveryDayBox = $('#editDeliveryDayBox');
	editDeliveryDayBox
		.dialog('option', 'title', (deliveryDayID ? 'Edit' : 'New') + ' delivery day')
		.removeClass('valid')
		.removeClass('invalid');
	$('#editDeliveryDayErrors').hide();
	$.ajax({
		data: {
			ajax: 1,
			action: 'loadDeliveryDay',
			deliveryDayID: deliveryDayID
		},
		dataType: 'json',
		type: 'post',
		url: 'manageRoutes.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			alert('Could not load delivery day\'s data');
		},
		success: function (r) {
			if (r.status) {
				populateEditDeliveryDayForm(r);
				h = window.innerHeight - 50;
				$('#editDeliveryDayBox').dialog('option', 'height', h);
				editDeliveryDayBox.dialog('open');
			}
		}
	});
}

function populateEditDeliveryDayForm (r) {
	editDeliveryDayForm = document.getElementById('editDeliveryDayForm');
	editDeliveryDayForm.deliveryDayID.value = r.deliveryDayID;
	editDeliveryDayForm.dateStart.value = (r.deliveryDayID ? r.dateStart : null);
	/* newDate = new Date(r.dateStartJ.year, r.dateStartJ.month, r.dateStartJ.day);
	// $('#dateStart').datepicker('option', 'defaultDate', newDate); */
	editDeliveryDayForm.period.value = r.period;
	editDeliveryDayForm.mult.selectedIndex = r.mult;
	editDeliveryDayForm.label.value = r.label;
	editDeliveryDayForm.cutoffDay.value = r.cutoffDay;
	editDeliveryDayForm.active.checked = r.active;
}

function validateDeliveryDayInfo (r) {
	populateEditDeliveryDayForm(r);
	switch (r.status) {
		case -1:
			$('#editDeliveryDayErrors')
				.text('You have some errors in your data. Please check the red boxes.')
				.show('fast');
			$('#editDeliveryDayBox')
				.removeClass('valid')
				.addClass('invalid')
				.scrollTo(0);
			for (i = 0; i < r.errorFields.length; i ++) {
				$(editDeliveryDayForm[r.errorFields[i]]).addClass('invalid');
			}
			break;
		case 0:
			$('#editDeliveryDayErrors').text('An error has occured. Please try pressing \'save\' again.');
			break;
		case 1:
			$('.ddl' + r.deliveryDayID).text(r.label);
			if (!r.active) $('#dd' + r.deliveryDayID).addClass('inactive');
			else $('#dd' + r.deliveryDayID).removeClass('inactive');
			$('#editDeliveryDayBox').dialog('close');
			break;
		case 2:
			newDD = $('<li>');
			newDD.attr('id', 'dd' + r.deliveryDayID);
			newDD.html('<a href="javascript:deleteDeliveryDay(' + r.deliveryDayID + ')"><img src="img/del.png" class="icon" alt="delete"/></a> <a class="label ddl' + r.deliveryDayID + '" href="javascript:editDeliveryDay(' + r.deliveryDayID + ')">' + htmlEscape(r.label) + '</a><ul></ul>');
			stockAddRoute = $('.addRouteForm:first').clone();
			$('option', stockAddRoute).each(function () {
				$(this).removeAttr('disabled');
			});
			$('input[name=\'deliveryDayID\']', stockAddRoute).val(r.deliveryDayID);
			newDD.append(stockAddRoute);
			$('#deliveryDayList').append(newDD);
			$('#editDeliveryDayBox').dialog('close');
			newDD.css({'opacity': '0'});
			$('html, body').animate({
			    scrollTop: newDD.offset().top
			}, 1000, function () {
				newDD.animate({'opacity': '1'}, 'fast');
			});
	}
}

function editRoute (routeID) {
	clearFormFields('editRouteForm');
	editRouteBox = $('#editRouteBox');
	editRouteBox
		.dialog('option', 'title', (routeID ? 'Edit' : 'New') + ' route')
		.removeClass('valid invalid');
	$('#editRouteErrors').hide();
	$.ajax({
		data: {
			ajax: 1,
			action: 'loadRoute',
			routeID: routeID
		},
		dataType: 'json',
		type: 'post',
		url: 'manageRoutes.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			alert('Could not load route\'s data');
		},
		'success': function (r) {
			if (r.status) {
				populateEditRouteForm(r);
				h = window.innerHeight - 50;
				editRouteBox.dialog('option', 'height', h).dialog('open');
			}
		}
	});
}

function populateEditRouteForm (r) {
	clearFormFields('editRouteForm');
	editRouteForm = document.getElementById('editRouteForm');
	editRouteForm.routeID.value = r.routeID;
	editRouteForm.label.value = r.label;
	editRouteForm.active.checked = r.active;
}

function validateRouteInfo (r) {
	editRouteForm = document.getElementById('editRouteForm');
	editRouteForm.routeID.value = r.routeID;
	editRouteForm.label.value = r.label;
	editRouteForm.active.checked = r.active;
	switch (r.status) {
		case -1:
			$('#editRouteErrors').text('You have some errors in your data. Please check the red boxes.');
			for (i = 0; i < r.errorFields.length; i ++) {
				$(editRouteForm[r.errorFields[i]]).addClass('invalid');
			}
			break;
		case 0:
			$('#editRouteErrors').text('An error has occured. Please try pressing \'save\' again.');
			break;
		case 1:
			$('.rl' + r.routeID).text(r.label);
			if (r.active) $('.r' + r.routeID).removeClass('inactive');
			else $('.r' + r.routeID).addClass('inactive');
			$('#editRouteBox').dialog('close');
			break;
		case 2:
			newRouteRow = $('<option>');
			newRouteRow
				.attr('value', r.routeID)
				.text(r.label)
				.addClass('rl' + r.routeID)
				.addClass('rs' + r.routeID);
			$('select.addRoute').append(newRouteRow);
			newRouteCat = $('<li>');
			newRouteCat
				.attr('id', 'r' + r.routeID)
				.addClass('r' + r.routeID + (r.active ? '' : ' inactive'))
				.html('<a href="javascript:deleteRoute(' + r.routeID + ')"><img src="img/del.png" class="icon" alt="delete"/></a> <a href="javascript:editRoute(' + r.routeID + ')" class="label rl' + r.routeID + '">' + htmlEscape(r.label) + '</a><ul></ul>')
				.css({'opacity': '0'})
				.insertBefore('#r0');
			$('html, body').animate({
			    scrollTop: newRouteCat.offset().top
			}, 1000, function () {
				newRouteCat.animate({'opacity': '1'}, 'fast');
			});
			$('#editRouteBox').dialog('close');
	}
}

function deleteRoute (routeID) {
	if (confirm('Are you sure you want to delete this route? All people in this route will be kept, but removed from the delivery schedule.')) {
		$.ajax({
			data: {
				ajax: 1,
				action: 'deleteRoute',
				routeID: routeID
			},
			dataType: 'json',
			type: 'post',
			url: 'manageRoutes.php',
			timeout: <?= (int) $config['ajaxTimeout'] ?>,
			error: function () {
				alert('Could not delete route');
			},
			success: function (r) {
				if (r.status) {
					$('.rs' + routeID).remove();
					$('#r0 ul').append($('#r' + routeID + ' ul li'));
					$('#r' + routeID).fadeOut('fast', function () {
						$(this).remove();
						$('.r' + routeID).remove();
					});
				}
			}
		});
	}
}

function removeRoute (routeID, deliveryDayID) {
	$.ajax({
		data: {
			ajax: 1,
			action: 'removeRoute',
			routeID: routeID,
			deliveryDayID: deliveryDayID
		},
		dataType: 'json',
		type: 'post',
		url: 'manageRoutes.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			alert('Could not remove route');
		},
		success: function (r) {
			if (r.status) {
				ddRow = $('#dd' + deliveryDayID);
				$('.r' + routeID, ddRow).remove();
				$('.rs' + routeID, ddRow).attr('disabled', false);
			}
		}
	});
}


function removePerson (personID) {
	personID = Number(personID);
	if (personID) {
		$.ajax({
			data: {
				ajax: 1,
				action: 'removePerson',
				personID: personID
			},
			dataType: 'json',
			type: 'post',
			url: 'manageRoutes.php',
			timeout: <?= (int) $config['ajaxTimeout'] ?>,
			error: function () {
				alert('Could not remove person');
			},
			success: function (r) {
				if (r.status) {
					// TODO: does it do anything if it fails? Should it?
					$('#r0 > ul').append($('#p' + Number(personID)));
				}
			}
		});
	}
}

function setRoute (personID, routeID) {
	setRouteBox = $('#setRouteBox');
	personRow = $('#p' + personID);
	setRouteBox.dialog('option', 'title', 'Move ' + $('.label', personRow).html());
	setRouteForm = $('#setRouteForm');
	regex = /\d+/;
	routeID = regex.exec(personRow.parents('li').attr('id'))[0];
	$('input[name=personID]', setRouteForm).val(personID);
	$('option', setRouteForm).each(function () {
		$(this).attr('disabled', $(this).val() == routeID);
	});
	setRouteBox.dialog('option', 'height', 100);
	setRouteBox.dialog('open');
}

$(function () {
	$('#dateStart').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true});
});
</script>

<h2><img src="img/rt_lg.png" class="iconLg" alt=""/> Manage Routes</h2>
<h3>Delivery days <a href="javascript:editDeliveryDay(0)" class="button small">+</a></h3>
<ul class="catList" id="deliveryDayList">
	<? foreach ($deliveryDays as $v) { ?>
	<li id="dd<?= $v->deliveryDayID ?>" <?= $v->active ? null : ' class="inactive"' ?>>
		<a href="javascript:deleteDeliveryDay(<?= $v->deliveryDayID ?>)"><img src="img/del.png" class="icon" alt="delete"/></a>
		<a class="label ddl<?= $v->deliveryDayID ?>" href="javascript:editDeliveryDay(<?= $v->deliveryDayID ?>)"><?= htmlEscape($v->label) ?></a>
		<ul>
		<?
		$routeIDs = $v->getRouteIDs();
		if (count($routeIDs)) {
			foreach ($routeIDs as $v2) {
				$v2 = $routes[$v2]; ?>
				<li class="r<?= $v2->routeID . ($v2->active ? null : ' inactive') . '" id="r' . $v2->routeID . '.d' . $v->deliveryDayID ?>">
				<a href="javascript:removeRoute(<?= $v2->routeID . ', ' . $v->deliveryDayID ?>)" class="del"><img src="img/del.png" class="icon" alt="remove"/></a>
				<a class="label rl<?= $v2->routeID ?>" href="#r<?= $v2->routeID ?>"><?= htmlEscape($v2->label) ?></a>
				</li><?
			}
		} ?>
		</ul>
		<form class="addRouteForm" action="manageRoutes.php" method="POST">
			<input type="hidden" name="deliveryDayID" value="<?= $v->deliveryDayID ?>"/>
			<input type="hidden" name="action" value="addRoute"/>
			<select name="routeID" class="addRoute">
				<option value="0">Add a route...</option><?
				foreach ($routes as $v2) {
					if ($v2->routeID) { ?>
						<option value="<?= $v2->routeID ?>" class="rl<?= $v2->routeID ?> rs<?= $v2->routeID ?>"<?= in_array($v2->routeID, $routeIDs) ? ' disabled="disabled"' : null ?>><?= htmlEscape($v2->label) ?></option><?
					}
				} ?>
			</select><input type="submit" value="+" class="small"/>
		</form>
	</li>
	<? } ?>
</ul>



<h3>Routes <a href="javascript:editRoute(0)" class="button small">+</a></h3>
<ul class="catList" id="routeList">
<?php
$routes2 = $routes;
foreach ($routes as $v) { ?>
	<li class="r<?= $v->routeID . ($v->active ? null : ' inactive') ?>"" id="r<?= (int) $v->routeID ?>">
		<? if ($v->routeID) { ?><a href="javascript:deleteRoute(<?= $v->routeID ?>)"><img src="img/del.png" class="icon" alt="delete"/></a><? } ?>
		 <<?= $v->routeID ? 'a href="javascript:editRoute(' . $v->routeID . ')"' : 'span' ?> class="label rl<?= $v->routeID ?>"><?= ($v->routeID ? htmlEscape($v->label) : 'Not in a route') ?></<?= $v->routeID ? 'a' : 'span' ?>>
		<ul>
		<? if (count($people[$v->routeID])) {
			foreach ($people[$v->routeID] as $v2) {
				if ($addresses = $v2->getAddresses(AD_SHIP)) { ?>
					<li id="p<?= $v2->personID ?>">
						<a href="javascript:removePerson(<?= $v2->personID ?>)" class="del"><img src="img/del.png" class="icon" alt="remove"/></a>
						<a href="javascript:setRoute(<?= $v2->personID ?>)"><img src="img/mv.png" class="icon" alt="move"></a>
						<span class="label"><?= htmlEscape($v2->getLabel()) ?></span>
						<? if ($thisAddress = current($addresses)) {
							echo ' &middot; ' . htmlEscape($thisAddress->address1) . ($thisAddress->address2 ? ', ' . htmlEscape($thisAddress->address2) : null) . ($thisAddress->city ? ', ' . htmlEscape($thisAddress->city) : null);
						} ?>
					</li><?
				}
			}
		} ?>
		</ul>
	</li>
<? } ?>
</ul>

<div id="editDeliveryDayBox" style="display: none;">
	<p class="errorBox" id="editDeliveryDayErrors" style="display: none;"></p>
	<form id="editDeliveryDayForm" action="manageRoutes.php" method="POST"><input type="hidden" name="deliveryDayID"/><input type="hidden" name="action" value="editDeliveryDay"/>
		<ul class="form">
			<li>
				<label for="label">Delivery day name</label>
				<input type="text" name="label"/>
			</li>
			<li>
				<label for="dateStart">Starts on</label>
				<input type="text" name="dateStart" id="dateStart"/>
			</li>
			<li>
				<label for="mult">Frequency</label>
				<span class="widget">
					<label>Once every <input type="text" name="period" size="3" maxlength="5"/></label>
					<select name="mult">
						<option value="<? echo T_DAY; ?>">day(s)</option>
						<option value="<? echo T_WEEK; ?>">week(s)</option>
						<option value="<? echo T_MONTH; ?>">month(s)</option>
						<option value="<? echo T_YEAR; ?>">year(s)</option>
					</select>
				</span>
			</li>
			<li>
				<label for="cutoffDay">Cutoff day</label>
				<span class="widget"><input type="text" name="cutoffDay" size="1"/> day(s) before</span>
			</li>
			<li>
				<label for="active">Active</label>
				<input type="checkbox" name="active"/>
			</li>
			<li>
				<span class="label">&nbsp;</span>
				<input type="submit" value="Save"/>
			</li>
		</table>
	</form>
</div>

<div id="editRouteBox" class="popup" style="display: none;">
	<p class="errorBox" id="editRouteErrors" style="display: none;"></p>
	<form id="editRouteForm" action="manageRoutes.php" method="POST"><input type="hidden" name="routeID"/><input type="hidden" name="action" value="editRoute"/>
		<table class="formLayout">
			<tr>
				<th><label for="label">Route name</label></th>
				<td><input type="text" name="label"/></td>
			</tr>
			<tr>
				<th>Options</th>
				<td><label><input type="checkbox" name="active"/> active</label></td>
			</tr>
			<tr>
				<th> </th>
				<td><input type="submit" value="Save"/></td>
			</tr>
		</table>
	</form>
</div>

<div id="setRouteBox" class="popup" style="display: none;">
	<form id="setRouteForm" action="manageRoutes.php" method="POST"><input type="hidden" name="action" value="setRoute"/><input type="hidden" name="personID"/>
		<select name="routeID" class="addRoute">
			<option value="">Move to route...</option>
			<? foreach ($routes as $v) {
				if ($v->routeID) {?>
					<option value="<?= $v->routeID ?>" class="rl<?= $v->routeID . ' rs' . $v->routeID ?>"><?= htmlEscape($v->label) ?></option>
				<? }
			} ?>
		</select> <input type="submit" value="move"/>
	</form>
</div>
