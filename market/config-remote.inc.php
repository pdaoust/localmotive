<?php

$config = Array (
	'dbUsername' => 'e20967f_local',
	'dbPassword' => 'pleasefeedme',
	'dbHost' => 'localhost',
	'dbDatabase' => 'e20967f_local',
	'encryptionKey' => 'hS787sehf98#*9289@&*',
	'serviceIDs' => array ('farmersMarket' => 516, 'healthyHarvest' => 28),
	'logType' => LOG_CONSOLE,
	'logFile' => '/var/www/vhosts/localmotive.ca/httpdocs/market/errors.log',
	'textCAPTCHAkey' => '5mcidug0ilgkkwoc4k44cckcg',
	'ajaxTimeout' => 10000,
	'pfUser' => '1LOCAcad',
	'pfPwd' => 'EatL0ca1***',
	'pfPartner' => 'Ecomm',
	'pfMode' => 'live'
);
if (!$db = new DatabaseConnectionMySQL ($config['dbHost'], $config['dbUsername'], $config['dbPassword'], $config['dbDatabase'])) {
	databaseError($db);
	die();
}

$db->query('SELECT * from config');
while ($r = $db->getRow(F_RECORD)) {
	$config[$r->v('configID')] = $r->v('value');
}
setlocale (LC_ALL, $config['locale']);
if (!isset($config['dateFmtYear'])) $config['dateFmtYear'] = '%x';
if (!isset($config['dateFmtMonth'])) $config['dateFmtMonth'] = '%e %B';

?>
