<?php

	/**
	 * Parent class for all actions!
	*/
	class action {

		protected $writeToFile = false;
		protected $contentFromFileName = '';	// from what file I take content
		protected $contentToFileName = '';		// content will go to this file!
		protected $contentFromStub = '';		// to this content I will insert content of contentFromFileName
		protected $stats = [];

	    function __construct($item, $isFirst, transformationOrchestrator $mainClass ) {

	        d("Executing " . get_class($this));

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
			d("Destroying " . get_class($this));
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

	    function getStats() {
	    	return $this->stats;
	    }

	    function perform($item, $content, transformationOrchestrator $mainClass) {
	    	//should be redefined at all childs!
	    }
	}



	//action: CopyFile
	class actionCopyFile extends action {

		function perform($item, $content, transformationOrchestrator $mainClass) {

			if (gettype($item['fileName']) == 'string') {
				$arr = [$item['fileName']];
			} else {
				$arr = $item['fileName'];
			}

			$fromDir = isset($item['fromDir']) ? $item['fromDir'] : 'source';
			$toDir = isset($item['toDir']) ? $item['toDir'] : 'target';

			$c = 0;
			foreach ($arr as $key => $fName) {
				$r = copy(
					$mainClass->getDir($fromDir) . $fName, 
					$mainClass->getDir($toDir) . $fName
				);

				if ($r) $c++;
			}

			$this->stats['msg'] = "Copied {$c} / " . count($arr);

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
					$mainClass->getDir($item['fromDir']) . $folderName,
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
		        } else {
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

			$this->writeToFile = true;

			// important!
			$contentFromFileName = $this->contentFromFileName;
			$contentFromStub = $this->contentFromStub;

			foreach ($item['tags'] as $tag) {

				$matches = [];
				$curPattern = str_replace('header', $tag, $this->tplPattern);

				d("curPattern = {$curPattern}");

				preg_match_all($curPattern, $contentFromFileName, $matches);

				d("contentFromFileName: " . strlen($contentFromFileName) . " bytes.");
				d("contentFromStub: " . strlen($contentFromStub) . " bytes.");
				d($matches, 2);

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

				d("Matches count = {$c}");

				$this->stats["{$tag}"] = (isset($matches[1][0]) ? 'y' : 'n') . "-" . $c;
			}

			return $contentFromStub;
		}
	}


?>