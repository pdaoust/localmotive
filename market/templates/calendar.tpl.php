<? if (!$embedded) { ?>
<h2>Calendar</h2>
<? }
echo generate_calendar($di['year'], $di['mon'], $days, 3, null, 0, array ('&larr;' => 'calendar.php?date=' . $prevMonth, '&rarr;' => 'calendar.php?date=' . $nextMonth));
?>
