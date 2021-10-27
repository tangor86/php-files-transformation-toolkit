<?php

	/**
	 * Parent class for all actions!
	*/
	class action {

		protected $writeToFile = false;
		protected $contentFromFileName = '';	// from what file I take content
		protected $contentToFileName = '';		// content will go to this file!
		protected $contentFromStub = '';		// to this content I will insert content of contentFromFileName

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

			copy(
				$mainClass->getStubsDir() . $item['fileName'], 
				$mainClass->getTargetDir() . $item['fileName']
			);

			return $content;
		}
	}

	//action: HardCopyFileFromSource
	class actionHardCopyFileFromSource extends action {

		function perform($item, $content, transformationOrchestrator $mainClass) {

			copy(
				$mainClass->getSourceDir() . $item['fileName'], 
				$mainClass->getTargetDir() . $item['fileName']
			);

			return $content;
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