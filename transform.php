<?php

	define("SEP", "\\");		// dir separator
	define("DEB", 1); 			// 1 - basic, 2 - moderate, 3 - detailed

	include "transformClasses.php";
	
	//init
	//Example: "php C:\Users\EUGENY\vvv-local\www\php-files-transformation-toolkit\transform.php c:\Users\EUGENY\vvv-local\www\sample-theme\public_html"
	$mainClass = new transformationOrchestrator();
	$mainClass->run($argv);

?>