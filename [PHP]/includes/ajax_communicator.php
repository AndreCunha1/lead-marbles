<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

if ( isset( $_POST['function'] ) ) {
	switch ( strtolower( $_POST['function'] ) ) {
		case 'communicator':
			switch ( count( $_POST ) ) {
				case 2:
					// send alert to all participants (notification)
					switch ( TRUE ) {
						case isset( $_POST['folder_id'] ):
							Events::create( 'folder', 'communicator_alert', $user_id, $_POST['folder_id'], $pdo_handler->query_users_ids_of_object_id( 'folder', $_POST['folder_id'] ) );
							printHTMLAlertMessage( array( 'folder_id' => $_POST['folder_id'] ) );
						break;

						case isset( $_POST['text_id'] ):
							Events::create( 'text', 'communicator_alert', $user_id, $_POST['text_id'], $pdo_handler->query_users_ids_of_object_id( 'text', $_POST['text_id'] ) );
							printHTMLAlertMessage( array( 'text_id' => $_POST['text_id'] ) );
						break;
					}
				break;

				case 3:
					// output pending messages
					if ( isset( $_POST['last_timestamp'] ) ) {
						switch ( TRUE ) {
							case isset( $_POST['folder_id'] ):
								printHTMLMessagesSinceTimestamp( array( 'folder_id' => $_POST['folder_id'], 'timestamp' => $_POST['last_timestamp'] ) );
							break;

							case isset( $_POST['text_id'] ):
								printHTMLMessagesSinceTimestamp( array( 'text_id' => $_POST['text_id'], 'timestamp' => $_POST['last_timestamp'] ) );
							break;
						}
					}
				break;

				case 4:
					// insert message in the database and output pending messages
					if ( isset( $_POST['last_timestamp'] ) && isset( $_POST['communicator_input_message'] ) ) {
						switch ( TRUE ) {
							case isset( $_POST['folder_id'] ):
								$pdo_handler->query( 'INSERT INTO `etc_communicator` ( `from_user_id`, `to_folder_id`, `message` )
														VALUES( :user_id, :folder_id, :message );',
														array( ':user_id' => $user_id,
																':folder_id' => $_POST['folder_id'],
																':message' => $_POST['communicator_input_message'] ) );
								printHTMLMessagesSinceTimestamp( array( 'folder_id' => $_POST['folder_id'], 'timestamp' => $_POST['last_timestamp'] ) );
							break;

							case isset( $_POST['text_id'] ):
								$pdo_handler->query( 'INSERT INTO `etc_communicator` ( `from_user_id`, `to_text_id`, `message` )
														VALUES( :user_id, :text_id, :message );',
																	array( ':user_id' => $user_id,
																			':text_id' => $_POST['text_id'],
																			':message' => $_POST['communicator_input_message'] ) );
								printHTMLMessagesSinceTimestamp( array( 'text_id' => $_POST['text_id'], 'timestamp' => $_POST['last_timestamp'] ) );
							break;
						}
					}
				break;
			}
		break;

		case 'notes':
			switch ( count( $_POST ) ) {
				case 3:
					// output pending notes
					if ( isset( $_POST['last_timestamp'] ) ) {
						switch ( TRUE ) {
							case isset( $_POST['text_id'] ):
								printHTMLTextNotesSinceTimestamp( array( 'text_id' => $_POST['text_id'], 'timestamp' => $_POST['last_timestamp'] ) );
							break;
						}
					}
				break;

				case 4:
					// insert note in the database and output pending notes
					if ( isset( $_POST['last_timestamp'] ) && isset( $_POST['text_notes_input_note'] ) ) {
						switch ( TRUE ) {
							case isset( $_POST['text_id'] ):
								$pdo_handler->query( 'INSERT INTO `etc_text_notes` ( `from_user_id`, `to_text_id`, `note` )
														VALUES( :user_id, :text_id, :note );',
														array( ':user_id' => $user_id,
																':text_id' => $_POST['text_id'],
																':note' => $_POST['text_notes_input_note'] ) );
								printHTMLTextNotesSinceTimestamp( array( 'text_id' => $_POST['text_id'], 'timestamp' => $_POST['last_timestamp'] ) );
							break;
						}
					}
				break;
			}
		break;
	}
}


//*************************************************************************************************
//FUNCTIONS
//*************************************************************************************************

function printHTMLMessagesSinceTimestamp ( $params ) {
	//$params = array( 'folder_id' => '', 'text_id' => '', 'timestamp' => '' );
	if ( !is_assoc( $params ) ) {
		// TODO TO DO write entry in log file
		//echo __FUNCTION__.'(): $params is not an associative array.';
	}

	global $pdo_handler;

	switch ( TRUE ) {
		case !empty( $params['folder_id'] ):
			$messages = $pdo_handler->query( 'SELECT *
												FROM `etc_communicator`
												WHERE `to_folder_id` = :folder_id
												AND `timestamp` > :timestamp
												ORDER BY `timestamp` ASC;',
												array( ':folder_id' => $params['folder_id'],
														':timestamp' => $params['timestamp'] ) );
		break;

		case !empty( $params['text_id'] ):
			$messages = $pdo_handler->query( 'SELECT *
												FROM `etc_communicator`
												WHERE `to_text_id` = :text_id
												AND `timestamp` > :timestamp
												ORDER BY `timestamp` ASC;',
												array( ':text_id' => $params['text_id'],
														':timestamp' => $params['timestamp'] ) );
		break;

		default:
			// TODO TO DO write entry in log file
			return;
		break;
	}

	$user_names = array();

	foreach ( $messages as $message ) {
		?>
		<div data-timestamp="<?php echo $message['timestamp']; ?>" style="margin-bottom:8px;">
			<div class="tx_small" style="font-weight:bold;">
				<?php
				echo '['.formatTimestamp( $message['timestamp'] ).'] ';
				if ( empty( $user_names[$message['from_user_id']] ) ) {
					$user_names[$message['from_user_id']] = $pdo_handler->query_object_name_from_id( 'user', $message['from_user_id'] );
				}
				echo $user_names[$message['from_user_id']];
				?>
			</div>
			<div class="tx_small">
				<?php echo $pdo_handler->toHTML( $message['message'] ); ?>
			</div>
		</div>
		<?php
	}
}

function printHTMLAlertMessage ( $params ) {
	//$params = array( 'folder_id' => '', 'text_id' => '' );
	if ( !is_assoc( $params ) ) {
		echo __FUNCTION__.': $params is not an associative array.';
	}

	?>
	<div style="margin-bottom:8px;">
		<div class="tx_smaller" style="font-weight:bold; color:#991111;">
			<?php
			switch ( TRUE ) {
				case !empty( $params['folder_id'] ):	echo '[ Seu pedido de atenção foi enviado aos participantes desta pasta ]';
				break;

				case !empty( $params['text_id'] ):		echo '[ Seu pedido de atenção foi enviado aos participantes deste texto ]';
				break;
			}
			?>
		</div>
	</div>
	<?php
}

function printHTMLTextNotesSinceTimestamp ( $params ) {
	//$params = array( 'text_id' => '', 'timestamp' => '' );
	if ( !is_assoc( $params ) ) {
		echo __FUNCTION__.'(): $params is not an associative array.';
	}

	global $pdo_handler;

	switch ( TRUE ) {
		case !empty( $params['text_id'] ):
			$notes = $pdo_handler->query( 'SELECT *
											FROM `etc_text_notes`
											WHERE `to_text_id` = :text_id
											AND `timestamp` > :timestamp
											ORDER BY `timestamp` ASC;',
											array( ':text_id' => $params['text_id'],
													':timestamp' => $params['timestamp'] ) );
		break;
	}

	$user_names = array();

	foreach ( $notes as $note ) {
		?>
		<div data-timestamp="<?php echo $note['timestamp']; ?>" style="margin-bottom:8px;">
			<div class="tx_small" style="font-weight:bold;">
				<?php
				echo '['.formatTimestamp( $note['timestamp'] ).'] ';
				if ( empty( $user_names[$note['from_user_id']] ) ) {
					$user_names[$note['from_user_id']] = $pdo_handler->query_object_name_from_id( 'user', $note['from_user_id'] );
				}
				echo $user_names[$note['from_user_id']];
				?>
			</div>
			<div class="tx_small">
				<?php echo $pdo_handler->toHTML( $note['note'] ); ?>
			</div>
		</div>
		<?php
	}
}

?>
