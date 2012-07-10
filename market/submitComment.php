<?php
require_once ('marketInit.inc.php');
require_once ($path . '/market/classes/person.inc.php');
$person = new Person ((int) $_REQUEST['personID']);
$comments = 'Page: ' . $_REQUEST['page'] . "\n";
$comments .= 'Browser: ' . $SERVER['HTTP_USER_AGENT'] . "\n";
if ($person->personID) {
	$comments .= 'Person: ' . $person->getLabel() . ' (personID ' . $person->personID . ")\n";
	$comments .= 'E-mail: ' . $person->email . "\n";
}
$comments .= "\n" . stripslashes(wordwrap($_REQUEST['comments']));
if (@mail(($_REQUEST['nature'] == 'service' ? $config['email'] : $config['webmaster']), 'Comment on the website' . ($person->personID ? ' from ' . $person->getLabel() : null), $comments, 'From: ' . ($person->personID ? $person->email : 'orders@localmotive.ca'))) echo '1';
else echo '0';
?>