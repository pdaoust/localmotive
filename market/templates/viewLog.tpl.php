<script type="text/javascript" language="JavaScript">
function toggleBlobSize (blobID) {
	if (blobObj = document.getElementById(blobID)) {
		switch (blobObj.className) {
			case 'truncBlob':
				blobObj.className = 'truncBlobExp';
				break;
			case 'truncBlobExp':
				blobObj.className = 'truncBlob';
		}
	}
}
</script>

<style type="text/css">
div#main { width: 900px; }
</style>

<h2>Log</h2><? } ?>
<form action="viewLog.php"><input type="hidden" name="style" value="<? echo $style; ?>"/>
<div>From
	<select name="dayStart">
		<?php
		for ($i = 1; $i <= 31; $i ++) {
			echo "\t\t\t<option value=\"$i\"" . ($i == $dayStart ? ' selected="selected"' : null) . ">$i</option>\n";
		} ?>
	</select>
	<select name="monthStart">
		<?php
		for ($i = 1; $i <= 12; $i ++) {
			echo "\t\t\t<option value=\"$i\"" . ($i == $monthStart ? ' selected="selected"' : null) . '>' . $monthNames[$i] . "</option>\n";
		} ?>
	</select>
	<select name="yearStart"><?php
		for ($i = 2008; $i <= strftime('%Y'); $i ++) {
			echo "\t\t\t<option value=\"$i\"" . ($i == $yearStart ? ' selected="selected"' : null) . ">$i</option>\n";
		}
	?></select>
	 to
	<select name="dayEnd">
		<?php
		for ($i = 1; $i <= 31; $i ++) {
			echo "\t\t\t<option value=\"$i\"" . ($i == $dayEnd ? ' selected="selected"' : null) . ">$i</option>\n";
		} ?>
	</select>
	<select name="monthEnd">
		<?php
		for ($i = 1; $i <= 12; $i ++) {
			echo "\t\t\t<option value=\"$i\"" . ($i == $monthEnd ? ' selected="selected"' : null) . '>' . $monthNames[$i] . "</option>\n";
		} ?>
	</select>
	<select name="yearEnd"><?php
		for ($i = 2008; $i <= strftime('%Y'); $i ++) {
			echo "\t\t\t<option value=\"$i\"" . ($i == $yearEnd ? ' selected="selected"' : null) . ">$i</option>\n";
		}
	?></select> <input type="submit" value="View"/>
</div>
</form>
<table class="listing">
	<tr class="even">
		<th class="odd">Date</th>
		<th class="even">Page</th>
		<th class="odd">Person ID</th>
		<th class="even">Function</th>
		<th class="odd">Error code</th>
		<th class="even">Message</th>
		<th class="odd">Vars</th>
	</tr>
<?php
$i = 0;
$thisRoot = $_SERVER['PHP_SELF'];
$thisRoot = substr($thisRoot, 0, -11);
$pathLen = strlen($thisRoot);
foreach ($logEntries as $thisEntry) {
	$i ++;
	echo "\t<tr class=\"" . ($i % 2 ? 'odd' : 'even') . "\">\n";
	echo "\t\t<td class=\"odd\">" . Date::human(strtotime($thisEntry->v('dateCreated'))) . "</td>\n";
	echo "\t\t<td class=\"even\">" . substr($thisEntry->v('page'), $pathLen) . "</td>\n";
	echo "\t\t<td class=\"odd\">" . $thisEntry->v('personID') . "</td>\n";
	echo "\t\t<td class=\"even\">" . $thisEntry->v('source') . " </td>\n";
	echo "\t\t<td class=\"odd\">" . (isset($errorCodes[$thisEntry->v('errorCode')]) ? $errorCodes[$thisEntry->v('errorCode')] : null) . "</td>\n";
	if (!$entryText = @unserialize($thisEntry->v('entryText'))) $entryText = $thisEntry->v('entryText');
	echo "\t\t<td class=\"even\"><div class=\"truncBlob\" id=\"et" . $thisEntry->v('logEntryID') . '" onclick="toggleBlobSize(\'et' . $thisEntry->v('logEntryID') . '\')"><pre>' . htmlEscape(is_array($entryText) ? print_r($entryText, true) : $entryText) . "</pre></div></td>\n";
	echo "\t\t<td class=\"odd\"><div class=\"truncBlob\" id=\"var" . $thisEntry->v('logEntryID') . '" onclick="toggleBlobSize(\'var' . $thisEntry->v('logEntryID') . '\')"><pre>' . print_r(unserialize($thisEntry->v('sessionVars')), true) . "\n" . print_r(unserialize($thisEntry->v('requestVars')), true) . "</pre></div></td>\n";
	echo "\t</tr>\n";
}
?>
</table>
