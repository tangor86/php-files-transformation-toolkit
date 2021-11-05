<?php

	//Main class
	class transformationOrchestrator {

		public $dirs = [
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

		function getSettings($currentDir, $jsonSettingsFileName) {
			$content = file_get_contents($currentDir . SEP . "transformations" . SEP . $jsonSettingsFileName);
			$rulesJson = json_decode($content, true);
			return $rulesJson;
		}

		function readSourceFile($item, $fileType = 'fileName') {

			switch ($fileType) {
				case 'targetFile':
					$fDir = $this->getDir('target');
					break;
				case 'stubFile':
					$fDir = $this->getDir('stubs');
					break;
				default:
					$fDir = $this->getDir('source');
					break;
			}

			$fName = $fDir . SEP . $item[$fileType];

			d("readSourceFile: {$fName}, type: {$fileType}", 2);

			$content = file_get_contents($fName);

			return $content;
		}

		function getTargetFileName($item) {
			if (isset($item['targetFile'])) {
				return $item['targetFile'];
			} else if (isset($item['fileName'])) {
				return $item['fileName'];
			} else {
				return $item['stubFile'];
			}
		}

		function writeToFile($item, $content) {

			$pref = '';
			$fNameFull = $this->getDir('target') . SEP . $pref . $this->getTargetFileName($item);

			d("writeToFile: {$fNameFull}, " . strlen($content) . " bytes.", 1);

			$myfile = fopen($fNameFull, "w") or die("Unable to open file!");
			fwrite($myfile, $content);
			fclose($myfile);

			return true;
		}

		function setStatsTask($stats, $i, $task, $item, $processors) {

			if (isset($item['fileName'])) {
				
				$f = $item['fileName'];

			} else if (isset($item['folderName'])) {
				
				$f = $item['folderName'];

			} else if (isset($item['tplFile'])) {

				$f = $item['tplFile'];

			}

			$processors = array_column($processors, "name");

			$stats->setValue($i, "task", $task, true);
			$stats->setValue($i, "files", $f);
			$stats->setValue($i, "processors", $processors);
		}

		function run($argv, $currentDir) {

			//var_dump($argv);
			//echo "my env: " . getenv('t_engine');
			$stats = new outputStats();

			$rulesJson = $this->getSettings($currentDir, $argv[1]);
			$this->dirs = $rulesJson['dirs'];

			echo "Running transform.php at " . date("h:i:sa") . " for " . count($rulesJson['tasks']) . " items!\n";

			d($rulesJson, 3);

			$contentFromFileName = '';
			$contentFromStub = '';

			$stats->header = [
				'task' 			=> 25,
				'time' 			=> 7,
				'files' 		=> 25,
				'processors' 	=> 25,
				'stats'			=> 50
			];
			//'error'

			$i = 0;

			foreach ($rulesJson['tasks'] as $task => $item) {

				$processors = $item['processors'];
				d("i: " . $i . " (".$task.")", 2);

				$this->setStatsTask($stats, $i, $task, $item, $processors);

				$content = '';
				$ii = $i-1;
				foreach ($processors as $proc => $procItem) {

					$ii++;

					$procName = $procItem['name'];
					$clName = "action{$procName}";
					$clObj = new $clName($item, !$ii, $content, $this);
					
					$stats->setValue($ii, 'processors', $procName);

					$content = call_user_func(array($clObj, 'perform'), array_merge($item, $procItem), $this);
					$writeToFile = call_user_func(array($clObj, 'getWriteToFile'));
					$curStats = call_user_func(array($clObj, 'getStats'));

					//to call destruct method!
					unset($clObj);

					$stats->setValue($ii, 'stats', json_encode($curStats, JSON_UNESCAPED_SLASHES));

				}

				$stats->setTs($i);

				$i = $ii;

				//if ($writeToFile) {
				if (isset($item["writeToFile"])) {
					$this->writeToFile($item, $content);
					$content = '';
				}

				$i++;

				//$stats->setValue($i, "error", substr($e->getMessage(), 0, 25));
			}
			
			unset($stats);
		}
	}

?>