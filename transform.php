<?php

	define("SEP", "\\");

	//settings class!
	class transformer {
		static $rules = array(
			'workingDir' => 'C:\Users\EUGENY\vvv-local\www\sample-theme\public_html',
			'targetDir' => 'C:\Users\EUGENY\vvv-local\www\wordpress-one\public_html\wp-content\themes\gogogo',
			'items' => [
				array(
					'command' => 'copyFile',
					'filename' => 'style.css'
				)
			]
		);
	}
	
	function getTargetPath($fName) {
		//return $transformationObj['targetDir'] . SEP . $fName;
		return transformer::$rules['targetDir'] . SEP . $fName;
	}

	/**
	 * Command to copy a file!
	 */
	function cmd_copyFile($item) {
		
		$myfile = fopen(getTargetPath($item['filename']), "w") or die("Unable to open file!");
		//$myfile = fopen("style2.css", "w") or die("Unable to open file!");
		$txt = file_get_contents(transformer::$rules['workingDir'] . SEP . $item['filename']);
		fwrite($myfile, $txt);
		fclose($myfile);

		return true;
	}


	echo "Running transform.php at " . date("h:i:sa") . " for " . count(transformer::$rules['items']) . " items!\n";
	echo "Executing...\n";

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

?>