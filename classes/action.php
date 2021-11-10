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
		protected $content = '';
		protected $tplContent = '';

	    function __construct($item, $isFirst, $content, transformationOrchestrator $mainClass ) {

	        d("Executing " . get_class($this));

	        if (empty($content)) {
	        	/*
				if (isset($item['tplFile'])) {
					
					//$this->setContentFromFileName($mainClass->readSourceFile($item, 'tplFile'));
					$this->content = $mainClass->readSourceFile($item, 'tplFile');

				}

				if (isset($item['stubFile'])) {
					
					//$this->setContentFromStub($mainClass->readSourceFile($item, 'stubFile'));
					$this->content = $mainClass->readSourceFile($item, 'stubFile');

				}
				*/
	        } else {
	        	$this->content = $content;
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

	    function perform($item, transformationOrchestrator $mainClass) {
	    	//should be redefined at all childs!
	    }
	}



	//action: CopyFile
	class actionCopyFile extends action {

		function perform($item, transformationOrchestrator $mainClass) {

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

			return 'done';
		}
	}

	//action: CopyFolder
	class actionCopyFolder extends action {

		//https://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php/2050965

		function perform($item, transformationOrchestrator $mainClass) {

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
			
			return 'done';
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

	//action: StrReplace (x times)
	class actionStrReplace extends action {

		//https://stackoverflow.com/questions/4703737/how-to-use-str-replace-to-remove-text-a-certain-number-of-times-only-in-php
		function str_replace_occurrences($find, $replace, $string, $count = -1) {
		    // current occrurence 
		    $current = 0;
		    // while any occurrence 
		    while (($pos = strpos($string, $find)) != false) {
		        // update length of str (size of string is changing)
		        $len = strlen($find);
		        // found next one 
		        $current++;
		        // check if we've reached our target 
		        // -1 is used to replace all occurrence 
		        if ($current <= $count || $count == -1) {
		            // do replacement 
		            $string = substr_replace($string, $replace, $pos, $len);
		        } else {
		            // we've reached our 
		            break;
		        }
		    }
		    return $string;
		}

		function perform($item, transformationOrchestrator $mainClass) {

			$this->writeToFile = true;

			$l1 = strlen($this->content);
			$c = isset($item["count"]) ? intval($item["count"]) : -1;
			$content = $this->str_replace_occurrences($item["find"], $item["replace"], $this->content, $c); 
			$l2 = strlen($content);

			$this->stats['len1'] = $l1; 
			$this->stats['len2'] = $l2; 

			return $content;
		}
	}

	class actionHTMLReplace extends action {

		//https://stackoverflow.com/questions/4898192/preg-replace-how-to-replace-only-matching-xxx1yyy-pattern-inside-the-selector
		//https://regex101.com/r/XIrVlW/1
		//https://regex101.com/r/PYJSjT/1
		//https://regex101.com/r/HYQmbV/1
		private $tplPattern = "/(?<=<!-- \[t:header\] -->\s)([\s\S]+?)(?=\s*<!-- \[\/t:header\] -->)/m";

		//https://regex101.com/r/Mt0KUi/1
		private $tplCond	= "/(<!-- \[t:cond\]\s)([\s\S]+)(-->\s)([\s\S]+)(\<!--\[\/t:cond\]-->)/m";

		function perform($item, transformationOrchestrator $mainClass) {

			$this->writeToFile = true;

			// important!
			$tplContent = $mainClass->readSourceFile($item, 'tplFile');
			$stubFileContent = !empty($this->content) ? $this->content : $mainClass->readSourceFile($item, 'stubFile');

			// replacing conditional tags first!
			$tplContent = preg_replace($this->tplCond, "$2", $tplContent);

			foreach ($item['tags'] as $tag) {

				$matches = [];
				$curPattern = str_replace('header', $tag, $this->tplPattern);

				d("curPattern = {$curPattern}");

				preg_match_all($curPattern, $tplContent, $matches);

				d("tplContent: " . strlen($tplContent) . " bytes.");
				d("stubFileContent: " . strlen($stubFileContent) . " bytes.");
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

					$stubFileContent = preg_replace($curPattern, $toInsert, $stubFileContent, 1, $c);
				}

				d("Matches count = {$c}");

				$this->stats["{$tag}"] = (isset($matches[1][0]) ? 'y' : 'n') . "-" . $c;
			}

			return $stubFileContent;
		}
	}


?>