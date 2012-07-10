<h2>Edit person</h2>
<form id="editPersonForm" action="editAccount.php" method="POST"><input type="hidden" name="personID" value="<? echo $user->personID; ?>"/><input type="hidden" name="action" value="save"/>
	<table class="formLayout">
		<tbody>
			<tr>
				<th><label for="contactName">Name</label></th>
				<td><input type="text" name="contactName" value="<?= htmlEscape($user->contactName) ?>"<? if (in_array('contactName', $errorFields)) echo ' class="errorField"'; ?>/></td>
			</tr>
			<tr>
				<th><label for="groupName">Organisation</label></th>
				<td><input type="text" name="groupName" value="<?= htmlEscape($user->groupName) ?>"<? if (in_array('groupName', $errorFields)) echo ' class="errorField"'; ?>/></td>
			</tr>
			<tr>
				<th colspan="2">Addresses &nbsp; <a href="javascript:addAddress()"><img src="img/naddr.png" class="icon" alt="+"/> new</a> <input type="hidden" id="newAddyQty" value="<?= isset($newaddresses) ? count($newaddresses) : '0' ?>"/></th>
			</tr>
		</tbody>
		<tbody id="addresses">
		<tr>
			<th></th>
			<td></td>
		</tr>
		</tbody>
		<? if (!$depot) { ?>
		<tbody>
			<tr>
				<th>Route</th>
				<td>
					<select name="routeID">
						<option value=""> </option>
					<?php
					$routeID = $user->getRouteID(false);
					foreach ($routes as $thisRoute) {
						$schedule = $thisRoute->getSchedule();
						echo "\t\t\t\t\t<option value=\"" . $thisRoute->routeID . '"' . ($routeID == $thisRoute->routeID ? ' selected="selected"' : null) . '>' . $thisRoute->label . (count($schedule) ? ' (' . implode(', ', $schedule) . ')' : null) . "</option>\n";
					}
					?>
					</select>
					<p style="display: none;">You can find your closest route on <a href="javascript:spawnRouteMap()">our route map</a>. If you are not in any of the route outlines, just choose the nearest route and we'll try to accommodate you.</p>
				</td>
			</tr><? } else { ?>
			<tr>
				<th>Pick-up location</th>
				<td>
					<select name="depotID" id="depotID">
						<option value=""> </option>
						<?
						$depotID = (isset($_REQUEST['depotID']) ? (int) $_REQUEST['depotID'] : ($depot ? $depot->personID : null));
						foreach ($depots as $thisDepot) {
							foreach ($thisDepot->addresses as $thisAddy) {
								if ($thisAddy->addressType & AD_SHIP) echo "\t\t\t\t\t<option value=\"" . $thisDepot->personID . ':' . $thisAddy->addressID . '"' . ($thisDepot->personID == $depotID ? ' selected="selected"' : null) . '>' . ($thisAddy->careOf ? $thisAddy->careOf : $thisDepot->groupName) . ', ' . $thisAddy->address1 . ', '  . $thisAddy->city . "</option>\n";
							}
						}
						if (!isset($depotStatus)) $depotStatus = true;
					?></select>
				<div id="privateKeyLine" style="<?= ($depotStatus === E_INVALID_DATA || isset($privateKey)) ? null : 'display: none; ' ?>margin-top: 0.5em;">Secret key: <input type="text" name="privateKey" id="privateKey"<?= $depotStatus === E_INVALID_DATA ? ' class="errorField"' : null ?><?= isset($privateKey) ? ' value="' . $privateKey . '"' : null ?>/> <div id="privateKeyStatus" class="<?= $depotStatus === E_INVALID_DATA ? 'errorBox' : 'noticeBox' ?>" style="margin-top: 0.5em; margin-bottom: 0.5em;"><?= $depotStatus === E_INVALID_DATA ? 'Your secret key is not valid!' : 'This is a private depot and requires a secret key. If you do not have one, please contact the administrator of this depot.' ?></div></div></td>
			</tr><? } ?>
			<tr>
				<th>Phone</th>
				<td><input type="text" name="phone" value="<? echo $user->phone; ?>"<? if (in_array('phone', $errorFields)) echo ' class="errorField"'; ?>/></td>
			</tr>
			<tr>
				<th>E-mail</th>
				<td><input type="text" name="email" value="<? echo $user->email; ?>"<? if (in_array('email', $errorFields)) echo ' class="errorField"'; ?>/></td>
			</tr>
	<? if ($user->personType & P_SUPPLIER) { ?>
			<tr>
				<th>Description</th>
				<td><textarea rows="3" name="description"<? if (in_array('description', $errorFields)) echo ' class="errorField"'; ?>><? echo $user->description; ?></textarea></td>
			</tr>
			<tr>
				<th>Website</th>
				<td><input type="text" name="website" value="<? echo $user->website; ?>"<? if (in_array('website', $errorFields)) echo ' class="errorField"'; ?>/></td>
			</tr>
	<? } ?>
			<tr>
				<th>Payment type</th>
				<td>
					<select name="payTypeID">
						<option value=""> </option>
						<? foreach ($payTypes as $v) {
							echo "\t\t\t\t\t<option value=\"" . $v->payTypeID . '"' . ($v->isActive() ? null : ' disabled="disabled"') . '>' . $v->label . "</option>\n";
						} ?>
					</select>
				</td>
			</tr>
			<tr>
				<th>Options</th>
					<? if (!($user->personType & P_CSA)) { ?><input type="checkbox" name="customCancelsRecurring"<? if ($user->customCancelsRecurring) echo ' checked="checked"'; ?>/> when I place a custom order, cancel my recurring order for that week<? } ?>
			</tr>
			<tr>
				<th>Change password</th>
				<td>
					<input type="password" name="oldPassword" size="12"<? if ($passwordError == 2) echo ' class="errorField"'; ?>/> Old password<br/>
					<input type="password" name="newPassword1" size="12"<? if ($passwordError == 1) echo ' class="errorField"'; ?>/> New password<br/>
					<input type="password" name="newPassword2" size="12"<? if ($passwordError == 1) echo ' class="errorField"'; ?>/> New password (again)
				</td>
			</tr>
			<tr>
				<th> </th>
				<td><input type="submit" value="Save"/></td>
			</tr>
		</tbody>
	</table>
</form>
<script type="text/javascript" language="JavaScript">
$(function () {
	$('#depotID').change(function () {
		activatePrivateKey();
	});
	<? if ($depotStatus === E_INVALID_DATA || isset($privateKey)) { ?>
	activatePrivateKey();
	<? }
	foreach ($user->addresses as $v) {
		$thisAddy = (array) $v;
		$e = array ();
		if ($v->getError() == E_INVALID_DATA) $e['errorFields'] = $v->getErrorDetail();
		else $e['errorFields'] = array ();
		$thisAddy = array_merge($thisAddy, $e);
		?>
	addAddress(<?= json_encode($thisAddy) ?>);
	<? }
	if (isset($newaddresses)) {
		foreach ($newaddresses as $v) {
			$thisAddy = (array) $v;
			if ($v->getError() == E_INVALID_DATA) $thisAddy = array_merge($thisAddy, $v->getErrorDetail());
			?>
	addAddress(<?= json_encode($thisAddy) ?>, true);
	<? } } ?>
});

function activatePrivateKey () {
	if ($('#depotID option:selected').val()) {
		$.getJSON('editAccount.php', { 'action': 'hasPrivateKey', 'depotID': $('#depotID option:selected').val()}, function (json) {
			if (json.privateKey) {
				$('#privateKeyLine').show();
				$('#privateKey').keyup(function () {
					checkPrivateKey (json.privateKey);
				});
			} else $('#privateKeyLine').hide();
		});
	}
}

function checkPrivateKey (privateKey) {
	privateKeyE = $('#privateKey').val();
	if (!privateKeyE) {
		$('#privateKey').removeClass();
		$('#privateKeyStatus').html('This is a private depot and requires a secret key. If you do not have one, please contact the administrator of this depot.');
		$('#privateKeyStatus').removeClass();
		$('#privateKeyStatus').addClass('noticeBox');
	} else if (privateKeyE != privateKey.substr(0, privateKeyE.length)) {
		$('#privateKey').removeClass('okay');
		$('#privateKey').addClass('errorField');
		$('#privateKeyStatus').html('Your secret key is not valid!');
		$('#privateKeyStatus').removeClass();
		$('#privateKeyStatus').addClass('errorBox');
	} else {
		$('#privateKey').removeClass('errorField');
		$('#privateKey').addClass('okay');
		$('#privateKeyStatus').removeClass();
		$('#privateKeyStatus').addClass('okBox');
		if (privateKeyE.length < privateKey.length) $('#privateKeyStatus').html('So far, so good. Keep typing!');
		else $('#privateKeyStatus').html('Your secret key has been validated!');
	}
}

</script>
