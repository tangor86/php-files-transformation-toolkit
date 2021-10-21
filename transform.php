<?php

	define("SEP", "\\");
	define("DEB", 1);

	//settings class!
	class transformer {

		static $jsonSettingsFileName = 'php-transform.json';

		static $rules = array(
			'workingDir' => 'C:\Users\EUGENY\vvv-local\www\sample-theme\public_html',
			'targetDir' => 'C:\Users\EUGENY\vvv-local\www\wordpress-one\public_html\wp-content\themes\gogogo'
		);
	}
	
	/**
	 * Parent class for all actions!
	*/
	class action {

	    function __construct() {
	        if (DEB) print "In constructor " . get_class($this) . "\n";
	    }

	    function __destruct() {
			if (DEB) print "Destroying " . get_class($this) . "\n";
	    }

		function getTargetPath($fName) {
			//return $transformationObj['targetDir'] . SEP . $fName;
			return transformer::$rules['targetDir'] . SEP . $fName;
		}

	    function perform($item) {
	    	//should be redefined at all childs!
	    }
	}

	//CopyFile class!
	class actionCopyFile extends action {

		function perform($item) {
			$myfile = fopen($this->getTargetPath($item['fileName']), "w") or die("Unable to open file!");
			//$myfile = fopen("style2.css", "w") or die("Unable to open file!");
			$txt = file_get_contents(transformer::$rules['workingDir'] . SEP . $item['fileName']);
			fwrite($myfile, $txt);
			fclose($myfile);

			return true;
		}
	}

	
	//var_dump($argv);
	transformer::$rules['workingDir'] = $argv[1];


	$string = file_get_contents($argv[1] . SEP . transformer::$jsonSettingsFileName);
	$rulesJson = json_decode($string, true);

	echo "Running transform.php at " . date("h:i:sa") . " for " . count($rulesJson['actions']) . " items!\n";

	if (DEB) {
		echo "Passed rules:\n";
		print_r($rulesJson);
	}

	foreach ($rulesJson['actions'] as $v) {
		$clName = 'action' . $v['action'];
		$clObj = new $clName();
		call_user_func(array($clObj, 'perform'), $v);

		//call_user_func(array('action'.$v['action'], 'perform'), $v);
	}


	/*
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
	*/
?>