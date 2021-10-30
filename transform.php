<?php

	define("SEP", "\\");		// dir separator
	define("DEB", 1); 			// 1 - basic, 2 - moderate, 3 - detailed

	/*
	function log_error( $num, $str, $file, $line, $context = null )
	{
		//log_exception( new ErrorException( $str, 0, $num, $file, $line ) );
		throw new Exception("{$str}");
	}

	function log_exception(Exception $e) {
		throw new Exception($e->getMessage());
	}

	set_error_handler( "log_error" );
	set_exception_handler( "log_exception" );
	*/


	include "classTransformationOrchestrator.php";
	include "classAction.php";
	include "classOutputStats.php";
	
	//init
	//Example: "php C:\Users\EUGENY\vvv-local\www\php-files-transformation-toolkit\transform.php c:\Users\EUGENY\vvv-local\www\sample-theme\public_html"
	$mainClass = new transformationOrchestrator();
	$mainClass->run($argv);

?>