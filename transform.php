<?php

	define("SEP", "\\");		// dir separator
	define("DEB", 1); 			// 1 - basic, 2 - moderate, 3 - detailed

	//Main class
	class transformationOrchestrator {

		private $jsonSettingsFileName	= 'php-transform.json';		// you can change it!
		private $sourceDir 				= '';						// to read files from (like index.html)
		private $targetDir 				= '';						// to write files to (wp themes dir!)

		function readSourceFile($item) {
			$content = file_get_contents($this->sourceDir . SEP . $item['fileName']);
			return $content;
		}

		function writeToFile($item, $content) {
			if (DEB) echo "Writing to file: " . $item['fileName'] . ", " .strlen($content) . " bytes." . "\n";

			$myfile = fopen($this->targetDir . SEP . "t-". $item['fileName'], "w") or die("Unable to open file!");
			fwrite($myfile, $content);
			fclose($myfile);

			return true;
		}


		function run($argv) {

			//var_dump($argv);
			//transformationOrchestrator::$rules['workingDir'] = $argv[1];

			$string = file_get_contents($argv[1] . SEP . $this->jsonSettingsFileName);
			$rulesJson = json_decode($string, true);

			$this->sourceDir = $argv[1];
			$this->targetDir = $rulesJson['targetDir'];

			echo "Running transform.php at " . date("h:i:sa") . " for " . count($rulesJson['actions']) . " items!\n";

			if (DEB == 3) {
				echo "Passed rules:\n";
				print_r($rulesJson);
			}

			$content = '';

			foreach ($rulesJson['actions'] as $i => $item) {

				if (DEB) echo "i: " . $i . "\n";

				if (empty($content)) {
					$content = $this->readSourceFile($item);
				}

				$clName = 'action' . $item['action'];
				$clObj = new $clName($item, $content);
				$content = call_user_func(array($clObj, 'perform'), $item, $content);
				$writeToFile = call_user_func(array($clObj, 'getWriteToFile'));

				if (DEB) echo "writeToFile = " . ($writeToFile?'yes':'no') . "\n";
				
				//to call destruct method!
				unset($clObj);

				if (
					$writeToFile || 
					($i == count($rulesJson['actions'])-1)
				) {
					$this->writeToFile($item, $content);
					$content = '';
				}

				//call_user_func(array('action'.$v['action'], 'perform'), $v);
			}
		}
	}
	



	/**
	 * Parent class for all actions!
	*/
	class action {

		protected $writeToFile = false;

	    function __construct() {
	        if (DEB) print "Executing " . get_class($this) . "\n";
	    }

	    function __destruct() {
			if (DEB) print "Destroying " . get_class($this) . "\n";
	    }

	    function getWriteToFile() {
	    	return $this->writeToFile;
	    }

	    function perform($item, $content) {
	    	//should be redefined at all childs!
	    }
	}



	//action: CopyFile
	class actionCopyFile extends action {

		function perform($item, $content) {
			//since all files actions are done in orchestrator, this class is more like stub!
			$this->writeToFile = true;

			return $content;
		}
	}

	class actionHTMLReplace extends action {

		//https://regex101.com/r/XIrVlW/1
		private $rPattern = "/<!-- \[t:header\] -->([\s\S]+?)<!-- \[\/t:header\] -->/m";

		function perform($item, $content) {

			$matches = [];
			preg_match_all($this->rPattern, $content, $matches);

			if (DEB) {
				echo "Content length: " . strlen($content) . "\n";
				print_r($matches);
			}

			return $content;
		}
	}

	
	//init
	//Example: "php C:\Users\EUGENY\vvv-local\www\php-files-transformation-toolkit\transform.php c:\Users\EUGENY\vvv-local\www\sample-theme\public_html"
	$mainClass = new transformationOrchestrator();
	$mainClass->run($argv);
	
?>