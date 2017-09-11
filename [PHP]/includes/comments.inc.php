<?php

class Comments {

	function insertComment ( $user, $text, $content, $reference ) {
		global $pdo_handler;
		$result = $pdo_handler->query( 'INSERT INTO `etc_text_comments` ( `user_id`, `text_id`, `content`, `reference`, `solved`, `parent`)
										VALUES ( :user, :text, :content, :reference, 0, 0 );',
												array( ':user' => $user,
														':text' => $text,
														':content' => $content,
														':reference' => $reference ) );
		return $result;
	}

	function getComment ( $id ) {
		global $pdo_handler;
		$parent = 0;
		$comments = array();
		$results = $pdo_handler->query( 'SELECT `etc_text_comments`.*, `etc_users`.`name` FROM `etc_text_comments`, `etc_users`
										WHERE `etc_users`.`user_id` = `etc_text_comments`.`user_id`
										AND `id` = :id
										AND `parent` = :parent;',
										array( ':id' => $id,
												':parent' => $parent ) );
		array_push( $comments, $results );
		while ( !empty( $results ) ) {
			$parent = $results[0]['id'];
			$results = $pdo_handler->query( 'SELECT `etc_text_comments`.*, `etc_users`.`name` FROM `etc_text_comments`,`etc_users`
											WHERE `etc_users`.`user_id` = `etc_text_comments`.`user_id`
											AND `parent` = :parent;',
											array( ':parent' => $parent ) );
			array_push( $comments, $results );
		}
		array_pop( $comments );
		foreach ( $comments as &$comments_real ) {
			foreach ( $comments_real as &$comment ) {
				if ( !empty( $comment['date'] ) ) {
					$comment['date'] = formatTimestamp( $comment['date'] );
				}
			}
		}
		return ( $comments );
	}

	function marcarComoResolvida ( $id ) {
		global $pdo_handler;
		$pdo_handler->query( 'UPDATE `etc_text_comments` SET `solved` = 1 WHERE `id` = :id;',
								array( ':id' => $id ) );
	}

	function reply ( $user, $text, $parent, $content ) {
		global $pdo_handler;
		$results = $pdo_handler->query( 'INSERT INTO `etc_text_comments` ( `user_id`, `text_id`, `content`, `reference`, `solved`, `parent` )
										VALUES ( :user, :text, :content, "", 0, :parent );',
										array( ':user' => $user,
												':text' => $text,
												':content' => $content,
												':parent' => $parent ) );
		$comment = $this->findFinalParent( $results );
		if ( $this->isSolved( $comment ) ) {
			$pdo_handler->query( 'UPDATE `etc_text_comments` SET `solved` = 0 WHERE `id` = :id;',
									array( ':id' => $comment ) );
		}
		return $results;
	}

	function isSolved ( $id ) {
		global $pdo_handler;
		$results = $pdo_handler->query( 'SELECT `solved` FROM `etc_text_comments` WHERE `id` = :id;',
										array( ':id' => $id ) );
		if ( !empty( $results ) ) {
			if ( !empty( $results[0]['solved'] ) ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	function findFinalParent ( $id ) {
		global $pdo_handler;
		$parent = 0;
		do {
			$results = $pdo_handler->query( 'SELECT `parent` FROM `etc_text_comments` WHERE `id` = :id;',
											array( ':id' => $id ) );
			$parent = $id;
			if ( !empty( $results ) ) {
				$id = $results[0]['parent'];
			} else {
				$id = 0;
			}
		} while ( $id != 0 );
		return $parent;
	}

	function showSolvedComments ( $text ) {
		global $pdo_handler;
		$comment = new Comments();
		$comments = $pdo_handler->query( 'SELECT `id` FROM `etc_text_comments` WHERE `solved` = 1 AND `parent` = 0 AND `text_id` = :text;',
										array( ':text' => $text ) );
		$allSolvedComments = array();
		for ( $count = 0; $count < count( $comments ); $count++ ) {
			array_push( $allSolvedComments, $comment->getComment( $comments[$count]['id'] ) );
		}
		return ( $allSolvedComments );
	}

	function findParent ( $id ) {
		global $pdo_handler;
		$parents = $pdo_handler->query( 'SELECT parent FROM `etc_text_comments` WHERE id = :id;',
										array( ':id' => $id ) );
		return $parents;
	}
}
?>
