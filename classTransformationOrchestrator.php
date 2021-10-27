<?php

	//Main class
	class transformationOrchestrator {

		private $jsonSettingsFileName	= 'php-transform.json';		// you can change it!
		private $sourceDir 				= '';						// to read files from (like index.html)
		private $targetDir 				= '';						// to write files to (wp themes dir!)
		private $stubsDir 				= '';						// directory with stub files (template files)

		function getSourceDir() {
			return $this->sourceDir;
		}

		function getTargetDir() {
			return $this->targetDir;
		}

		function getStubsDir() {
			return $this->stubsDir;
		}

		function readSourceFile($item, $fileType = 'fileName') {

			//TODO: dir array!
			if ($fileType == 'contentToFileName') {
				$fName = $this->targetDir . SEP . $item[$fileType];
			} else if ($fileType == 'stubFileName') {
				$fName = $this->stubsDir . SEP . $item[$fileType];
			} else {
				$fName = $this->sourceDir . SEP . $item[$fileType];
			}
			
			if (DEB) echo "readSourceFile: " . $fName . ", type: " . $fileType . "\n";

			$content = file_get_contents($fName);

			return $content;
		}

		function writeToFile($item, $content) {

			$pref = '';
			$fName = (isset($item['contentToFileName']) ?  $item['contentToFileName'] : $item['fileName']);

			if (DEB) echo "writeToFile: " . $fName . ", " .strlen($content) . " bytes." . "\n";

			$myfile = fopen($this->targetDir . SEP . $pref . $fName, "w") or die("Unable to open file!");
			fwrite($myfile, $content);
			fclose($myfile);

			return true;
		}


		function run($argv) {

			//var_dump($argv);
			//transformationOrchestrator::$rules['workingDir'] = $argv[1];

			$string = file_get_contents($argv[1] . SEP . $this->jsonSettingsFileName);
			$rulesJson = json_decode($string, true);

			$this->sourceDir = $argv[1] . '\\';
			$this->targetDir = $rulesJson['targetDir'];
			$this->stubsDir = $rulesJson['stubsDir'];

			echo "Running transform.php at " . date("h:i:sa") . " for " . count($rulesJson['actions']) . " items!\n";

			if (DEB == 3) {
				echo "Passed rules:\n";
				print_r($rulesJson);
			}

			$content = '';
			$contentFromFileName = '';
			$contentFromStub = '';

			foreach ($rulesJson['actions'] as $i => $item) {

				$action = $item['action'];
				if (DEB) echo "i: " . $i . " (".$action.")" . "\n";

				if (empty($content) && $action != 'HardCopyFileFromStubs') {
					if (isset($item['fileName']))
						$content = $this->readSourceFile($item);
				}

				$clName = 'action' . $action;
				$clObj = new $clName($item);

				if (isset($item['contentFromFileName'])) {
					$contentFromFileName = $this->readSourceFile($item, 'contentFromFileName');
					call_user_func(array($clObj, 'setContentFromFileName'), $contentFromFileName);
	        	}

				if (isset($item['stubFileName'])) {
					$contentFromStub = $this->readSourceFile($item, 'stubFileName');
					call_user_func(array($clObj, 'setContentFromStub'), $contentFromStub);
	        	}

				$content = call_user_func(array($clObj, 'perform'), $item, $content, $this);
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

?>