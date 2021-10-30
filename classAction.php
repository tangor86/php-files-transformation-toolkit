<?php

	/**
	 * Parent class for all actions!
	*/
	class action {

		protected $writeToFile = false;
		protected $contentFromFileName = '';	// from what file I take content
		protected $contentToFileName = '';		// content will go to this file!
		protected $contentFromStub = '';		// to this content I will insert content of contentFromFileName

	    function __construct($item, $isFirst, transformationOrchestrator $mainClass ) {

	        if (DEB) print "Executing " . get_class($this) . "\n";

	        /*
	        if (empty($content) && $action != 'HardCopyFileFromStubs') {
				if (isset($item['fileName']))
					$content = $this->readSourceFile($item);
			}
			*/

			/*
			if (isset($item['contentFromFileName'])) {
				call_user_func(
					array($clObj, 'setContentFromFileName'),
					$this->readSourceFile($item, 'contentFromFileName')
				);
        	}

			if (isset($item['stubFileName'])) {
				call_user_func(
					array($clObj, 'setContentFromStub'), $contentFromStub,
					$this->readSourceFile($item, 'stubFileName')
				);
        	}
			*/

			if (isset($item['contentFromFileName'])) {
				$this->setContentFromFileName($mainClass->readSourceFile($item, 'contentFromFileName'));
			}

			if (isset($item['stubFileName'])) {
				$this->setContentFromStub($mainClass->readSourceFile($item, 'stubFileName'));
			}
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

		function setContentFromStub($content) {
	    	$this->contentFromStub = $content;
	    }

	    function getWriteToFile() {
	    	return $this->writeToFile;
	    }

	    function perform($item, $content, transformationOrchestrator $mainClass) {
	    	//should be redefined at all childs!
	    }
	}



	//action: CopyFile
	class actionCopyFile extends action {

		function perform($item, $content, transformationOrchestrator $mainClass) {

			//since all files actions are done in orchestrator, this class is more like stub!
			$this->writeToFile = true;

			return $content;
		}
	}

	//action: HardCopyFileFromStubs
	//this class does brutal copying as OS does (doesn't open file)
	class actionHardCopyFileFromStubs extends action {

		function perform($item, $content, transformationOrchestrator $mainClass) {

			if (gettype($item['fileName']) == 'array') {
				foreach ($item['fileName'] as $fName) {
					copy(
						$mainClass->getDir('stubs') . $fName, 
						$mainClass->getDir('target') . $fName
					);
				}
			} else {
				copy(
					$mainClass->getDir('stubs') . $item['fileName'], 
					$mainClass->getDir('target') . $item['fileName']
				);
			}
			
			return $content;
		}
	}

	//action: HardCopyFileFromSource
	class actionHardCopyFileFromSource extends action {

		function perform($item, $content, transformationOrchestrator $mainClass) {

			copy(
				$mainClass->getDir('source') . $item['fileName'], 
				$mainClass->getDir('target') . $item['fileName']
			);

			return $content;
		}
	}

	//action: CopyFolder
	class actionCopyFolder extends action {

		//https://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php/2050965

		function perform($item, $content, transformationOrchestrator $mainClass) {

			if (gettype($item['folderName']) == 'string') {
				$arr = [$item['folderName']];
			} else {
				$arr = $item['folderName'];
			}

			foreach ($arr as $key => $folderName) {
				$this->recurseCopy(
					$mainClass->getDir($item['dirType']) . $folderName,
					$mainClass->getDir('target') . $folderName
				);
			}
			
			return $content;
		}

		function recurseCopy(
		    string $sourceDirectory,
		    string $destinationDirectory,
		    string $childFolder = ''
		): void {

		    $directory = opendir($sourceDirectory);

		    if (is_dir($destinationDirectory) === false) {
		        mkdir($destinationDirectory);
		    }

		    if ($childFolder !== '') {
		        if (is_dir("$destinationDirectory/$childFolder") === false) {
		            mkdir("$destinationDirectory/$childFolder");
		        }

		        while (($file = readdir($directory)) !== false) {
		            if ($file === '.' || $file === '..') {
		                continue;
		            }

		            if (is_dir("$sourceDirectory/$file") === true) {
		                $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
		            } else {
		                copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
		            }
		        }

		        closedir($directory);

		        return;
		    }

		    while (($file = readdir($directory)) !== false) {
		        if ($file === '.' || $file === '..') {
		            continue;
		        }

		        if (is_dir("$sourceDirectory/$file") === true) {
		            $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
		        }
		        else {
		            copy("$sourceDirectory/$file", "$destinationDirectory/$file");
		        }
		    }

		    closedir($directory);
		}
	}



	class actionHTMLReplace extends action {

		//https://stackoverflow.com/questions/4898192/preg-replace-how-to-replace-only-matching-xxx1yyy-pattern-inside-the-selector
		//https://regex101.com/r/XIrVlW/1
		//https://regex101.com/r/PYJSjT/1
		//https://regex101.com/r/HYQmbV/1
		private $tplPattern = "/(?<=<!-- \[t:header\] -->\s)([\s\S]+?)(?=\s*<!-- \[\/t:header\] -->)/m";

		function perform($item, $content, transformationOrchestrator $mainClass) {

			// important!
			$contentFromFileName = $this->contentFromFileName;
			$contentFromStub = $this->contentFromStub;

			foreach ($item['tags'] as $tag) {

				$matches = [];
				$curPattern = str_replace('header', $tag, $this->tplPattern);

				if (DEB) {
					echo "curPattern = " . $curPattern . "\n";
				}

				preg_match_all($curPattern, $contentFromFileName, $matches);

				if (DEB) {
					echo "contentFromFileName: " . strlen($contentFromFileName) . " bytes." . "\n";
					echo "contentFromStub: " . strlen($contentFromStub) . " bytes." . "\n";
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

					$contentFromStub = preg_replace($curPattern, $toInsert, $contentFromStub, 1, $c);
				}

				if (DEB) echo "Matches count = " . $c . "\n";
			}

			return $contentFromStub;
		}
	}


?>