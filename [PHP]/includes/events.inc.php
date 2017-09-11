<?php

class Events extends BasicClass {

	/* [ PROPERTIES ] */

	// combination of valid function-action; actions' keys may contain non-numeric characters denoting a special behaviour or requirement of that action
	// key containing "!": do not allow defining affected users instead of requiring it
	protected static $combinations = array(
		'account'	=> array( '!' => 'login' ),
		'folder'	=> array( 'remove_user', 'add_user', 'edit', 'delete', 'communicator_alert' ),
		'text'		=> array( '!' => 'access', 'remove_user', 'add_user', 'edit', 'delete', 'communicator_alert', 'comment' ),
		'library'	=> array( 'delete', 'add', 'edit' ),
		'forum'		=> array( 'create', 'reply' ),
		'message'	=> array( 'send' )
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

	protected function error ( $message = '' ) {
		$this->registerLog( 'error', $message, 2 );
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


	/* [ PUBLIC METHODS ] */

	public static function getActionsOfFunction ( $function ) {
		return empty( self::$combinations[$function] ) ? array() : self::$combinations[$function];
	}

	public static function isValidFunction ( $function ) {
		return !empty( self::$combinations[$function] );
	}

	public static function isValidCombination ( $function, $action ) {
		return ( !empty( self::$combinations[$function] ) && in_array( $action, self::$combinations[$function], TRUE ) );
	}


	/* [ CRUD ] */

	public static function create ( $function, $action, $author_user_id, $affected_object_id, $affected_user_ids = array() ) {
		global $pdo_handler;

		if ( self::isValidCombination( $function, $action ) === FALSE ) {
			self::registerLog( 'error', __METHOD__.'() parameter $function and $action are not a valid combination', 1 );
		}
		if ( empty( $author_user_id ) ) {
			self::registerLog( 'error', __METHOD__.'() parameter $author_user_id is empty', 1 );
		}
		if ( !is_array( $affected_user_ids ) ) {
			self::registerLog( 'error', __METHOD__.'() parameter $affected_user_ids is not an array', 1 );
		} else {
			$action_key = array_search( $action, self::$combinations[$function] );
			if ( ( strpos( $action_key, '!' ) !== FALSE ) && !empty( $affected_user_ids ) ) { // [ERROR] action's key contains "!" and $affected_user_ids is not empty
				self::registerLog( 'error', __METHOD__.'() passed combination requires empty $affected_user_ids', 1 );
			}
		}

		$inserted_id = $pdo_handler->query( 'INSERT INTO `etc_events` ( `ip`, `function`, `action`, `author_user_id`, `affected_object_id` )
											VALUES( :ip, :function, :action, :author_user_id, :affected_object_id );',
											array( ':ip' => $_SERVER['REMOTE_ADDR'],
													':function' => $function,
													':action' => $action,
													':author_user_id' => $author_user_id,
													':affected_object_id' => $affected_object_id ) );
		$affected_user_ids = array_filter( $affected_user_ids ); // prevents duplicates
		foreach ( $affected_user_ids as $affected_user_id ) {
			$pdo_handler->query( 'INSERT INTO `etc_events_affected_users` ( `event_id`, `affected_user_id` )
									VALUES( :inserted_id, :affected_user_id );',
									array( ':inserted_id' => $inserted_id,
											':affected_user_id' => $affected_user_id ) );
		}

		return $inserted_id;
	}

	public static function read () {}

	public static function update () {}

	public static function delete () {}
}

?>
