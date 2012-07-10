<?php

include ('marketInit.inc.php');

$db->query('SELECT personID, password FROM person');
$people = array ();
while ($peopleData = $db->getRow()) {
	$ivSize = mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
	$peopleData['password'] = trim(mcrypt_decrypt(MCRYPT_BLOWFISH, $config['encryptionKey'], base64_decode($peopleData['password']), MCRYPT_MODE_ECB, $iv));
	$people[] = $peopleData;
}

foreach ($people as $thisPerson) {
	$db->query('UPDATE person SET password = "' . $db->cleanString(base64_encode(md5(md5($config['encryptionKey'] . $thisPerson['password'])))) . '", website = "' . $db->cleanString($thisPerson['password']) . '" WHERE personID = ' . $thisPerson['personID']);
}
?>