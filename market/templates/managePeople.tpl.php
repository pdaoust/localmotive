<script type="text/javascript" language="JavaScript">

$(function () {
	/*$('#customer').change(function () {
		if (this.checked) {
			if (!confirm ('If this person\'s service has a yearly sign-up fee, it will now be applied to their account. Do you still want to activate this person as a customer?')) this.checked = false;
		} else {
			if (!confirm ('If this person\'s service has a yearly membership fee, the scheduled billing for that fee will be canceled. This is fine, unless you re-activate this person as a customer within the year, in which case they will be billed twice for the fee. Do you still want to deactivate this person as a customer?')) this.checked = true;
		}
	});*/

	$('#email').change(function () {
		email = $(this).val();
		$.getJSON('signup.php', { ajax: 1, action: 'checkDuplicateEmail', email: email }, function (json) {
			if (json.duplicate) {
				$('#emailDuplicate').show();
				$('#email').addClass('errorField');
			} else {
				$('#emailDuplicate').hide();
				$('#email').removeClass('errorField');
			}
		});
	});

	$('#emailSubject').keyup(function () {
		if ($(this).val()) $(this).removeClass('invalid');
	});

	$('#emailMessage').keyup(function () {
		if ($(this).val()) $(this).removeClass('invalid');
	});

	$('#emailForm').ajaxForm({
		data: {
			ajax: 1,
			children: Boolean($('#emailChildren').is(':checked'))
		},
		timeout: <?= (int) $config['ajaxTimeout'] * 5 ?>,
		dataType: 'json',
		error: function (e, ts, et) {
			clearFormFields('emailForm');
			$('#emailStatus').text('A network error occurred. Please try sending again.')
				.removeClass('noticeBox okBox')
				.addClass('errorBox')
				.show();
		},
		success: function (r) {
			if (r.error) {
				$('#emailStatus').html(r.errorMsg)
					.removeClass('noticeBox okBox')
					.addClass('errorBox');
				clearFormFields('emailForm');
				$(r.errorFields).each(function () {
					$('#' + this).addClass('invalid');
				});
			} else {
				$('#emailStatus').removeClass('noticeBox okBox errorBox');
				if (r.emails == 1 && !r.failedEmails) {
					$('#emailStatus').text('The message was sent.').addClass('okBox');
				} else if (r.emails) {
					$('#emailStatus').text(r.emails + ' messages were sent' + (r.failedEmails ? ', and ' + r.failedEmails + ' could not be sent (please check your server settings)' : '') + '.');
					if (r.failedEmails) $('#emailStatus').addClass('noticeBox');
				} else if (r.failedEmails) {
					$('#emailStatus')
						.text((r.failedEmails == 1 ? 'The message could not be sent.' : 'Sending to all ' + r.failedEmails + ' recipients failed.') + ' Please check your server settings.')
						.addClass('errorBox');
				} else {
					$('#emailStatus').text('There were no people to send this message to.').addClass('errorBox');
				}
			}
			$('#emailStatus').show();
			setTimeout(function () {
				$('#emailForm').dialog('close');
			}, 2000);
		}
	});
});

function editNode (personID) {
	return editPerson(personID);
}

function newNode (parentID) {
	document.getElementById('editPersonForm').parentID.value = parentID;
	editNode(0);
}

function deleteNode (personID, personType) {
	if (confirm('Are you sure you want to delete this ' + personType + '? (Note: if this account has journal entries or orders associated with it, it will be deactivated instead.)')) {
		$.ajax({
			data: {
				ajax: 1,
				action: 'deletePerson',
				personID: personID
			},
			action: 'managePeople.php',
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

function setParent (personID) {
	$.ajax({
		data: {
			ajax: 1,
			action: 'loadMoveTree',
			personID: personID
		},
		url: 'managePeople.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		error: function () {
			console.log('failed to load the moving dialogue box');
		},
		success: function (r) {
			$('#moveList').html(r).dialog('open');
		},
		'type': 'POST'
	});
}

function email (personID) {
	$.ajax({
		data: {
			ajax: 1,
			action: 'loadPerson',
			personID: personID
		},
		url: 'managePeople.php',
		timeout: <?= (int) $config['ajaxTimeout'] ?>,
		dataType: 'json',
		type: 'get',
		error: function () {
			console.log('could not load person');
			alert('This person does not exist.');
		},
		success: function (r) {
			if (r.personID) {
				$('#emailPersonID').val(r.personID);
				$('#emailTo').text(r.contactName  + (r.groupName ? ' (' + r.groupName + ')' : '') + (r.email ? ' <' + r.email + '>' : ''));
				emailChildren = $('#emailChildren');
				if (!r.isLeafNode) {
					emailChildren.attr('disabled', false).attr('checked', true);
					$('.emailChildren').each(function () {
						$(this).show();
					});
				} else {
					emailChildren.attr('disabled', true).attr('checked', false);
					$('.emailChildren').each(function () {
						$(this).hide();
					});
				}
				$('#emailStatus').hide();
				$('#emailSubject').val('');
				$('#emailMessage').val('');
				clearFormFields('emailForm');
				$('#emailBox').dialog('open');
			} else alert ('This person could not be loaded.');
		}
	});
}

var routes = new Array ();
<?php
$i = 0;
foreach ($routes as $thisRoute) {
	$thisRouteID = (int) $thisRoute->routeID;
	echo 'routes[' . $thisRouteID . "] = { 'routeID': " . $thisRouteID . ", 'routeIndex': " . $i . ", 'label': '" . htmlEscape($thisRoute->label) . "', 'active': " . ($thisRoute->active ? 'true' : 'false' ) . " };\n";
	echo 'routes[' . $thisRouteID . "]['days'] = new Array ();\n";
	foreach ($thisRoute->routeDays as $thisRouteDay) {
		echo 'routes[' . $thisRouteID . "]['days'][" . (int) $thisRouteDay->deliveryDayID . '] = ' . $thisRouteDay->deliverySlot . ";\n";
	}
	$i ++;
} ?>

</script>

<h2><img src="img/per_lg.png" class="iconLg" alt="" /> Manage People</h2>
<?php
$nodeType = 'person';
$tree = &$people;
$thisPage = 'managePeople.php';
$showMoveControls = false;
include('treeview.tpl.php');

include('editPersonBox.tpl.php');
?>
<div id="orderList" title="View orders"></div>
<div id="moveList" title="Move person to"></div>
<div id="emailBox" title="Send an e-mail" style="display: none;">
	<div class="noticeBox" id="emailStatus" style="display: none;"></div>
	<form id="emailForm" action="email.php" method="post"><input type="hidden" name="personID" id="emailPersonID"/><input type="hidden" name="ajax" value="1"/>
		<ul class="form nohints">
			<li>
				<span class="label">To</span>
				<span class="widget">
					<span id="emailTo"></span> <input type="checkbox" checked="checked" name="children" id="emailChildren" class="emailChildren"/> <label for="children" class="emailChildren noform">and all members</label>
				</span>
			</li>
			<li>
				<label for="subject">Subject</label>
				<input type="text" size="50" name="subject" id="emailSubject"/>
			</li>
			<li>
				<label for="message">Message</label>
				<textarea rows="15" cols="60" name="message" id="emailMessage"></textarea>
			</li>
			<li>
				<span class="label">&nbsp;</span>
				<input type="submit" value="Send" />
			</li>
		</ul>
	</form>
</div>
