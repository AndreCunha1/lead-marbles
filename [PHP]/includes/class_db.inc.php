<?php

class DbHandler extends BasicClass {

	/* Properties */

	protected $server;
	protected $name;
	protected $user;
	protected $pass;
	protected $mysql_link;

	protected $result;


	/* Constructor & Destructor */

	public function __construct ( $db_server, $db_name, $db_user, $db_pass ) {
		parent::__construct( get_class( $this ) );

		$this->server		= $db_server;
		$this->name			= $db_name;
		$this->user			= $db_user;
		$this->pass			= $db_pass;
		$this->mysql_link	= NULL;

		$this->result		= FALSE;
	}

	public function __destruct () {
		$this->disconnect();
	}


	/* Protected Methods */

	protected function error ( $message = '' ) {
		if ( is_resource( $this->mysql_link ) ) {
			$_errno = mysql_errno( $this->mysql_link );
			$_error = mysql_error( $this->mysql_link );
		} else {
			$_errno = mysql_errno();
			$_error = mysql_error();
		}

		switch ( $_errno ) {
			case 0: // no error occurred
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$_errno.') '.$message, 2 );
				break;
			case 1049: // unknown database (not exists?)
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$_errno.') unknown requested database (not exists?)', 2 );
				break;
			case 1054: // unknown column (not exists?)
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$_errno.') unknown requested column (not exists?)', 2 );
				break;
			case 1146: // table doesn't exist
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$_errno.') requested table does not exist', 2 );
				break;
			default:
				$this->registerLog( 'error', __METHOD__.'() (MySQL #'.$_errno.') '.$_error, 2 );
				break;
		}
	}


	/* Public Methods */

	public function connected () {
		if ( $this->mysql_link === NULL ) {
			return FALSE;
		} else {
			if ( @mysql_ping( $this->mysql_link ) === TRUE ) { // [WARNING] @ AT ARROBA
				return TRUE;
			} else {
				$this->mysql_link = NULL;
				return FALSE;
			}
		}
	}

	public function connect () {
		$this->mysql_link = mysql_connect( $this->server, $this->user, $this->pass );
		if ( $this->mysql_link === FALSE ) {
			$this->mysql_link = NULL;
			$this->error();
			return FALSE;
		}
		if ( mysql_query( "SET NAMES 'utf8' COLLATE 'utf8_unicode_ci'", $this->mysql_link ) === FALSE ) {
			$this->error();
			return FALSE;
		}
		if ( mysql_set_charset( 'utf8', $this->mysql_link ) === FALSE ) {
			$this->error();
			return FALSE;
		}
		if ( mysql_select_db( $this->name, $this->mysql_link ) === FALSE ) {
			$this->error();
			return FALSE;
		}
		return TRUE;
	}

	public function disconnect () {
		if ( $this->connected() === TRUE ) {
			if ( mysql_close( $this->mysql_link ) === TRUE ) {
				$this->mysql_link = NULL;
				return TRUE;
			} else {
				$this->mysql_link = NULL;
				$this->error();
				return FALSE;
			}
		} else {
			return TRUE;
		}
	}

	public function now () {
		$result = $this->query( "SELECT NOW() AS `now`;" );
		return $result[0]['now'];
	}

	// Query, Return SMART! (:
	// INSERT: return inserted id
	// UPDATE: return mysql_affected_rows()
	// SELECT: associative array with all results (empty array if no results)
	public function query ( $query ) {
		$this->result = mysql_query( $query, $this->mysql_link );
		if ( $this->result === FALSE ) {
			$this->error();
		} else {
			if ( $this->result === TRUE ) { // INSERT, UPDATE, DELETE, DROP, etc
				$insert_id = mysql_insert_id( $this->mysql_link );
				if ( $insert_id === 0 ) { // query does not generate an AUTO_INCREMENT value
					return mysql_affected_rows( $this->mysql_link );
				} else {
					return $insert_id;
				}
			} else { // SELECT, SHOW, DESCRIBE, EXPLAIN and other statements returning resultset
				$results = array();
				while ( $results[] = mysql_fetch_assoc( $this->result ) );
				array_pop( $results ); // removes the FALSE at the end of the array
				return $results;
			}
		}
	}

	public function escape ( $string, $with_trim = FALSE ) {
		//addcslashes( mysql_real_escape_string( $string ), "%_" ); // important! mysql_real_escape_string() do not escape % and _, so read about it and be smart!
		return $with_trim === TRUE ? mysql_real_escape_string( trim( $string ) ) : mysql_real_escape_string( $string );
	}

	public function toHTML ( $string, $with_trim = FALSE ) {
		return $with_trim === TRUE ? nl2br( htmlentities( trim( $string ), ENT_QUOTES, 'UTF-8' ) ) : nl2br( htmlentities( $string, ENT_QUOTES, 'UTF-8' ) );
	}


	// ETC SPECIFIC QUERIES BELOW

	public function query_user_can_access_object_id ( $object_type, $object_id, $user_id ) {
		$object_id = intval( $object_id, 10 );
		$user_id = intval( $user_id, 10 );
		switch ( strtolower( $object_type ) ) {
			case 'folder':
				$results = $this->query( "SELECT `user_id`
										FROM `pastas_user`
										WHERE `pasta_id` = '".$this->escape( $object_id )."'
										AND `user_id` = '".$this->escape( $user_id )."';" );
				return empty( $results ) ? FALSE : TRUE;
			break;

			case 'text':
				$results = $this->query( "SELECT `user_id`
										FROM `textos_user`
										WHERE `texto_id` = '".$this->escape( $object_id )."'
										AND `user_id` = '".$this->escape( $user_id )."';" );
				return empty( $results ) ? FALSE : TRUE;
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"' );
			break;
		}
	}

	public function query_path_of_object_id ( $object_type, $object_id, $return_type = 'html' ) {
		$object_type = strtolower( $object_type );
		$object_id = intval( $object_id, 10 );
		$return_type = strtolower( $return_type );
		$object_name = $this->query_object_name_from_id( $object_type, $object_id );
		$folder_parent_id = $this->query_folder_id_of_object_id( $object_type, $object_id );
		switch ( $object_type ) {
			case 'folder':
				switch ( $return_type ) {
					case 'html':	return ( $folder_parent_id > 0 ) ? $this->query_path_of_object_id( $object_type, $folder_parent_id, $return_type ).'<a class="tx_darkblue" href="index.php?pasta='.$object_id.'">'.$object_name.'</a> / ' : '<a href="index.php?pasta='.$object_id.'">'.$object_name.'</a> / ';
					break;

					case 'text':	return ( $folder_parent_id > 0 ) ? $this->query_path_of_object_id( $object_type, $folder_parent_id, $return_type ).$object_name.' / ' : $object_name.' / ';
					break;

					case 'array':	return ( $folder_parent_id > 0 ) ? array_merge( $this->query_path_of_object_id( $object_type, $folder_parent_id, $return_type ), array( $object_name ) ) : array( $object_name );
					break;

					default:
						$this->registerLog( 'error', __METHOD__.'() parameter $return_type is invalid: "'.print_r( $return_type, TRUE ).'"' );
					break;
				}
			break;

			case 'text':
			case 'library':
			case 'forum':
			case 'message':
				switch ( $return_type ) {
					case 'html':
					case 'text':	return ( $folder_parent_id > 0 ) ? $this->query_path_of_object_id( 'folder', $folder_parent_id, $return_type ).$object_name : $object_name;
					break;

					case 'array':	return ( $folder_parent_id > 0 ) ? array_merge( $this->query_path_of_object_id( 'folder', $folder_parent_id, $return_type ), array( $object_name ) ) : array( $object_name );
					break;

					default:
						$this->registerLog( 'error', __METHOD__.'() parameter $return_type is invalid: "'.print_r( $return_type, TRUE ).'"' );
					break;
				}
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"' );
			break;
		}
	}

	public function query_folder_id_of_object_id ( $object_type, $object_id ) {
		$object_id = intval( $object_id, 10 );
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
				$results = $this->query( "SELECT `pasta_texto_id`
											FROM `library`
											WHERE `element_id` = '".$this->escape( $object_id )."';" );
				return empty( $results ) ? 0 : intval( $results[0]['pasta_texto_id'], 10 );
			break;

			case 'forum':
				$results = $this->query( "SELECT `forum_id`
											FROM `forum_etc`
											WHERE `msg_id` = '".$this->escape( $object_id )."';" );
				return empty( $results ) ? 0 : intval( $results[0]['forum_id'], 10 );
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"' );
			break;
		}
	}

	public function query_users_ids_of_object_id ( $object_type, $object_id ) {
		$object_id = intval( $object_id, 10 );
		switch ( strtolower( $object_type ) ) {
			case 'folder':
				$results = $this->query( "SELECT `user_id`
									FROM `pastas_user`
									WHERE `pasta_id` = '".$this->escape( $object_id )."'
									ORDER BY `user_id` ASC;" );
				$temp = array();
				foreach ( $results as $result ) {
					$temp[] = $result['user_id'];
				}
				return $temp;
			break;

			case 'text': // ORDER BY `etc_users`.`name` ASC: not necessary, but happens
				$results = $this->query( "SELECT `etc_users`.`user_id`
									FROM `textos_user`, `etc_users`
									WHERE `texto_id` = '".$this->escape( $object_id )."' AND
										`textos_user`.`user_id` = `etc_users`.`user_id`
									ORDER BY `etc_users`.`name` ASC;" );
				$temp = array();
				foreach ( $results as $result ) {
					$temp[] = $result['user_id'];
				}
				return $temp;
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"' );
			break;
		}
	}

	public function query_objects_from_user_folder ( $objects_type, $folder_id, $user_id ) {
		$folder_id = intval( $folder_id, 10 );
		$user_id = intval( $user_id, 10 );
		switch ( strtolower( $objects_type ) ) {
			case 'text': // for each user text in the folder, retrieves the id, name, user id that last saved, and timestamp of last save
				return $this->query( "SELECT `USER_TEXTS_IN_FOLDER`.`texto_id`, `USER_TEXTS_IN_FOLDER`.`name`, `etc_texts_history`.`user_id`, `MAX_TIMESTAMPS`.`timestamp`
										FROM
											( SELECT `textos`.`texto_id`, `textos`.`name`
												FROM `textos`, `textos_user`
												WHERE
													`textos`.`pasta_id` = '".$this->escape( $folder_id )."' AND
													`textos_user`.`user_id` = '".$this->escape( $user_id )."' AND
													`textos`.`texto_id` = `textos_user`.`texto_id` ) AS `USER_TEXTS_IN_FOLDER`
											LEFT JOIN
											( SELECT `text_id`, MAX( `timestamp` ) AS `timestamp`
												FROM `etc_texts_history`
												GROUP BY `text_id` ) AS `MAX_TIMESTAMPS`
											ON `USER_TEXTS_IN_FOLDER`.`texto_id` = `MAX_TIMESTAMPS`.`text_id`
											LEFT JOIN
												`etc_texts_history`
											ON `etc_texts_history`.`timestamp` = `MAX_TIMESTAMPS`.`timestamp` AND
												`etc_texts_history`.`text_id` = `MAX_TIMESTAMPS`.`text_id`
										ORDER BY `MAX_TIMESTAMPS`.`timestamp` DESC, `USER_TEXTS_IN_FOLDER`.`name` ASC;" );
			break;

			case 'library':
			break;

			case 'forum':
			break;

			default:
				$this->registerLog( 'error', __METHOD__.'() parameter $objects_type is invalid: "'.print_r( $objects_type, TRUE ).'"' );
			break;
		}
	}

	public function query_object_name_from_id ( $object_type, $object_id, &$existing_names_array = NULL ) {
		$object_id = intval( $object_id, 10 );
		if ( !empty( $existing_names_array[$object_id] ) ) {
			return $existing_names_array[$object_id];
		} else {
			switch ( strtolower( $object_type ) ) {
				case 'user':
					$results = $this->query( "SELECT `name`
												FROM `etc_users`
												WHERE `user_id` = '".$this->escape( $object_id )."';" );
					$existing_names_array[$object_id] = empty( $results ) ? 'UNKNOWN_USER' : $results[0]['name'];
					return $existing_names_array[$object_id];
				break;

				case 'folder':
					$results = $this->query( "SELECT `name`
													FROM `pastas`
													WHERE `pasta_id` = '".$this->escape( $object_id )."';" );
					$existing_names_array[$object_id] = empty( $results ) ? 'UNKNOWN_FOLDER' : $results[0]['name'];
					return $existing_names_array[$object_id];
				break;

				case 'text':
					$results = $this->query( "SELECT `name`
													FROM `textos`
													WHERE `texto_id` = '".$this->escape( $object_id )."';" );
					$existing_names_array[$object_id] = empty( $results ) ? 'UNKNOWN_TEXT' : $results[0]['name'];
					return $existing_names_array[$object_id];
				break;

				case 'library':
					$results = $this->query( "SELECT `titulo`
													FROM `library`
													WHERE `element_id` = '".$this->escape( $object_id )."';" );
					$existing_names_array[$object_id] = empty( $results ) ? 'UNKNOWN_OBJECT' : $results[0]['titulo'];
					return $existing_names_array[$object_id];
				break;

				case 'forum':
					$results = $this->query( "SELECT `titulo`
													FROM `forum_etc`
													WHERE `msg_id` = '".$this->escape( $object_id )."';" );
					$existing_names_array[$object_id] = empty( $results ) ? 'UNKNOWN_FORUM' : $results[0]['titulo'];
					return $existing_names_array[$object_id];
				break;

				case 'message':
					$results = $this->query( "SELECT `subject`
													FROM `etc_email`
													WHERE `id` = '".$this->escape( $object_id )."';" );
					$existing_names_array[$object_id] = empty( $results ) ? 'UNKNOWN_EMAIL' : $results[0]['subject'];
					return $existing_names_array[$object_id];
				break;

				default:
					$this->registerLog( 'error', __METHOD__.'() parameter $object_type is invalid: "'.print_r( $object_type, TRUE ).'"' );
				break;
			}
		}
	}
}

?>
