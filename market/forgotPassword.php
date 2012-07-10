<?php

require_once ('marketInit.inc.php');

$pageTitle = 'Localmotive - Reset your password';

$noPerson = false;
$resetStatus = false;
if (isset($_REQUEST['reset'])) {
	if (isset($_REQUEST['email'])) {
		$email = trim($_REQUEST['email']);
		if (!$db->query('SELECT * FROM person WHERE email = \'' . $db->cleanString($email) . '\'')) {
			databaseError($db);
			die ();
		}
		$person = new Person ();
		while ($r = $db->getRow(F_RECORD)) {
			$person = new Person ($r);
		}
		if (!$person->personID) {
			$noPerson = true;
		} else {
			$newPassword = createRandomPassword();
			$person->setPassword($newPassword);
			$person->save();
			$message = 'Your new temporary password is ' . $newPassword . "\nPlease log into your account as soon as possible with this password,\nand change it to a password of your own choice.\n\nhttp://www.localmotive.ca/market/";
			$headers = "From: Localmotive Market <orders@localmotive.ca>\r\nReply-To: orders@localmotive.ca";
			logError('reset password for ' . $email . ' to ' . $newPassword);
			logError('about to send message -- headers ' . $headers . ' message ' . $message);
			$mailStatus = @mail($email, 'Your new password', $message, $headers);
			if ($mailStatus) $resetStatus = true;
			else logError('could not send mail to ' . $email);
		}
	}
}
$noLogin = true;
include ($path . '/header.tpl.php');
include ($path . '/market/templates/forgotPassword.tpl.php');
include ($path . '/footer.tpl.php');

?>
