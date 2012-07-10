<script src="templates/bsn.AutoSuggest.js" type="text/javascript"></script>
<script type="text/javascript" language="JavaScript">
var xmlHttp;

function loadCalendar (timestamp) {
	if (Number(timestamp)) {
		xmlHttp = getXmlHttpObject ();
		xmlHttp.onreadystatechange = changeCalendar;
		xmlHttp.open('GET', 'calendar.php?embedded&date=' + timestamp, true);
		xmlHttp.send(null);
	}
}

function changeCalendar () {
	if (xmlHttp.readyState == 4) {
		result = xmlHttp.responseText;
		document.getElementById('bigCal').innerHTML = result;
	}
}

function getItem () {
	return true;
}

</script>

<h2>Administration area</h2>

<? if (!$user->isLeafNode() || $user->personID == 1) { ?><h3>People and Orders</h3>
<p><a href="managePeople.php"><img src="img/per.png" alt="Manage people, groups, and depots" class="icon"/> People, groups, and depots</a></p>
<form action="managePeople.php" method="get"><p><img src="img/per.png" alt="Manage one account" class="icon"/> Search <input type="text" id="personText" class="search"/><input type="hidden" name="nodeID" id="managePersonID"/> <input type="submit" value="Go"/></p></form>
<p><a href="manageRoutes.php"><img src="img/rt.png" alt="Manage routes" class="icon"/> Schedule and routes</a></p>
<p><a href="manageAccounts.php"><img src="img/act.png" alt="Accounting" class="icon"/> Accounting</a></p>
<p><a href="orderHistory.php?recursive=1"><img src="img/ordh.png" alt="Order history" class="icon"/> Order history</a></p>

<h3>Inventory</h3>
<p><a href="manageInventory.php"><img src="img/itm.png" alt="Manage the inventory" class="icon"/> Inventory</a></p>
<form action="manageInventory.php" method="get" id="editItemForm"><p><img src="img/itm.png" alt="Manage one item" class="icon"/> Search <input type="text" id="itemText" class="search"/><input type="hidden" name="nodeID" id="manageItemID"/> <input type="submit" value="Go"/></p></form>

<h3>Schedule</h3>
<p><a href="calculateStars.php"><img src="img/new.png" alt="Calculate stars" class="icon"/> Calculate stars for this week
<? if (isset($config['lastStarCalcDate'])) {
	$starsDaysAgo = floor((time() - (int) $config['lastStarCalcDate']) / T_DAY);
	echo ($starsDaysAgo < 6 ? '<span class="notice">' : null) . '(last done ' . $starsDaysAgo . ' day' . ($starsDaysAgo != 1 ? 's' : null) . ' ago)' . ($starsDaysAgo < 6 ? '</span>' : null);
} ?></a></p>
<form action="createRecurringOrders.php" method="GET" id="createRecurringOrders"><p><img src="img/std.png" alt="Create recurring orders" class="icon"/> Create recurring orders for week of <input type="text" name="dateStd" id="dateStd"/> <input type="submit" value="Go"/> <img src="img/load.gif" alt="Processing orders..." title="Processing orders..." style="display:none" id="recurringLoad"/></p></form>
<div id="bigCal" style="margin-top: 1em;">
<? $embedded = true;
include ('calendar.php');
?></div>

<h3>Other stuff</h3>
<!--<p><a href="../admin.php"><img src="img/nday.png" alt="Edit calendar" class="icon"/> Edit calendar and announcements</a></p>-->
<p><a href="backup.php"><img src="img/db.png" alt="Download backup" class="icon"/> Download a backup of the database
<? if (isset($config['lastBackupDate'])) {
	$backupDaysAgo = floor((time() - (int) $config['lastBackupDate']) / T_DAY);
	echo ($backupDaysAgo > 7 ? '<span class="notice">' : null) . '(last done ' . $backupDaysAgo . ' day' . ($backupDaysAgo != 1 ? 's' : null) . ' ago)' . ($backupDaysAgo > 7 ? '</span>' : null);
} ?></a></p>
<!--<p><a href="viewLog.php"><img src="img/n.png" alt="View logs" class="icon"/> View the error and access log</a></p>
<p><a href="checkBalances.php"><img src="img/y.png" alt="Check balances" class="icon"/> Check integrity of balances</a></p>-->
<? } ?>

<!--</div>-->
<script type="text/javascript" language="JavaScript">
<?php
$nextDeliveryDay = strftime('%Y-%m-%d', getNextDeliveryDay(null, false));
$deliveryDays = getDeliveryDays(true);
$daysOff = array ();
for ($i = 0; $i < 7; $i ++) {
	if (!in_array($i, $deliveryDays)) $daysOff[] = ($i ? $i : 7);
}
$daysOff = implode(', ', $daysOff);
?>

$(function () {
	$('#dateStd').datepicker({dateFormat: '<?= TF_PICKER_JQ ?>', gotoCurrent: true, minDate: new Date()});
	$('#personText').autocomplete({
		'source': 'getPersonID.php?parentID=<?= $user->personID ?>',
		'minLength': 3,
		'select': function (e, u) {
			$('#managePersonID').val(u.item.id);
			return true;
		}
	});
	$('#itemText').autocomplete({
		'source': 'getItemID.php?parentID=1',
		'minLength': 3,
		'select': function (e, u) {
			$('#manageItemID').val(u.item.id);
			return true;
		}
	});
	$('#createRecurringOrders').submit(function () {
		$(this).find('input[type=submit]').attr('disabled', true);
		$('#recurringLoad').show();
	});
	$('.calendar-prev a, .calendar-next a').live('click', function () {
		calendarHref = $(this).attr('href')+'&ajax';
		$.ajax({
			'url': calendarHref,
			'dataType': 'html',
			'success': function (r) {
				$('#bigCal').html(r);
			}
		});
		return false;
	});
});
</script>
<div id="caldiv" style="position:absolute; visibility:hidden; margin: 0; padding: 0;"></div>
