<?php

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
