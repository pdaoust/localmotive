<?php

$config = Array (
	'dbUsername' => 'root',
	'dbPassword' => 'teragram',
	'dbHost' => 'localhost',
	'dbDatabase' => 'localmotive',
	'encryptionKey' => 'hS787sehf98#*9289@&*',
	'encryptionSalt' => '*Yy9pyp9alh @&*%(089',
	'serviceIDs' => array ('healthyHarvest' => 28),
	'logType' => LOG_FILE,
	'logFile' => '/var/www/localmotive/market/errors.log',
	'urlPrefix' => 'http://localmotive.local',
	'secureUrlPrefix' => 'https://localmotive.local',
	'docRoot' => '',
	'textCAPTCHAkey' => '5mcidug0ilgkkwoc4k44cckcg',
	'ajaxTimeout' => 10000,
	'display_errors' => true,
	'error_reporting' => E_ALL,
	'pfUser' => '1LOCAcad',
	'pfPwd' => 'EatL0ca1***',
	'pfPartner' => 'Ecomm',
	'pfMode' => 'test',
	'locale' => 'en_CA',
	'provDefault' => 'BC'
);
ini_set('display_errors', $config['display_errors']);
ini_set('error_reporting', $config['error_reporting']);
if (!$db = new DatabaseConnectionMySQL ($config['dbHost'], $config['dbUsername'], $config['dbPassword'], $config['dbDatabase'])) {
	databaseError($db);
	die('database error when setting up database');
}
//print_r($db);

$db->query('SELECT * from config');
while ($r = $db->getRow(F_RECORD)) {
	$config[$r->v('configID')] = $r->v('value');
}
setlocale (LC_ALL, $config['locale']);
if (!isset($config['dateFmtYear'])) $config['dateFmtYear'] = '%x';
if (!isset($config['dateFmtMonth'])) $config['dateFmtMonth'] = '%e %B';

?>
