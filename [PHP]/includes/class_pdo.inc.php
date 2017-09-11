<?php

class PDOHandler extends BasicClass {

	/* [ PROPERTIES ] */

	// http://php.net/manual/en/pdo.drivers.php
	// IMPORTANT: precede optional arguments with "?"
	protected static $driver_arguments = array(
		'cubrid'	=> array( 'host', 'port', 'dbname' ),
		'sybase'	=> array( 'host', 'dbname', 'charset', 'appname', 'secure' ),
		'mssql'		=> array( 'host', 'dbname', 'charset', 'appname', 'secure' ),
		'dblib'		=> array( 'host', 'dbname', 'charset', 'appname', 'secure' ),
		'firebird'	=> array( 'dbname', 'charset', 'role' ),
		'ibm'		=> array( 'DATABASE', 'HOSTNAME', 'PORT', 'PROTOCOL', 'UID', 'PWD' ),
		'informix'	=> array( 'host', 'service', 'database', 'server', 'protocol', 'EnableScrollableCursors' ),
		'mysql'		=> array( 'host', '?port', 'dbname', '?unix_socket', 'charset' ),
		'sqlsrv'	=> array( 'APP', 'ConnectionPooling', 'Database', 'Encrypt', 'Failover_Partner', 'LoginTimeout', 'MultipleActiveResultSets', 'QuotedId', 'Server', 'TraceFile', 'TraceOn', 'TransactionIsolation', 'TrustServerCertificate', 'WSID' ),
		'oci'		=> array( 'dbname', 'charset' ),
		'odbc'		=> array( 'DSN', 'DRIVER', 'HOSTNAME', 'PORT', 'DATABASE', 'PROTOCOL', 'UID', 'PWD' ),
		'pgsql'		=> array( 'host', 'port', 'dbname', 'user', 'password' ),
		'sqlite'	=> array( 'DSN' ),
		'sqlite2'	=> array( 'DSN' ),
		'4D'		=> array( 'host', 'port', 'user', 'password', 'dbname', 'charset' )
	);

	protected $PDO;
	protected $buffer; // TODO TO-DO TO DO: global buffer (query independant) (must to check wether MYSQL_ATTR_USE_BUFFERED_QUERY or alikes is already being used to avoid duplicate buffering!)
	protected $arguments;
	protected $error_message;
	protected $error_code;
	protected $sql;
	protected $bind;
	protected $error_messageMsgFormat;
	protected $options;
	protected $pdostmt;


	/* [ CONSTRUCTOR & DESTRUCTOR ] */

	public function __construct ( $arguments = array() ) {
		parent::__construct( get_class( $this ) );

		if ( !is_array( $arguments ) ) {
			// TODO LOG THIS ERROR
		} else {
			$this->arguments = $arguments;
		}
		$this->options = array(
			PDO::ATTR_CASE => PDO::CASE_NATURAL,
			PDO::ATTR_ERRMODE => ( $this->getProperty( 'debug' ) === TRUE ) ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
			PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES => TRUE, // string return type is expected from fetch queries
			PDO::ATTR_EMULATE_PREPARES => FALSE,
			PDO::ATTR_PERSISTENT => FALSE,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::MYSQL_ATTR_USE_BUFFERED_QUERY  => TRUE,
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, '.
											'@@sql_mode = CONCAT( @@sql_mode, ",ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES" );'
											/*
											Those options are set by default since MySQL 5.7.5, so we set them for consistent execution with prior versions
											In case they must be disabled, do as follows:
											'@@sql_mode = REPLACE( @@sql_mode, "ONLY_FULL_GROUP_BY", "" ), '.
											'@@sql_mode = REPLACE( @@sql_mode, "STRICT_TRANS_TABLES", "" );'
											*/
		);
		$this->PDO = NULL;
	}

	public function __destruct () {
		$this->disconnect();
	}


	/* [ PROTECTED METHODS ] */

	protected function error ( $message = '' ) {
		$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$this->error_code.') '.$this->error_message, 2 );
		switch ( $this->error_code ) {
			case 0: // no error_message occurred
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$this->error_code.') '.$message, 2 );
				break;
			case '42000':	// 1049: // unknown database (not exists?)
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$this->error_code.') unknown requested database (not exists?). [SQL: '.$this->sql.']', 2 );
				break;
			case '42S22':	// 1054: // unknown column (not exists?)
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$this->error_code.') unknown requested column (not exists?). [SQL: '.$this->sql.']', 2 );
				break;
			case '42S02':	// 1146: // table does not exist
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$this->error_code.') requested table does not exist. [SQL: '.$this->sql.']', 2 );
				break;
			default:
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$this->error_code.') '.$this->error_message.' [SQL: '.$this->sql.']', 2 );
				break;
		}
	}

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

	// return available connectable drivers as array( 'driver' => array( 'argument' => 'value' ) )
	protected function getConnectableDrivers () {
		$available_drivers = array_intersect_key( self::$driver_arguments, array_flip( PDO::getAvailableDrivers() ) );
		$connectable_drivers = array();
		foreach ( $available_drivers as $key => $value ) {
			// filter out arguments starting with "?" (optional arguments)
			$required_driver_arguments = array_filter( $value, function ( $string ) { return ( strncmp( $string, '?', 1 ) === 0 ) ? FALSE : TRUE; } );
			if ( $required_driver_arguments === array_intersect( $required_driver_arguments, array_keys( $this->arguments ) ) ) {
				$compatible_arguments_given = array_intersect_key( $this->arguments, array_flip( array_map( function ( $string ) { return ltrim( $string, '?' ); }, $value ) ) );
				$connectable_drivers[$key] = $compatible_arguments_given;
			}
		}
		return $connectable_drivers;
	}

	protected function assembleDSN ( $driver, $arguments ) {
		$DSN = '';
		switch ( $driver ) {
			case 'cubrid':		// http://php.net/manual/en/ref.pdo-cubrid.connection.php
			case 'sybase':		// http://php.net/manual/en/ref.pdo-dblib.connection.php
			case 'mssql':		// http://php.net/manual/en/ref.pdo-dblib.connection.php
			case 'dblib':		// http://php.net/manual/en/ref.pdo-dblib.connection.php
			case 'firebird':	// http://php.net/manual/en/ref.pdo-firebird.connection.php
			case 'mysql':		// http://php.net/manual/en/ref.pdo-mysql.connection.php
			case 'sqlsrv':		// http://php.net/manual/en/ref.pdo-sqlsrv.connection.php
			case 'oci':			// http://php.net/manual/en/ref.pdo-oci.connection.php
			case 'pgsql':		// http://php.net/manual/en/ref.pdo-pgsql.connection.php
			case '4D':			// http://php.net/manual/en/ref.pdo-4d.connection.php
				$DSN = $driver.':';
				foreach ( $arguments as $key => $value ) {
					$DSN .= $key.'='.$value.';';
				}
				$DSN = rtrim( $DSN, ';' );
			break;

			case 'ibm':			// http://php.net/manual/en/ref.pdo-ibm.connection.php
			case 'odbc':		// http://php.net/manual/en/ref.pdo-odbc.connection.php
				$DSN = $driver.':';
				foreach ( $arguments as $key => $value ) {
					$DSN .= $key.'='.$value.';';
				}
			break;

			case 'informix':	// http://php.net/manual/en/ref.pdo-informix.connection.php
				$DSN = $driver.':';
				foreach ( $arguments as $key => $value ) {
					$DSN .= $key.'='.$value.'; ';
				}
				$DSN = rtrim( $DSN, '; ' );
			break;

			case 'sqlite':		// http://php.net/manual/en/ref.pdo-sqlite.connection.php
			case 'sqlite2':		// http://php.net/manual/en/ref.pdo-sqlite.connection.php
				$DSN = $driver.':';
				foreach ( $arguments as $key => $value ) {
					$DSN .= $value;
				}
			break;

			default:
				// ERROR: unable to build DSN (unknown driver)
			break;
		}
		return $DSN;
	}


	/* [ PUBLIC METHODS ] */

	public function connected () {
		if ( empty( $this->PDO ) ) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	public function connect () {
		if ( $this->connected() === FALSE ) {
			$connectable_drivers = $this->getConnectableDrivers();
			if ( empty( $connectable_drivers ) ) {
				// ERROR: no drivers available to connect, perhaps because of missing required arguments
			} else {
				try {
					switch ( TRUE ) { // available database drivers, in order of preference
						case array_key_exists( 'mysql',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'mysql',		$connectable_drivers['mysql'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'cubrid',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'cubrid',		$connectable_drivers['cubrid'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'sybase',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'sybase',		$connectable_drivers['sybase'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'mssql',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'mssql',		$connectable_drivers['mssql'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'dblib',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'dblib',		$connectable_drivers['dblib'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'firebird',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'firebird',	$connectable_drivers['firebird'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'ibm',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'ibm',		$connectable_drivers['ibm'] ),		$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'informix',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'informix',	$connectable_drivers['informix'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'sqlsrv',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'sqlsrv',		$connectable_drivers['sqlsrv'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'oci',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'oci',		$connectable_drivers['oci'] ),		$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'odbc',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'odbc',		$connectable_drivers['odbc'] ),		$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'pgsql',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'pgsql',		$connectable_drivers['pgsql'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'sqlite',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'sqlite',		$connectable_drivers['sqlite'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( 'sqlite2',	$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( 'sqlite2',	$connectable_drivers['sqlite2'] ),	$this->arguments['username'], $this->arguments['password'], $this->options ); break;
						case array_key_exists( '4D',		$connectable_drivers ): $this->PDO = new PDO( $this->assembleDSN( '4D',			$connectable_drivers['4D'] ),		$this->arguments['username'], $this->arguments['password'], $this->options ); break;

						default:
							// ERROR: driver Data Source Name (DSN) not configured
						break;
					}
				} catch ( PDOException $e ) {
					$this->error_message = $e->getMessage();
					$this->error_code = $e->getCode();
					$this->error();
					return null;
				}
			}
		}
		return $this->PDO;
	}

	public function disconnect () {
		if ( $this->connected() === TRUE ) {
			$this->PDO = NULL;
		}
		return TRUE;
	}

	public function escape ( $string ) {
		$this->connect();
		return trim( $this->PDO->quote( $string ), '\'' );
	}

	public function quote ( $string ) {
		$this->connect();
		return $this->PDO->quote( $string );
	}

	public function toHTML ( $string ) {
		return nl2br( htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
	}

	public function now () {
		$results = $this->query( 'SELECT NOW() AS `now`;' );
		return $results[0]['now'];
	}

	public function rowCount () {
		return ( $this->pdostmt instanceof PDOStatement ) ? $this->pdostmt->rowCount() : FALSE;
	}

	// Query, Return SMART! (:
	// INSERT: return inserted id
	// UPDATE: return number of affected rows
	// SELECT: associative array with all results (empty array if no results)
	public function query ( $sql, $bind = array() ) {
		if ( $this->connected() === FALSE ) {
			$this->connect();
		}
		$this->sql = trim( preg_replace( '/\s+/', ' ', $sql ) );
		$this->bind = $bind;
		try {
			$this->pdostmt = $this->PDO->prepare( $this->sql );
			if ( $this->pdostmt->execute( $this->bind ) !== FALSE ) {
				if ( preg_match( '/^(SELECT|DESCRIBE|PRAGMA) /i', $this->sql ) ) {
					$result = $this->pdostmt->fetchAll( PDO::FETCH_ASSOC );
				} else if ( preg_match( '/^(DELETE|UPDATE) /i', $this->sql ) ) {
					$result = $this->pdostmt->rowCount();
				} else if ( preg_match( '/^(INSERT) /i', $this->sql ) ) {
					$result = $this->PDO->lastInsertId();
				}
				$this->pdostmt->closeCursor();
				return $result;
			} else {
				$this->error_message = $this->pdostmt->errorInfo();
				$this->error_code = $this->pdostmt->errorCode();
				// TODO: log error
				return FALSE;
			}
		} catch ( PDOException $e ) {
			$this->error_message = $e->getMessage();
			$this->error_code = $e->getCode();
			$this->error();
			return FALSE;
		}
	}


	/* [ CRUD ] */

	public static function create () {}

	public static function read () {}

	public static function update () {}

	public static function delete () {}




	/*
	ETC SPECIFIC QUERIES BELOW
	*/

	public function query_path_of_object_id ( $object_type, $object_id, $return_type = 'html' , $base_url_path = '') {
		// TODO TO-DO TO DO: change this recursion to a loop, just because yeah, I do not like using the stack this way and wololo
		$object_type = strtolower( $object_type );
		$object_id = intval( $object_id, 10 );
		$return_type = strtolower( $return_type );
		$object_name = $this->query_object_name_from_id( $object_type, $object_id );
		$folder_parent_id = $this->query_folder_id_of_object_id( $object_type, $object_id );
		switch ( $object_type ) {
			case 'folder':
				switch ( $return_type ) {
					case 'html':	return ( $folder_parent_id > 0 ) ? $this->query_path_of_object_id( $object_type, $folder_parent_id, $return_type, $base_url_path ).'<a class="tx_darkblue" href="'.$base_url_path.'index.php?pasta='.$object_id.'">'.$object_name.'</a> / ' : '<a class="tx_darkblue" href="'.$base_url_path.'index.php?pasta='.$object_id.'">'.$object_name.'</a> / ';
					break;

					case 'text':	return ( $folder_parent_id > 0 ) ? $this->query_path_of_object_id( $object_type, $folder_parent_id, $return_type, $base_url_path ).$object_name.' / ' : $object_name.' / ';
					break;

					case 'array':	return ( $folder_parent_id > 0 ) ? array_merge( $this->query_path_of_object_id( $object_type, $folder_parent_id, $return_type, $base_url_path ), array( $object_name ) ) : array( $object_name );
					break;

					default:
						$this->registerLog( 'error', __METHOD__.'() parameter $return_type is invalid: "'.print_r( $return_type, TRUE ).'"', 1 );
					break;
				}
			break;

			case 'text':
			case 'library':
			case 'forum':
			case 'message':
				switch ( $return_type ) {
					case 'html':	// TODO: return HTML link EVEN for the requested object itself instead of just the non-clickable name DIFFICULTY: consider ALL types of objects, not just texts
					case 'text':	return ( $folder_parent_id > 0 ) ? $this->query_path_of_object_id( 'folder', $folder_parent_id, $return_type, $base_url_path ).$object_name : $object_name;
					break;

					case 'array':	return ( $folder_parent_id > 0 ) ? array_merge( $this->query_path_of_object_id( 'folder', $folder_parent_id, $return_type, $base_url_path ), array( $object_name ) ) : array( $object_name );
					break;

					default:
						$this->registerLog( 'error', __METHOD__.'() parameter $return_type is invalid: "'.print_r( $return_type, TRUE ).'"', 1 );
					break;
				}
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"', 1 );
			break;
		}
	}

	public function query_user_can_access_object_id ( $object_type, $object_id, $user_id ) {
		switch ( strtolower( $object_type ) ) {
			case 'folder':
				$result = $this->query( 'SELECT `user_id`
										FROM `pastas_user`
										WHERE `pasta_id` = :object_id
										AND `user_id` = :user_id',
										array( ':object_id' => $object_id,
												':user_id' => $user_id ) );
				return empty( $result ) ? FALSE : TRUE;
			break;

			case 'text':
				$result = $this->query( 'SELECT `user_id`
										FROM `textos_user`
										WHERE `texto_id` = :object_id
										AND `user_id` = :user_id',
										array( ':object_id' => $object_id,
												':user_id' => $user_id ) );
				return empty( $result ) ? FALSE : TRUE;
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"', 1 );
			break;
		}
	}

	public function query_folder_id_of_object_id ( $object_type, $object_id ) {
		switch ( strtolower( $object_type ) ) {

			case 'folder':
				$results = $this->query( "SELECT `pasta_pai`
										FROM `pastas`
										WHERE `pasta_id` = '".$this->escape( $object_id )."';" );
				return empty( $results ) ? 0 : intval( $results[0]['pasta_pai'], 10 );
			break;

			case 'text':
				$results = $this->query( "SELECT `pasta_id`
										FROM `textos`
										WHERE `texto_id` = '".$this->escape( $object_id )."';" );
				return empty( $results ) ? 0 : intval( $results[0]['pasta_id'], 10 );
			break;

			case 'library':
				$results = $this->query( 'SELECT `pasta_texto_id`
											FROM `library`
											WHERE `element_id` = :object_id',
											array( ':object_id' => $object_id ) );
				return empty( $results ) ? 0 : intval( $results[0]['pasta_texto_id'], 10 );
			break;

			case 'forum':
				$results = $this->query( 'SELECT `forum_id`
											FROM `forum_etc`
											WHERE `msg_id` = :object_id',
											array( ':object_id' => $object_id ) );
				return empty( $results ) ? 0 : intval( $results[0]['forum_id'], 10 );
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"', 1 );
			break;
		}
	}

	public function query_users_ids_of_object_id ( $object_type, $object_id ) {
		switch ( strtolower( $object_type ) ) {
			case 'folder':
				$results = $this->query( 'SELECT `user_id`
											FROM `pastas_user`
											WHERE `pasta_id` = :object_id
											ORDER BY `user_id` ASC',
											array( ':object_id' => $object_id ) );
				$temp = array();
				foreach ( $results as $result ) {
					$temp[] = $result['user_id'];
				}
				return $temp;
			break;

			case 'text': // ORDER BY `etc_users`.`name` ASC: not necessary, but happens
				$results = $this->query( 'SELECT `etc_users`.`user_id`
											FROM `textos_user`, `etc_users`
											WHERE `texto_id` = :object_id
											AND `textos_user`.`user_id` = `etc_users`.`user_id`
											ORDER BY `etc_users`.`name` ASC',
											array( ':object_id' => $object_id ) );
				$temp = array();
				foreach ( $results as $result ) {
					$temp[] = $result['user_id'];
				}
				return $temp;
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"', 1 );
			break;
		}
	}

	public function query_objects_from_user_folder ( $objects_type, $folder_id, $user_id ) {
		switch ( strtolower( $objects_type ) ) {
			case 'text': // for each user's text in the folder, retrieves it's id, name, user id that last saved it, and last save's timestamp
				return $this->query( 'SELECT `USER_TEXTS_IN_FOLDER`.`texto_id`, `USER_TEXTS_IN_FOLDER`.`name`, `etc_texts_history`.`user_id`, `MAX_TIMESTAMPS`.`timestamp`
										FROM ( SELECT `textos`.`texto_id`, `textos`.`name`
												FROM `textos`, `textos_user`
												WHERE `textos`.`pasta_id` = :folder_id AND
													`textos_user`.`user_id` = :user_id AND
													`textos`.`texto_id` = `textos_user`.`texto_id` ) AS `USER_TEXTS_IN_FOLDER`
										LEFT JOIN ( SELECT `text_id`, MAX( `timestamp` ) AS `timestamp`
													FROM `etc_texts_history`
													GROUP BY `text_id` ) AS `MAX_TIMESTAMPS`
											ON ( `USER_TEXTS_IN_FOLDER`.`texto_id` = `MAX_TIMESTAMPS`.`text_id` )
										LEFT JOIN `etc_texts_history`
											ON ( `etc_texts_history`.`timestamp` = `MAX_TIMESTAMPS`.`timestamp` AND
												`etc_texts_history`.`text_id` = `MAX_TIMESTAMPS`.`text_id` )
										ORDER BY `MAX_TIMESTAMPS`.`timestamp` DESC, `USER_TEXTS_IN_FOLDER`.`name` ASC;',
										array( ':folder_id' => $folder_id,
												':user_id' => $user_id ) );
			break;

			case 'library':
			break;

			case 'forum':
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $objects_type is invalid: "'.print_r( $objects_type, TRUE ).'"', 1 );
			break;
		}
	}

	public function query_object_name_from_id ( $object_type, $object_id, &$existing_names_array = NULL ) {
		if ( !empty( $existing_names_array[$object_id] ) ) {
			return $existing_names_array[$object_id];
		} else {
			switch ( strtolower( $object_type ) ) {
				case 'user':
					$result = $this->query( 'SELECT `name`
											FROM `etc_users`
											WHERE `user_id` = :object_id',
											array( ':object_id' => $object_id ) );
					$existing_names_array[$object_id] = empty( $result ) ? 'UNKNOWN_USER' : $result[0]['name'];
					return $existing_names_array[$object_id];
				break;

				case 'folder':
					$result = $this->query( 'SELECT `name`
											FROM `pastas`
											WHERE `pasta_id` = :object_id',
											array( ':object_id' => $object_id ) );
					$existing_names_array[$object_id] = empty( $result ) ? 'UNKNOWN_FOLDER' : $result[0]['name'];
					return $existing_names_array[$object_id];
				break;

				case 'text':
					$result = $this->query( 'SELECT `name`
											FROM `textos`
											WHERE `texto_id` = :object_id',
											array( ':object_id' => $object_id ) );
					$existing_names_array[$object_id] = empty( $result ) ? 'UNKNOWN_TEXT' : $result[0]['name'];
					return $existing_names_array[$object_id];
				break;

				case 'library':
					$result = $this->query( 'SELECT `titulo`
											FROM `library`
											WHERE `element_id` = :object_id',
											array( ':object_id' => $object_id ) );
					$existing_names_array[$object_id] = empty( $result ) ? 'UNKNOWN_OBJECT' : $result[0]['titulo'];
					return $existing_names_array[$object_id];
				break;

				case 'forum':
					$result = $this->query( 'SELECT `titulo`
											FROM `forum_etc`
											WHERE `msg_id` = :object_id',
											array( ':object_id' => $object_id ) );
					$existing_names_array[$object_id] = empty( $result ) ? 'UNKNOWN_FORUM' : $result[0]['titulo'];
					return $existing_names_array[$object_id];
				break;

				case 'message':
					$result = $this->query( 'SELECT `subject`
											FROM `etc_email`
											WHERE `id` = :object_id',
											array( ':object_id' => $object_id ) );
					$existing_names_array[$object_id] = empty( $result ) ? 'UNKNOWN_EMAIL' : $result[0]['subject'];
					return $existing_names_array[$object_id];
				break;

				default:
					$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"', 1 );
				break;
			}
		}
	}

	public function query_last_version_of_text_id ( $text_id ) {
		$results = $this->query( 'SELECT `timestamp`, `text`
									FROM `etc_texts_history`
									WHERE `text_id` = :text_id
									ORDER BY `timestamp` DESC
									LIMIT 1',
									array( ':text_id' => $text_id ) );
		return empty( $results ) ? array() : $results[0];
	}
}
?>
