<?php

	//Main class
	class transformationOrchestrator {

		private $jsonSettingsFileName	= 'php-transform.json';		// you can change it!
		private $sourceDir 				= '';						// to read files from (like index.html)
		private $targetDir 				= '';						// to write files to (wp themes dir!)

		function readSourceFile($item, $fileType = 'fileName') {

			if ($fileType == 'contentToFileName') {
				$fName = $this->targetDir . SEP . $item[$fileType];
			} else {
				$fName = $this->sourceDir . SEP . $item[$fileType];
			}
			
			if (DEB) echo "readSourceFile: " . $fName . ", type: " . $fileType . "\n";

			$content = file_get_contents($fName);
			return $content;
		}

		function writeToFile($item, $content) {

			$fName = (isset($item['contentToFileName']) ?  $item['contentToFileName'] : $item['fileName']);

			if (DEB) echo "writeToFile: " . $fName . ", " .strlen($content) . " bytes." . "\n";

			$myfile = fopen($this->targetDir . SEP . "t-". $fName, "w") or die("Unable to open file!");
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
			$contentFromFileName = '';
			$contentToFileName = '';

			foreach ($rulesJson['actions'] as $i => $item) {

				if (DEB) echo "i: " . $i . "\n";

				if (empty($content)) {
					if (isset($item['fileName']))
						$content = $this->readSourceFile($item);
				}

				$clName = 'action' . $item['action'];
				$clObj = new $clName($item);

				if (isset($item['contentFromFileName'])) {
					$contentFromFileName = $this->readSourceFile($item, 'contentFromFileName');
					call_user_func(array($clObj, 'setContentFromFileName'), $contentFromFileName);
	        	}

				if (isset($item['contentToFileName'])) {
					$contentToFileName = $this->readSourceFile($item, 'contentToFileName');
					call_user_func(array($clObj, 'setContentToFileName'), $contentToFileName);
	        	}

				$content = call_user_func(array($clObj, 'perform'), $item, $content);
				$writeToFile = call_user_func(array($clObj, 'getWriteToFile'));

				
				
				//to call destruct method!
				unset($clObj);

				$condWriteToFile =	$writeToFile || 
									($i == count($rulesJson['actions'])-1);

				if (DEB) echo "WriteToFile = " . ($condWriteToFile?'yes':'no') . "\n";

				if ($condWriteToFile) {
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
		protected $contentFromFileName = '';	// from what file I take content
		protected $contentToFileName = '';		// from what file I will replace with new content

	    function __construct($item) {
	        if (DEB) print "Executing " . get_class($this) . "\n";
	    }

	    function __destruct() {
			if (DEB) print "Destroying " . get_class($this) . "\n";
	    }

	    function setContentFromFileName($content) {
	    	$this->contentFromFileName = $content;
	    }

		function setContentToFileName($content) {
	    	$this->contentToFileName = $content;
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

		//https://stackoverflow.com/questions/4898192/preg-replace-how-to-replace-only-matching-xxx1yyy-pattern-inside-the-selector
		//https://regex101.com/r/XIrVlW/1
		//https://regex101.com/r/PYJSjT/1
		//https://regex101.com/r/HYQmbV/1
		private $tplPattern = "/(?<=<!-- \[t:header\] -->\s)([\s\S]+?)(?=\s*<!-- \[\/t:header\] -->)/m";

		function perform($item, $content) {

			// important!
			$contentFromFileName = $this->contentFromFileName;
			$contentToFileName = $this->contentToFileName;

			foreach ($item['tags'] as $tag) {

				$matches = [];
				$curPattern = str_replace('header', $tag, $this->tplPattern);

				if (DEB) {
					echo "curPattern = " . $curPattern . "\n";
				}

				preg_match_all($curPattern, $contentFromFileName, $matches);

				if (DEB) {
					echo "contentFromFileName: " . strlen($contentFromFileName) . " bytes." . "\n";
					echo "contentToFileName: " . strlen($contentToFileName) . " bytes." . "\n";
					print_r($matches);
				}

				$c = 0;

				if (!empty($matches[1][0])) {

					$toInsert = $matches[1][0];
					
					// replacing first new line character with nothing to have a prettier output look!
					$needle = "\n";
					$pos = strpos($toInsert, $needle);

					if ($pos !== false) {
						$toInsert = substr_replace($toInsert, '', $pos, strlen($needle));
					}
					// end of replacing new line character!


					$contentToFileName = preg_replace($curPattern, $toInsert, $contentToFileName, 1, $c);
				}

				if (DEB) echo "Matches count = " . $c . "\n";
			}

			return $contentToFileName;
		}
	}

?>