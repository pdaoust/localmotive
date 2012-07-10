<?php

require_once ('marketInit.inc.php');

$ajax = true;

$error = false;
$errorFields = array ();
$errorMsg = array ();

if (!$user = tryLogin()) {
	$error = E_PERMISSION;
	$errorMsg[] = 'You are not logged in.';
}
if (!isset($_POST['personID'])) {
	$error = E_NO_OBJECT_ID;
	$errorMsg[] = 'You have not chosen a person to send a message to.';
}
if (!$recipient = new Person ((int) $_POST['personID'])) {
	$error = E_NO_OBJECT;
	$errorMsg[] = 'This person\'s account could not be loaded.';
}
if (!$recipient->personID) {
	$error = E_NO_OBJECT;
	$errorMsg[] = 'This person does not exist.';
} else if (!$recipient->isIn($user)) {
	$error = E_PERMISSION;
	$errorMsg[] = 'You do not have permission to send a message to this recipient.';
}

if (isset($_REQUEST['children'])) $children = (bool) $_REQUEST['children'];
else $children = false;

if (!$error) {
	if (!isset($_REQUEST['subject'])) $subject = false;
	else $subject = trim($_REQUEST['subject']);

	if (!$subject) {
		$errorFields[] = 'emailSubject';
		$error = E_INVALID_DATA;
		$errorMsg[] = 'Please enter a subject for this message.';
	}

	if ($children && !$recipient->isLeafNode()) {
		$tree = $recipient->getTree();
	} else $tree[$recipient->personID] = $recipient;

	if (isset($_REQUEST['message'])) $message = trim($_REQUEST['message']);
	else $message = false;

	if (!$message) {
		$errorFields[] = 'emailMessage';
		$error = E_INVALID_DATA;
		$errorMsg[] = 'The body of this message is blank.';
	}

		if (!$error) {
		$headers = "From: " . $user->contactName . ($user->groupName ? ' (' . $user->groupName . ')' : null) . '<' . $user->email . ">\r\nReply-To: feedme@localmotive.ca\r\n";

		$emails = 0;
		$failedEmails = 0;
		foreach ($tree as $k => $v) {
			if ($v->email) {
				$result = @mail($v->contactName . ($v->groupName ? ' (' . $v->groupName . ')' : null) . ' <' . $v->email . '>', $subject, $message, $headers);
				if ($result) $emails ++;
				else $failedEmails ++;
			}
		}
	}
}

if ($ajax) {
	if (count($errorMsg)) $errorMsg = '<p>'.implode('</p><p>', $errorMsg).'</p>';
	else $errorMsg = null;
	echo json_encode(array(
		'error' => ($error ? $error : false),
		'errorMsg' => $errorMsg,
		'errorFields' => $errorFields,
		'emails' => (int) $emails,
		'failedEmails' => (int) $failedEmails
	));
}
?>