<?php

class BasicClass {

	/* [ PROPERTIES ] */

	protected $debug;
	protected $register_file_loc;

	protected $reflection_obj;
	protected $properties_to_get;
	protected $properties_to_set;


	/* [ CONSTRUCTOR & DESTRUCTOR ] */

	public function __construct ( $class_name ) {
		$this->debug = ETC_DEBUG_MODE;
		$this->register_file_loc = ETC_LOG_FILE;

		$this->reflection_obj = new ReflectionClass( $class_name );
		$this->properties_to_set = array( 'debug', 'reflection_obj', 'register_file_loc' );
		$this->properties_to_get = array();
		foreach ( $this->reflection_obj->getProperties() as $property ) {
			$this->properties_to_get[] = $property->getName();
		}

		// register a custom PHP error handler, configured to handle all errors (allowing, for example, a complete error logging)
		set_error_handler( array( $this, 'errorHandler' ), ~0 ); // E_ALL should be an alias of something like ~0 or 2147483647 (check differences of specific PHP versions)
		//set_exception_handler(); // TODO
		register_shutdown_function( array( $this, 'shutdownHandler' ) );
	}

	public function __destruct () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* [ PROTECTED METHODS ] */

	//protected static function error () {
		/*
		¯\_(ツ)_/¯
		*/
	//}

	protected static function table () {
		/*
		¯\_(ツ)_/¯
		*/
	}

	protected static function deleteOldEntries () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* [ PUBLIC METHODS ] */

	//================================================================================================================================
	// CLASS SUPER POWERS : START
	public function callMethod ( $method_name ) {
		if ( $this->reflection_obj->hasMethod( $method_name ) ) {
			return $this->$method_name();
		} else {
			$this->registerLog( 'error', 'Class "'.$this->reflection_obj->getName().'" has no method named "'.$method_name.'".' );
		}
	}

	public function getProperty ( $property_name ) {
		if ( in_array( $property_name, $this->properties_to_get ) === FALSE ) {
			$this->registerLog( 'error', 'Class "'.$this->reflection_obj->getName().'" has no property named "'.$property_name.'".' );
		} else {
			return $this->$property_name;
		}
	}

	public function setProperty ( $property_name, $value ) {
		if ( in_array( $property_name, $this->properties_to_set ) === FALSE ) {
			if ( in_array( $property_name, array_keys( get_class_vars( get_class( $this ) ) ) ) === FALSE ) {
				$this->registerLog( 'error', 'Property "'.$property_name.'" does not exist in the class "'.$this->reflection_obj->getName().'"' );
			} else {
				$this->registerLog( 'error', 'Value of property "'.$property_name.'" can not be changed in the class "'.$this->reflection_obj->getName().'"' );
			}
		} else {
			$this->$property_name = $value;
		}
	}
	// CLASS SUPER POWERS : END
	//================================================================================================================================
	// ERROR HANDLER AND REPORTING : START
	public static function printHTMLMessage ( $message, $type = 'notice' ) {
		switch ( strtolower ( $type ) ) {
			case 'error':
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$background_color = '#DD2222'; // reddish
			break;

			case 'warning':
			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
			case E_RECOVERABLE_ERROR:
				$background_color = '#EEEE44'; // yellowish
			break;

			case 'notice':
			case 'debug':
			case E_NOTICE:
			case E_USER_NOTICE:
			case E_STRICT:
			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$background_color = '#BBBBBB'; // grayish
			break;

			default:
				$background_color = '#888888'; // dark-grayish
			break;
		}
		?>
		<div class="tx_smallest" style="margin:1px; padding:4px; background-color:<?php echo $background_color; ?>; color:#000000; font-family:Verdana, Helvetica, Arial, Geneva, sans-serif;">
			<?php echo $message; ?>
		</div>
		<?php
	}

	public static function writeLogEntry ( $log_entry ) {
		$log_entry = trim( $log_entry );
		if ( !empty( ETC_LOG_FILE ) ) {
			file_put_contents( ETC_LOG_FILE, $log_entry."\r\n", FILE_APPEND );
		}
	}

	public static function registerLog ( $type, $message, $caller_stack_position = 0, $override_info = array() ) {
		global $user_id;
		//$message = trim( str_replace( array( "\r\n", "\r", "\n" ), ' ', $message ) ); // remove line breaks and then trim()
		$message = trim( $message ); // just trim()
		$log_entry = '['.date( 'Y-m-d H:i:s' ).'] ['.$_SERVER['REMOTE_ADDR'].']';
		$log_entry .= empty( $user_id ) ? ' [NO_USER]' : ' [USER#'.$user_id.']';
		switch ( strtolower( $type ) ) {
			case 'error':
				$log_entry .= ' [ERROR]';
			break;
			case 'warning':
				$log_entry .= ' [WARNING]';
			break;
			case 'notice':
				$log_entry .= ' [NOTICE]';
			break;
			case 'debug':
				$log_entry .= ' [DEBUG]';
			break;
			case E_ERROR:				// 1
			case E_PARSE:				// 4
			case E_CORE_ERROR:			// 16
			case E_COMPILE_ERROR:		// 64
			case E_USER_ERROR:			// 256
				$log_entry .= ' [PHP_ERROR]';
			break;
			case E_WARNING:				// 2
			case E_CORE_WARNING:		// 32
			case E_COMPILE_WARNING:		// 128
			case E_USER_WARNING:		// 512
			case E_RECOVERABLE_ERROR:	// 4096
				$log_entry .= ' [PHP_WARNING]';
			break;
			case E_NOTICE:				// 8
			case E_USER_NOTICE:			// 1024
			case E_STRICT:				// 2048
			case E_DEPRECATED:			// 8192
			case E_USER_DEPRECATED:		// 16384
				$log_entry .= ' [PHP_NOTICE]';
			break;
			default:
				$log_entry .= ' [UNSPECIFIED]';
			break;
		}
		$backtrace = debug_backtrace();
		$caller = $caller_stack_position >= count( $backtrace ) ? end( $backtrace ) : $backtrace[$caller_stack_position];
		$log_entry .= ( empty( $caller['file'] ) || empty( $caller['line'] ) ) ? ' ['.$override_info['errfile'].':'.$override_info['errline'].']' : ' ['.$caller['file'].':'.$caller['line'].']';
		$log_entry .= empty( $message ) ? ' (UNSPECIFIED)' : ' '.$message;
		if ( ETC_DEBUG_MODE === TRUE ) {
			self::printHTMLMessage( $log_entry, $type );
		}
		self::writeLogEntry( $log_entry );
	}

	public function isErrorIgnorable ( $errno, $errstr ) {
		$ignorable_errstr_array = array( 'DOMDocument::loadHTML()', 'file_get_contents', 'get_headers' );
		foreach ( $ignorable_errstr_array as $ignorable_errstr ) {
			if ( strpos( $errstr, $ignorable_errstr ) === 0 ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	// bool handler ( int $errno, string $errstr [, string $errfile [, int $errline [, array $errcontext ]]] )
	public function errorHandler ( $errno, $errstr, $errfile, $errline, $errcontext ) {
		if ( function_exists( 'error_clear_last' ) ) { // introduced in PHP 7
			error_clear_last();
		}
		if ( $this->isErrorIgnorable( $errno, $errstr ) === TRUE ) {
			return TRUE;
		}
		// "default" PHP message structure: "$errstr in $errfile on line $errline"
		$this->registerLog( $errno, $errstr, 1, array( 'errfile' => $errfile, 'errline' => $errline ) );
		return ( $this->debug === TRUE ) ? FALSE : TRUE; // if the custom error handler returns FALSE, then the default error handler is also called afterwards
	}

	public function shutdownHandler () {
		$last_error = error_get_last();
		if ( function_exists( 'error_clear_last' ) ) { // introduced in PHP 7
			error_clear_last();
		}
		if ( empty( $last_error ) || ( $last_error['type'] !== E_ERROR ) || ( $this->isErrorIgnorable( $last_error['type'], $last_error['message'] ) === TRUE ) ) {
			return;
		}
		$this->registerLog( $last_error['type'], $last_error['message'], 1, array( 'errfile' => $last_error['file'], 'errline' => $last_error['line'] ) );
	}
	// ERROR HANDLER AND REPORTING : END
	//================================================================================================================================

	public static function getHTTPRequestFinalStatus ( $url, $timeout = 0.1 ) {
		// $timeout 0.1 should be enough for intranet pages, 0.3 otherwise
		// HELPFUL WEBSITE: http://httpstat.us
		$default_context_options = stream_context_get_options( stream_context_get_default() );
		stream_context_set_default( array( 'http' => array( 'method' => 'HEAD', 'timeout' => $timeout ) ) );
		$headers = @get_headers( $url, TRUE );
		if ( $headers === FALSE ) {
			$final_status = FALSE;
		} else {
			$final_status = $headers[max( array_filter( array_keys( $headers ), 'is_int' ) )];
		}
		// http://stackoverflow.com/questions/8429342/php-get-headers-set-temporary-stream-context/21148552#21148552
		stream_context_set_default( $default_context_options ); // does not work when $default_context_options is an empty array!
		return $final_status;
	}

	public static function getHTTPRequestFinalStatusCode ( $url, $timeout = 0.1 ) {
		$final_status = self::getHTTPRequestFinalStatus( $url, $timeout );
		if ( $final_status === FALSE ) {
			$final_status_code = FALSE;
		} else {
			$final_status_code = explode( ' ', $final_status, 3 );
			if ( count( $final_status_code ) < 2 ) {
				$final_status_code = FALSE;
			} else {
				$final_status_code = intval( $final_status_code[1], 10 );
				if ( ( $final_status_code < 100 ) || ( $final_status_code > 999 ) ) {
					$final_status_code = FALSE;
				}
			}
		}
		return $final_status_code;
	}

	public static function isHTTPResourceAvailable ( $url, $timeout = 0.1 ) {
		$final_status_code = self::getHTTPRequestFinalStatusCode( $url, $timeout );
		if ( $final_status_code === FALSE ) {
			return FALSE;
		} else if ( $final_status_code >= 400 ) {
			return FALSE;
		}
		return TRUE;
	}

}

?>
