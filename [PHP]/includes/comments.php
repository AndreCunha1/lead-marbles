<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );
require_once( 'comments.inc.php' );

if( !empty( $_GET) ){
	$function = trim($_GET['function']) ;

	$comments = new Comments();

	switch ( $function ) {

		case 'insert': // inserir
			$id = $comments->insertComment( $user_id, $_GET['text'], $_GET['content'], $_GET['reference'] );
			die(json_encode(array('id' => $id)));
			break;

		case 'reply': // responder
			$id = $comments->reply( $user_id, $_GET['text'], $_GET['parent'], $_GET['content'] );
			die(json_encode(array('id' => $id)));
			//echo $id;
			break;

		case 'showComment':
			$comment = $comments->getComment( $_GET['id'] );
			exit( json_encode( $comment ) );
			break;

		case 'solve':
			$comments->marcarComoResolvida( $_GET['id'] );
			break;

		case 'finalParent':
			$parent = $comments->findFinalParent( $_GET['id'] );
			die( json_encode(array('parent'=>$parent)) );
			break;

		case 'showSolvedComments':
			$commentsList = $comments->showSolvedComments( $_GET['text'] );
			exit( json_encode( $commentsList ) );
			break;

		case 'findParent':
			$parent = $comments->findParent( $_GET['id'] );
			die( json_encode( $parent ) );
			break;

		default;
			return false;
	}
}
?>
