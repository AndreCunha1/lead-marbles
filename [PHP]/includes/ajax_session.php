<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

if ( count( $_GET ) === 1 && !empty( $user ) ) {
	switch( TRUE ) {
		case isset( $_GET['get'] ):
			switch( strtolower( $_GET['get'] ) ) {
				case 'type':
					echo $user->getProperty( 'selectedType' );
				break;

				case 'folder_id':
					echo $user->getProperty( 'selectedFolderID' );
				break;

				case 'texts_ids':
					echo json_encode( $user->getProperty( 'selectedTextsIDs' ) );
				break;
			}
		break;

		case isset( $_GET['folder_id'] ):
			$user->setSelectedFolderID( $_GET['folder_id'] );
		break;

		case isset( $_GET['text_id'] ):
			$user->toggleSelectedTextID( $_GET['text_id'] );
		break;
	}
}
?>
