<?php

/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ PHP CONFIGURATION - START ▼▼▼▼▼▼▼▼▼▼▼
*/

// PHP VERSION CHECK
/*
[PHP 5.3]
	NEW: __DIR__ === dirname( __FILE__ )
	NEW: "static::" late static binding, in addition to "self::" which uses the class where the method is defined
[PHP 5.5]
	ALLOWS: empty( value/expression ), in addition of only empty( $variable )
[PHP 5.6]
	ALLOWS: class const array(), in addition to other value types for const
*/
if ( version_compare( PHP_VERSION, '5.0.0', '<' ) ) {
	exit( 'This system requires PHP version 5 or newer, but you are using version '.PHP_VERSION );
}

// PHP EXTENSIONS CHECK
$extensions_required = array( 'ctype', 'date', 'json', 'mbstring', 'PDO', 'Reflection', 'session' );
$extensions_not_loaded = array_diff( $extensions_required, get_loaded_extensions() );
if ( !empty( $extensions_not_loaded ) ) {
	exit( 'This system requires loading the following PHP extensions: '.implode( $extensions_not_loaded, ', ' ) );
}

// PHP ENCODING CONFIGURATION
$encoding = 'UTF-8';
mb_http_output( $encoding );
mb_detect_order( $encoding );
mb_regex_encoding( $encoding );
mb_internal_encoding( $encoding );
mb_language( 'uni' ); // 'neutral' or 'uni'
/*
http://php.net/manual/en/function.setlocale.php
try different possible locale names for german
$loc_de = setlocale(LC_ALL, 'de_DE@euro', 'de_DE', 'deu_deu');
echo "Preferred locale for german on this system is '$loc_de'";
*/

// PHP TIMEZONE
date_default_timezone_set( 'UTC' ); // date_default_timezone_get()

// PHP SESSION PARAMETERS
$session_timeout						= 360 * 60; // seconds of inactivity to disconnect the user
$session_timeout_warning				= 20 * 60; // seconds before timeout to warn the user
session_set_cookie_params( $session_timeout );
session_cache_limiter( 'nocache' );
ini_set( 'session.cache_limiter',		'nocache' );
ini_set( 'session.use_trans_sid',		'0' );
ini_set( 'session.use_cookies',			'1' );
ini_set( 'session.use_only_cookies',	'1' );
ini_set( 'session.gc_probability',		'0' );
ini_set( 'session.gc_divisor',			'100' );
ini_set( 'session.gc_maxlifetime',		''.$session_timeout );
ini_set( 'session.cookie_lifetime',		''.$session_timeout );
//session_save_path( '/tmp' );

// PHP DEBUG MODE
if ( !defined( 'ETC_DEBUG_MODE' ) ) { define( 'ETC_DEBUG_MODE', TRUE ); }
if ( ETC_DEBUG_MODE === TRUE ) {
	// enables (almost) all error reporting
	//error_reporting( E_ALL & (~E_STRICT) & (~E_DEPRECATED) ); // E_ALL should be an alias for something like ~0 or 2147483647 (check differences of specific PHP versions)
	error_reporting( ~0 ); // E_ALL should be an alias for something like ~0 or 2147483647 (check differences of specific PHP versions)
	ini_set( 'display_errors ',			'1' );
	ini_set( 'display_startup_errors',	'1' );
} else {
	// disables all error reporting
	error_reporting( 0 );
	ini_set( 'display_errors ',			'0' );
	ini_set( 'display_startup_errors',	'0' );
}

// END AND CLEAN THE OUTPUT BUFFER
while ( count( ob_get_status( TRUE ) ) > 0 ) ob_end_clean();

/*
▲▲▲ PHP CONFIGURATION - END ▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ DATABASE CONFIGURATION - START ▼▼▼▼▼▼
*/

switch ( 'DEV' ) {
	case 'DEV':
		//$CREDENTIALS = require_once( '/export/var/www/project/includes/credentials.inc.php' );
	break;
}

/*
▲▲▲ DATABASE CONFIGURATION - END ▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ ETC CONFIGURATION - START ▼▼▼▼▼▼▼▼▼▼▼
*/

// ETC configuration file load and creation START
function _getProtocol () {
	switch ( TRUE ) {
		case ( !empty( $_SERVER['HTTPS'] ) && ( strtolower( $_SERVER['HTTPS'] ) !== 'off' ) ):
		case ( !empty( $_SERVER['REQUEST_SCHEME'] ) && ( strtolower( $_SERVER['REQUEST_SCHEME'] === 'https' ) ) ):
		case ( !empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && ( strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) ) ):
		case ( !empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && ( strtolower( $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on' ) ) ):
		case ( !empty( $_SERVER['SERVER_PORT'] ) && ( intval( $_SERVER['SERVER_PORT'], 10 ) === getservbyname( 'https', 'tcp' ) ) ):
		// RFC7239: $_SERVER['HTTP_FORWARDED'] (string must be parsed to extract data)
			return 'https';
		break;
		default:
			return 'http';
		break;
	}
}
function _isDefinedRequiredDefines ( $required_defines ) {
	foreach ( $required_defines as $required_define ) {
		if ( !defined( $required_define ) ) {
			return FALSE;
		}
	}
	return TRUE;
}
function _isValidRequiredDefines ( $override_defines = array() ) {
	// TODO test other defines as well in addition to ETC_BASE_DIR
	if ( !empty( $override_defines ) ) {
		return is_readable( $override_defines['ETC_BASE_DIR'].basename( __FILE__ ) );
	} else {
		return is_readable( ETC_BASE_DIR.basename( __FILE__ ) );
	}
}
function _loadConfigFile ( $config_file ) {
	$required_defines = array( 'ETC_BASE_DIR', 'ETC_LOG_FILE', 'ETC_BASE_URL',  'ETC_LOGGED_PAGE', 'REWRITE_MODULE_ENABLED' );
	if ( _isDefinedRequiredDefines( $required_defines ) === TRUE ) {
		if ( _isValidRequiredDefines() === FALSE ) {
			exit( 'ERROR: required constants were not properly defined' );
		}
	} else {
		if ( !is_readable( $config_file ) ) {
			$config_file_array = _createConfigFile( $config_file );
		} else {
			$config_file_array = require_once( $config_file );
			if ( !is_array( $config_file_array ) ) {
				$config_file_array = _createConfigFile( $config_file );
			} else {
				$config_file_array = array_intersect_key( $config_file_array, array_flip( $required_defines ) ); // config_file filtering (ignores invalid elements)
				if ( ( count( $config_file_array ) !== count( $required_defines ) ) || ( _isValidRequiredDefines( $config_file_array ) === FALSE ) ) {
					$config_file_array = _createConfigFile( $config_file );
				}
			}
		}
		foreach ( $config_file_array as $key => $value ) {
			if ( !defined( $key ) ) {
				define( $key, $value );
			}
		}
	}
}
function _createConfigFile ( $config_file ) { // creates (and overwrites) configuration file
	if ( ( file_exists( $config_file ) && !is_writable( $config_file ) ) || !is_writable( dirname( $config_file ) ) ) {
		// configuration file cannot be overwrited or directory has no write permission
		exit( 'ERROR: configuration file cannot be created or edited' );
	}
	$config_file_array = array();
	$separators = '\\\/\ '.DIRECTORY_SEPARATOR;

	// ETC root location in the file system (ie Windows:'X:\Server\www\ETC\' or Linux:'/Server/www/ETC/')
	$config_file_array['ETC_BASE_DIR'] = rtrim( dirname( __FILE__ ), $separators ).DIRECTORY_SEPARATOR;
	// ETC log file location in the file system
	$config_file_array['ETC_LOG_FILE'] = $config_file_array['ETC_BASE_DIR'].'log.txt';
	// ETC root location in the web server (ie 'http://localhost/ETC/')
	$document_root_relative_script_path = trim( dirname( $_SERVER['SCRIPT_NAME'] ), $separators );
	if ( !empty( $document_root_relative_script_path ) ) {
		$document_root_relative_script_path = ( $document_root_relative_script_path === '.' ) ? '' : $document_root_relative_script_path.'/';
	}
	$config_file_array['ETC_BASE_URL'] = _getProtocol().'://'.trim( $_SERVER['HTTP_HOST'], $separators ).'/'.$document_root_relative_script_path;
	// ETC redirect page after login
	$config_file_array['ETC_LOGGED_PAGE'] = $config_file_array['ETC_BASE_URL'].'notifications/';

	// Apache's rewrite_module detection, used to beautify URL parameters
	// $config_file_array['REWRITE_MODULE_ENABLED'] = ( strtolower( getenv( 'HTTP_REWRITE_MODULE' ) ) === 'on' ) ? TRUE : FALSE;
	$config_file_array['REWRITE_MODULE_ENABLED'] = ( 'SUCCESS' === @file_get_contents( ETC_BASE_URL.'includes/checkfiles/apache_rewrite_module.test', FALSE, NULL, 0, 7 ) ) ? TRUE : FALSE;

	// recursively escapes $config_file_array values by adding backslashes
	array_walk_recursive( $config_file_array, function ( &$value, $key ) { $value = addslashes( $value ); } );

	file_put_contents( $config_file,
		'<?php'."\n".
		'//'.date( 'Y-m-d H:i:s' )."\n".
		'return array('."\n".
		'	\'ETC_BASE_DIR\'		=> \''.$config_file_array['ETC_BASE_DIR'].'\','."\n".
		'	\'ETC_LOG_FILE\'		=> \''.$config_file_array['ETC_LOG_FILE'].'\','."\n".
		'	\'ETC_BASE_URL\'		=> \''.$config_file_array['ETC_BASE_URL'].'\','."\n".
		'	\'ETC_LOGGED_PAGE\'	=> \''.$config_file_array['ETC_LOGGED_PAGE'].'\','."\n".
		'	\'REWRITE_MODULE_ENABLED\' => '.( ( $config_file_array['REWRITE_MODULE_ENABLED'] === TRUE ) ? 'TRUE' : 'FALSE' )."\n".
		');'."\n".
		'?>'."\n" );

	return $config_file_array;
}
_loadConfigFile( dirname( __FILE__ ).DIRECTORY_SEPARATOR.'_config.php' );

// ETC configuration file load and creation END
//-------------------------------------------------------------------------------------------------
// ETC misc configuration START

if ( !defined( 'ETC_PASS_LENGTH_MIN' ) )	{ define( 'ETC_PASS_LENGTH_MIN', 6 ); }
if ( !defined( 'ETC_EMAIL' ) )				{ define( 'ETC_EMAIL', 'email@project.com' ); }
$ETC_LANGUAGE_CODES							= array( 'PT-BR', 'EN-US' ); // first position in array will be used as default language

// ETC misc configuration END
//-------------------------------------------------------------------------------------------------

/*
▲▲▲ ETC CONFIGURATION - END ▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ INCLUDES - START ▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼
*/

require_once( ETC_BASE_DIR.'includes/php/general_purpose.inc.php' );
require_once( ETC_BASE_DIR.'includes/php/class_basic.inc.php' );
require_once( ETC_BASE_DIR.'includes/php/random_compat/lib/random.php' );

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'ETCAutoloadClasses' );
} else { // fall back to __autoload for older PHP versions ( http://php.net/manual/en/function.autoload.php )
	function __autoload ( $classname ) {
		ETCAutoloadClasses( $classname );
	}
}

function ETCAutoloadClasses ( $classname ) {
	switch ( $classname ) {
		case 'Comments':		require_once( ETC_BASE_DIR.'includes/php/comments.inc.php' ); break;
		case 'Communicator':	require_once( ETC_BASE_DIR.'includes/php/communicator.inc.php' ); break;
		case 'ETC_User':		require_once( ETC_BASE_DIR.'includes/php/etc_user.inc.php' ); break;
		case 'Events':			require_once( ETC_BASE_DIR.'includes/php/events.inc.php' ); break;
		case 'HTMLPage':		require_once( ETC_BASE_DIR.'includes/php/htmlpage.inc.php' ); break;
		case 'PDOHandler':		require_once( ETC_BASE_DIR.'includes/php/class_pdo.inc.php' ); break;
		case 'Reports':			require_once( ETC_BASE_DIR.'includes/php/reports.inc.php' ); break;
		case 'Session':			require_once( ETC_BASE_DIR.'includes/php/session.inc.php' ); break;
		case 'TextHistory':		require_once( ETC_BASE_DIR.'includes/php/text_history.inc.php' ); break;
		case 'Token':			require_once( ETC_BASE_DIR.'includes/php/token.inc.php' ); break;
		case 'Translator':		require_once( ETC_BASE_DIR.'includes/php/translator/translator.inc.php' ); break;
		case 'XHTML_Input':		require_once( ETC_BASE_DIR.'includes/php/xhtml_elements.inc.php' ); break;
		case 'XHTML_CheckBox':	require_once( ETC_BASE_DIR.'includes/php/xhtml_elements.inc.php' ); break;
		default:
			$possibilities = array( ETC_BASE_DIR.'includes/php/'.$classname.'.inc.php',
									ETC_BASE_DIR.'includes/php/'.strtolower( $classname ).'.inc.php',
									ETC_BASE_DIR.'includes/php/class_'.$classname.'.inc.php',
									ETC_BASE_DIR.'includes/php/class_'.strtolower( $classname ).'.inc.php' );
			for ( $i = 0; ( $i < count( $possibilities ) ) && !is_readable( $possibilities[$i] ); ++$i );
			if ( $i === count( $possibilities ) ) {
				// ERROR: required class could not be found and/or loaded (LOG THIS) TODO TO DO TO-DO
			} else {
				require_once( $possibilities[$i] );
			}
		break;
	}
}

$HTMLPurifier_OK = FALSE;
function Create_HTMLPurifier () {
	global $HTMLPurifier_config, $HTMLPurifier, $HTMLPurifier_OK;
	if ( !$HTMLPurifier_OK ) {
		require_once( ETC_BASE_DIR.'includes/php/HTMLPurifier/HTMLPurifier.standalone.php' );
		$HTMLPurifier_config = HTMLPurifier_Config::createDefault();
		$HTMLPurifier_config->set( 'Cache.DefinitionImpl', NULL ); // disables caching
		$HTMLPurifier_config->set( 'Core.Encoding', 'UTF-8' );
		$HTMLPurifier_config->set( 'HTML.Doctype', 'HTML 4.01 Transitional' );

		$def = $HTMLPurifier_config->getHTMLDefinition( TRUE );
		$def->addAttribute( 'a', 'target', 'Enum#_blank,_self,_target,_top' );
		$def->addAttribute( 'a', 'contenteditable', 'Enum#true,false,' );
		$def->addAttribute( 'span', 'id', 'Text' );
		$def->addAttribute( 'span', 'onclick', 'Text' );

		$HTMLPurifier = new HTMLPurifier( $HTMLPurifier_config );
		$HTMLPurifier_OK = TRUE;
		//$clean_html = $HTMLPurifier->purify( 'dirty html string here' );
	}
}

/*
▲▲▲ INCLUDES - END ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ INITIALIZATIONS - START ▼▼▼▼▼▼▼▼▼▼▼▼▼
*/

// wipe session? (debug)
if ( isset( $_GET['wipe'] ) ) {
	Session::destroy( 'ETC' );
	setHeaderLocation( ETC_BASE_URL );
}

// create database connection handler
//$pdo_handler = new PDOHandler( array( 'host' => $db_server, 'dbname' => $db_name, 'charset' => 'utf8', 'username' => $db_user, 'password' => $db_pass ) );

// starts or resumes the user session
$session = Session::getSession( 'ETC' ); // TODO TO-DO : MUST RECREATE THE REFLECTION OBJECT EVERYTIME BECAUSE IT IS SPECIAL AND IS NOT SAVED/RESTORED. YES. REDO IT EVERYTIME.

// check if the session is ok and redirects when necessary
//$session->check();
if ( !empty( $_GET['setLang'] ) ) {
	$session->setCurrentLanguageByCode( $_GET['setLang'] );
}

// translator object
//$translator = new Translator( $session->getProperty( 'currentLanguageCode' ) );
$translator = new Translator( $ETC_LANGUAGE_CODES[0] );

// get current user data from session
//$user = $session->getCurrentUser();
if ( empty( $user ) ) {
	$user_id = 0;
} else {
	if ( !empty( $_GET['setLang'] ) ) {
		$user->setLanguageByCode( $_GET['setLang'] );
	}

	$user_id			= $user->getProperty( 'ID' );
	$user_name			= $user->getProperty( 'name' );
	$user_email			= $user->getProperty( 'email' );
	$user_avatar		= ETC_BASE_URL.'archive/avatar/'.$user->getProperty( 'avatar' );
	$user_settings		= $user->getProperty( 'settings' );
	$user_languageCode	= $user->getProperty( 'languageCode' );

	$selectedType		= $user->getProperty( 'selectedType' ); // "folder" or "text"
	$selectedFolderID	= $user->getProperty( 'selectedFolderID' );
	$selectedTextsIDs	= array_keys( $user->getProperty( 'selectedTextsIDs' ) );
}

// GET parameters
switch ( TRUE ) {
	case !empty( $_GET['folderID'] ):	$currentFolderID = intval( $_GET['folderID'], 10 );
	break;
	case !empty( $_GET['pasta'] ):		$currentFolderID = intval( $_GET['pasta'], 10 );
	break;
	case !empty( $selectedFolderID ):	$currentFolderID = $selectedFolderID;
	break;
	default:							$currentFolderID = 1;
}

switch ( TRUE ) {
	case !empty( $_GET['textID'] ):		$currentTextID = intval( $_GET['textID'], 10 );
	break;
	case !empty( $_GET['texto'] ):		$currentTextID = intval( $_GET['texto'], 10 );
	break;
	case !empty( $_GET['textoID'] ):	$currentTextID = intval( $_GET['textoID'], 10 );
	break;
	case !empty( $_GET['texto_id'] ):	$currentTextID = intval( $_GET['texto_id'], 10 );
	break;
	default:							$currentTextID = 0;
}
$currentFolderID_OK = FALSE;
$currentTextID_OK = FALSE;

function Check_currentFolderID () {
	global $pdo_handler, $currentFolderID, $user_id, $currentFolderID_OK;
	if ( !$currentFolderID_OK ) {
		if ( $pdo_handler->query_user_can_access_object_id( 'folder', $currentFolderID, $user_id ) === FALSE ) {
			$currentFolderID = 1;
		}
		$currentFolderID_OK = TRUE;
	}
}

function Check_currentTextID () {
	global $pdo_handler, $currentTextID, $user_id, $currentTextID_OK;
	if ( !$currentTextID_OK ) {
		if ( $pdo_handler->query_user_can_access_object_id( 'text', $currentTextID, $user_id ) === FALSE ) {
			$currentTextID = 0;
		}
		$currentTextID_OK = TRUE;
	}
}

/*
▲▲▲ INITIALIZATIONS - END ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
*/

?>
