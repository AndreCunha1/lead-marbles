<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );
require_once( './HtmlDiff/HtmlDiff.php' );

if ( isset( $_POST['function'] ) ) {
	switch ( strtolower( $_POST['function'] ) ) {
		case 'versions':
			switch ( count( $_POST ) ) {
				case 2:
					// send information about the versions of a text
					if ( isset( $_POST['text_id'] ) ) {
						$versions = $pdo_handler->query( 'SELECT `user_id`, `timestamp`
															FROM `etc_texts_history`
															WHERE `text_id` = :text_id
															ORDER BY `timestamp` DESC;',
															array( ':text_id' => intval( $_POST['text_id'] ) ) );
						$user_names = array();
						foreach ( $versions as $key => $version ) {
							if ( empty( $user_names[$version['user_id']] ) ) {
								$user_names[$version['user_id']] = $pdo_handler->query_object_name_from_id( 'user', $version['user_id'] );
							}
							$versions[$key]['timestamp'] = '['.formatTimestamp( $version['timestamp'] ).']';
							$versions[$key]['user_name'] = $user_names[$version['user_id']];
							unset( $versions[$key]['user_id'] );
						}
						echo json_encode( $versions );
					}
				break;
			}
		break;

		case 'htmldiff':
			switch ( count( $_POST ) ) {
				case 4:
					// send the HTML Diff of the two text versions
					if ( isset( $_POST['text_id'] ) && isset( $_POST['selected_newer'] ) && isset( $_POST['selected_older'] ) ) {
						$texts = $pdo_handler->query( 'SELECT `text`
														FROM `etc_texts_history`
														WHERE `text_id` = :text_id
														ORDER BY `timestamp` DESC;',
														array( ':text_id' => intval( $_POST['text_id'] ) ) );
						$diff = new HtmlDiff( $texts[intval( $_POST['selected_older'] )]['text'], $texts[intval( $_POST['selected_newer'] )]['text'] );
						$diff->build();
						/*
						echo "<h2>Old html</h2>";
						echo $diff->getOldHtml();
						echo "<h2>New html</h2>";
						echo $diff->getNewHtml();
						echo "<h2>Compared html</h2>";
						*/
						echo $diff->getDifference();
					}
				break;
			}
		break;

		case 'revert':
			switch ( count( $_POST ) ) {
				case 3:
					// revert version to $_POST['version_number_revert']. Outputs 'OK' in success.
					if ( isset( $_POST['text_id'] ) && isset( $_POST['version_number_revert'] ) ) {
						$texts = $pdo_handler->query( 'SELECT `text`
														FROM `etc_texts_history`
														WHERE `text_id` = :text_id
														ORDER BY `timestamp` DESC;',
														array( ':text_id' => intval( $_POST['text_id'] ) ) );
						$pdo_handler->query( 'INSERT INTO `etc_texts_history` ( `text_id`, `user_id`, `text` )
												VALUES ( :text_id, :user_id, :text );',
												array( ':text_id' => intval( $_POST['text_id'] ),
														':user_id' => $user_id,
														':text' => $texts[intval( $_POST['version_number_revert'] )]['text'] ) );
						echo 'OK';
					}
				break;
			}
		break;
	}
}

?>
