<?php

require_once ('marketInit.inc.php');

if (!$user = tryLogin()) {
	echo 'authentication failed';
	die ();
}

// $command = sprintf('mysqldump --opt -h %s -u %s -p%s %s | gzip > %s/%s/%s-%s.gz', $config['dbHost'], $config['dbUsername'], $config['dbPassword'], $config['dbDatabase'], getenv('DOCUMENT_ROOT'), 'localmotive/market/backup', 'market', strftime('%Y-%m-%d', time())); 

$command = sprintf('PGPASSWORD=%s pg_dump -h %s -U %s %s | gzip -cf', $config['dbPassword'], $config['dbHost'], $config['dbUsername'], $config['dbDatabase']);

header('Content-type: application/x-gzip');
header('Content-Disposition: attachment; filename="localmotive-market-' . strftime('%Y-%m-%d', time()) . '.sql.gz"');
passthru($command);

$db->query('UPDATE config SET value = \'' . time() . '\' WHERE configID = \'lastBackupDate\'');

?>
