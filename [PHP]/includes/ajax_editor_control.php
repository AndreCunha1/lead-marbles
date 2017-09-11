<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

$response = array();

if ( empty( $_POST['function'] ) ) {
	exit;
}

switch ( $_POST['function'] ) {
	case 'GET_TEXT_LAST_TIMESTAMP':
		if ( empty( $_POST['text_id'] ) ) {
			exit;
		}
		$result = $pdo_handler->query_last_version_of_text_id( $_POST['text_id'] );
		if ( !empty( $result ) ) {
			$response['timestamp'] = $result['timestamp'];
		}
	break;

	case 'IMAGE_UPLOAD':
		if ( empty( $_FILES['image'] ) ) {
			exit;
		}
		$file = $_FILES['image'];
		$file_extension = getImageExtension( $file['name'] );
		if ( empty( $file_extension ) ) {
			$response['response'] = 'INVALID_EXTENSION';
		} else {
			$file_name = newUniqueName().$file_extension;
			if ( move_uploaded_file( $file['tmp_name'], ETC_BASE_DIR.'archive/images/'.$file_name ) === TRUE ) {
				$response['response'] = $file_name;
			} else {
				$response['response'] = 'UPLOAD_ERROR';
			}
		}
	break;

	default:
		exit;
	break;
}

if ( !empty( $response ) ) {
	echo json_encode( $response );
}

?>
