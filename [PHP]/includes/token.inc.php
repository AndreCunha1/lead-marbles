<?php

class Token extends BasicClass {

	/* [ PROPERTIES ] */

	// combination of valid function-action; actions' keys may contain non-numeric characters denoting a special behaviour or requirement of that action
	protected static $combinations = array(
		'account' => array( 'password_change', 'password_create', 'password_recover' )
	);


	/* [ CONSTRUCTOR & DESTRUCTOR ] */

	public function __construct () {
		parent::__construct( get_class( $this ) );
		//array_unshift( $this->properties_to_set, 'ID', 'name' );
	}

	public function __destruct () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* [ PROTECTED METHODS ] */

	protected static function error ( $message = '' ) {
		self::registerLog( 'error', $message, 2 );
		exit;
	}

	protected static function table () {
		global $pdo_handler;
		/*
		$pdo_handler->query( "
			DROP TABLE IF EXISTS `etc_events`;
		" );
		*/
		/*
		$pdo_handler->query( "
			CREATE TABLE IF NOT EXISTS `etc_events` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`function` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
				`action` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
				`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`author_user_id` int(10) unsigned NOT NULL DEFAULT '0',
				`affected_object_id` int(10) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
		" );
		*/

		/*
		$pdo_handler->query( "
			DROP TABLE IF EXISTS `etc_events_affected_users`;
		" );
		*/
		/*
		$pdo_handler->query( "
			CREATE TABLE IF NOT EXISTS `etc_events_affected_users` (
				`event_id` int(10) unsigned NOT NULL DEFAULT '0',
				`affected_user_id` int(10) unsigned NOT NULL DEFAULT '0',
				`seen_by_affected_user` tinyint(1) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`event_id`,`affected_user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		" );
		*/
	}

	protected static function deleteOldEntries () {
		/*
		global $pdo_handler;
		$pdo_handler->query( 'DELETE FROM `etc_events`
								WHERE `timestamp` < DATE_SUB( NOW(), INTERVAL 6 MONTH );' );
		*/
	}

	protected static function isValidFunction ( $function ) {
		return !empty( self::$combinations[$function] );
	}

	protected static function isValidCombination ( $function, $action ) {
		return ( !empty( self::$combinations[$function] ) && in_array( $action, self::$combinations[$function], TRUE ) );
	}

	protected static function receiveToken () {
		if ( empty( $_GET ) || ( count( $_GET ) !== 1 ) ) {
			// ERROR: invalid number of GET parameters
		} else {
			$keys = array_keys( $_GET );
			if ( ( count( $keys ) !== 1 ) || !empty( $_GET[$keys[0]] ) || ( $_SERVER['QUERY_STRING'] !== $keys[0] ) ) {
				// ERROR: parameter has value OR query string has aditional characters
			} else {
				return $keys[0];
			}
		}
		return FALSE;
	}


	/* [ PUBLIC METHODS ] */

	public static function getActionsOfFunction ( $function ) {
		return empty( self::$combinations[$function] ) ? array() : self::$combinations[$function];
	}

	public static function getTokenData () {
		$token = self::receiveToken();
		if ( empty( $token ) || !is_string( $token ) ) { return array(); }
		return self::read( $token );
	}

	public static function getTokenDataStatus ( $tokenData ) {
		if ( empty( $tokenData ) || !is_array( $tokenData ) )								{ return 'INVALID'; }
		if ( !empty( $tokenData['expired'] ) )												{ return 'EXPIRED'; }
		if ( !empty( $tokenData['used_ip'] ) || !empty( $tokenData['used_timestamp'] ) )	{ return 'USED'; }
		return 'VALID';
	}

	public static function getAmbientState () {
		if ( self::receiveToken() === FALSE )	{ return 'INVALID'; } // no token received, should do nothing
		else if ( empty( $_POST ) )				{ return 'PROMPT'; } // token received without _POST, should prompt for action
		else									{ return 'USE'; } // token received along with _POST, should try to use the token
	}

	public static function act () {
		$tokenData = self::getTokenData();
		switch ( self::getAmbientState() ) {
			default:
			case 'INVALID':
				/*
				¯\_(ツ)_/¯
				*/
				echo '¯\_(ツ)_/¯';
			break;

			case 'PROMPT':
				self::printHTMLForm( $tokenData );
			break;

			case 'USE':
				self::apply( $tokenData );
			break;
		}
	}

	public static function apply ( $tokenData ) {
		global $pdo_handler;
		switch ( self::getTokenDataStatus( $tokenData ) ) {
			default:
			case 'INVALID':
			case 'EXPIRED':
			case 'USED':
				?>
				<p>
					Token inválido (<?php echo self::getTokenDataStatus( $tokenData ); ?>)<br />
					Caso necessário, gere outro através do sistema.
				</p>
				<?php
			break;
			case 'VALID':
				self::update( $tokenData['token'] ); // token voiding
				switch ( $tokenData['function'] ) {
					case 'account':
						switch ( $tokenData['action'] ) {
							case 'password_change':
							case 'password_create':
							case 'password_recover':
								// TODO TO DO TO-DO: change this to call the class method responsible for changing passwords (maybe it does not even exist)
								$pdo_handler->query( 'UPDATE `etc_users`
														SET `password` = :password
														WHERE `user_id` = :user_id',
														array( ':password' => hashPassword( $_POST['password'] ),
																':user_id' => $tokenData['affected_object_id'] ) );
							break;
						}
					break;
				}
			break;
		}
	}


	/* [ CRUD ] */

	public static function create ( $function, $action, $author_user_id, $affected_object_id ) {
		global $pdo_handler;
		if ( self::isValidCombination( $function, $action ) === FALSE ) {
			self::error( __METHOD__.'() parameter $function and $action are not a valid combination' );
		}
		/*
		TODO TO DO TO-DO: CHECK WETHER author_user_id CAN BE EMPTY
		if ( empty( $author_user_id ) ) {
			self::error( __METHOD__.'() parameter $author_user_id is empty' );
		}
		*/

		$token = random_string( 32 );
		$inserted_id = $pdo_handler->query( 'INSERT INTO `etc_tokens` ( `function`, `action`, `author_user_id`, `affected_object_id`, `token` )
											VALUES( :function, :action, :author_user_id, :affected_object_id, :token );',
											array( ':function' => $function,
													':action' => $action,
													':author_user_id' => $author_user_id,
													':affected_object_id' => $affected_object_id,
													':token' => $token ) );
		return $token;
	}

	public static function read ( $token ) {
		global $pdo_handler;
		$results = $pdo_handler->query( 'SELECT *, ( `timestamp` < DATE_SUB( NOW(), INTERVAL 1 YEAR ) ) AS `expired`
										FROM `etc_tokens`
										WHERE `token` = BINARY :token;',
										array( ':token' => $token ) );
		if ( empty( $results ) )		{ return array(); }
		if ( count( $results ) !== 1 )	{ self::error( __METHOD__.'() token collision' ); }
		return $results[0];
	}

	public static function update ( $token ) {
		global $pdo_handler;
		// token voiding, setting the IP of the device using it (DB should also register the timestamp with "ON UPDATE CURRENT_TIMESTAMP")
		$pdo_handler->query( 'UPDATE `etc_tokens`
								SET `used_ip` = :used_ip
								WHERE `token` = BINARY :token;',
								array( ':used_ip' => $_SERVER['REMOTE_ADDR'],
										':token' => $token ) );
	}

	public static function delete () { /* ¯\_(ツ)_/¯ */ }


	/* [ HTML ] */

	protected static function printHTMLForm ( $tokenData ) {
		switch ( self::getTokenDataStatus( $tokenData ) ) {
			default:
			case 'INVALID':
			case 'EXPIRED':
			case 'USED':
				?>
				<p>
					Token inválido (<?php echo self::getTokenDataStatus( $tokenData ); ?>)<br />
					Caso necessário, gere outro através do sistema.
				</p>
				<?php
			break;
			case 'VALID':
				switch ( $tokenData['function'] ) {
					case 'account':
						switch ( $tokenData['action'] ) {
							case 'password_change':
							case 'password_create':
							case 'password_recover':
								?>
								<!-- TODO TO DO TO-DO autocomplete="off" autocomplete="nope" autocomplete="new-password" are UNRELIABLE -->
								<form method="post" action="<?php echo ETC_BASE_URL; ?>token.php?<?php echo $tokenData['token']; ?>" autocomplete="off">
									<label>Nova senha:<input name="password" type="password" autocomplete="off" /></label>
								</form>
								<?php
							break;
						}
					break;
				}
			break;
		}
	}
}

?>
