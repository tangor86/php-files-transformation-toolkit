<?php

	define("SEP", "\\");				// dir separator
	define("CLASS_DIR", "classes");		// directory which contains class files
	define("DEB", 0); 					// 1 - basic, 2 - moderate, 3 - detailed
	
	function d($msg, $lvl = 1) {
		if ($lvl <= DEB) {
			if (gettype($msg) == "array") {
				print_r($msg);
			} else {
				echo "{$msg}\n";
			}
		}
	}

	spl_autoload_register(function (string $className): void {

		//require "{$_SERVER['DOCUMENT_ROOT']}\{$className}.php";
		
		if (class_exists($className)) {
            return;
        }

		if (file_exists($className)) {
			require_once("./{$className}.php");
		} else {
			require_once(CLASS_DIR . SEP . "{$className}.php");
		}

	});


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
	
	require_once(CLASS_DIR . SEP . 'action.php');

	//init
	//Example: "php C:\Users\EUGENY\vvv-local\www\php-files-transformation-toolkit\transform.php c:\Users\EUGENY\vvv-local\www\sample-theme\public_html"
	$mainClass = new transformationOrchestrator();
	$mainClass->run($argv);

?>