<?php

	//Main class
	class transformationOrchestrator {

		private $jsonSettingsFileName = 'php-transform.json';		// you can change it!

		private $dirs = [
			// source 				- to read files from (like index.html)
			// target 				- to write files to (wp themes dir!)
			// stubs 				- directory with stub files (template files)
		];

		function getDir($dirType) {
			return $this->dirs[$dirType];
		}

		function setDir($dirType, $val) {
			$this->dirs[$dirType] = $val;
		}

		function getSettings() {
			$content = file_get_contents($this->getDir('source') . $this->jsonSettingsFileName);
			$rulesJson = json_decode($content, true);
			return $rulesJson;
		}

		function readSourceFile($item, $fileType = 'fileName') {

			switch ($fileType) {
				case 'contentToFileName':
					$fDir = $this->getDir('target');
					break;
				case 'stubFileName':
					$fDir = $this->getDir('stubs');
					break;
				default:
					$fDir = $this->getDir('source');
					break;
			}

			$fName = $fDir . SEP . $item[$fileType];

			if (DEB) echo "readSourceFile: " . $fName . ", type: " . $fileType . "\n";

			$content = file_get_contents($fName);

			return $content;
		}

		function writeToFile($item, $content) {

			$pref = '';
			$fName = (isset($item['contentToFileName']) ?  $item['contentToFileName'] : $item['fileName']);
			$fNameFull = $this->getDir('target') . SEP . $pref . $fName;

			if (DEB) echo "writeToFile: " . $fNameFull . ", " . strlen($content) . " bytes." . "\n";

			$myfile = fopen($fNameFull, "w") or die("Unable to open file!");
			fwrite($myfile, $content);
			fclose($myfile);

			return true;
		}


		function run($argv) {

			//var_dump($argv);
			$stats = new outputStats();

			$this->setDir('source', $argv[1] . SEP);
			$rulesJson = $this->getSettings();

			$this->setDir('target', $rulesJson['targetDir']);
			$this->setDir('stubs', $rulesJson['stubsDir']);

			echo "Running transform.php at " . date("h:i:sa") . " for " . count($rulesJson['actions']) . " items!\n";

			if (DEB == 3) {
				echo "Passed rules:\n";
				print_r($rulesJson);
			}

			$content = '';
			$contentFromFileName = '';
			$contentFromStub = '';

			$stats->header = [
				'task',
				'time',
				'files',
				'processors'
			];
			//'error'

			$i = 0;

			foreach ($rulesJson['tasks'] as $task => $item) {

				$processors = $item['processors'];
				if (DEB) echo "i: " . $i . " (".$task.")" . "\n";

				$stats->setValue($i, "task", $task);
				$stats->setValue($i, "files", $item['fileName']);
				$stats->setValue($i, "processors", count($processors));

				$ii = 0;
				foreach ($processors as $proc => $procItem) {

					$clName = "action{$proc}";
					$clObj = new $clName($item, !$ii, $this);

					$content = call_user_func(array($clObj, 'perform'), $item, $content, $this);
					$writeToFile = call_user_func(array($clObj, 'getWriteToFile'));

					$stats->setTs($i);

					//to call destruct method!
					unset($clObj);

					$ii++;
				}

				$this->writeToFile($item, $content);
				$content = '';
				
				/*
				$condWriteToFile =	$writeToFile || ($i == count($rulesJson['actions'])-1);
				if (DEB) echo "WriteToFile = " . ($condWriteToFile?'yes':'no') . "\n";
				if ($writeToFile) {
					$this->writeToFile($item, $content);
					$content = '';
				}
				*/

				$i++;

				//$stats->setValue($i, "error", substr($e->getMessage(), 0, 25));
			}
			
			unset($stats);
		}
	}

?>