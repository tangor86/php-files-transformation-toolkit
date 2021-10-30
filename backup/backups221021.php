<?php

//$toInsert = trim($matches[1][0]);
//$toInsert = str_replace(array("\n", "\r"), '', $toInsert, 1);

foreach (transformer::$rules['items'] as $v) {

	$ret = false;
	echo "=> " . $v['command'];

	switch ($v['command']) {
		case 'copyFile':
			$ret = cmd_copyFile($v);
			break;
		
		default:
			# code...
			break;
	}

	echo " -> " . ($ret?'done':'failed!') . "\n";
}

