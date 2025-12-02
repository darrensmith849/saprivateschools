<?php

namespace NGD_THEME\Functions;

if ( ! defined( 'ABSPATH' ) ) exit;

class Functions {

	public function __construct() {
	}

	public static function ppa( $string = '', $return = false ): bool|string {
		ob_start();
		?>
		<pre><?php print_r( $string ) ?></pre><?php
		$output = ob_get_clean();
		if ( $return ) {
			return $output;
		}
		else {
			echo $output;
		}

		return '';
	}

	public static function logMessage($entry, $mode = 'a', $file = 'NGD') : void {
		// Get the debug backtrace
		$debugBacktrace = debug_backtrace();

		// Get the file name and line number from the trace
		$callerFile = $debugBacktrace[0]['file'] ?? 'Unknown file';
		$callerLine = $debugBacktrace[0]['line'] ?? 'Unknown line';

		// Prepare the upload directory
		$upload_dir = wp_upload_dir();
		$upload_dir = $upload_dir['basedir'];

		// If the entry is array, json_encode.
		if (is_array($entry)) {
            $entry = Functions::ppa($entry, true);
		}

		// Prepare the log message
		$logMessage = sprintf(
			"%s::%s in %s on line %d\n",
			current_time('mysql'),
			$entry,
			$callerFile,
			$callerLine
		);

		// Write the log file.
		$file = $upload_dir . '/' . $file . '.log';
		$fileHandle = fopen($file, $mode);
		fwrite($fileHandle, $logMessage);
		fclose($fileHandle);
	}

}