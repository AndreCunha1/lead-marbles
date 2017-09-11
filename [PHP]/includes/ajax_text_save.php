<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

Create_HTMLPurifier();
$save_as			= isset( $_POST['save_as'] ) ? trim( $_POST['save_as'] ) : 'false';
$save_as_folder_id	= isset( $_POST['save_as_target_folder_id'] ) ? intval( $_POST['save_as_target_folder_id'], 10 ) : 0;
$nome				= isset( $_POST['texto_nome'] ) ? trim( $_POST['texto_nome'] ) : '';
$texto_id			= isset( $_POST['texto_id'] ) ? intval( $_POST['texto_id'], 10 ) : 0;
$conteudo			= isset( $_POST['conteudo'] ) ? $HTMLPurifier->purify( trim( $_POST['conteudo'] ) ) : '';

$text_info = $pdo_handler->query( 'SELECT *
									FROM `textos`
									WHERE `texto_id` = :texto_id;',
									array( ':texto_id' => $texto_id ) );

if ( empty( $text_info ) ) {
	// text_id inválido TODO TO DO LOG ATTACKER TRESSPASSING
} else if ( $save_as === 'true' ) {
	// cria o novo texto
	$textoNovo_id = $pdo_handler->query( 'INSERT INTO `textos` ( `pasta_id`, `name`, `descricao`, `url` )
											VALUES ( :save_as_folder_id, :nome, :descricao, :newUniqueName );',
											array( ':save_as_folder_id' => $save_as_folder_id,
													':nome' => $nome,
													':descricao' => $text_info[0]['descricao'],
													':newUniqueName' => newUniqueName() ) );
	$pdo_handler->query( 'INSERT INTO `etc_texts_history` ( `text_id`, `user_id`, `text` )
							VALUES ( :textoNovo_id, :user_id, :conteudo );',
							array( ':textoNovo_id' => $textoNovo_id,
									':user_id' => $user_id,
									':conteudo' => $conteudo ) );

	// associa o novo texto aos autores do texto original
	$select_users = $pdo_handler->query( 'SELECT `user_id`
											FROM `textos_user`
											WHERE `texto_id` = :texto_id;',
											array( ':texto_id' => $texto_id ) );
	foreach ( $select_users as $select_user ) {
		$pdo_handler->query( 'INSERT INTO `textos_user` ( `user_id`, `texto_id` )
								VALUES ( :user_id, :textoNovo_id );',
								array( ':user_id' => $select_user['user_id'],
										':textoNovo_id' => $textoNovo_id ) );
	}

	Events::create( 'text', 'add_user', $user_id, $textoNovo_id, $pdo_handler->query_users_ids_of_object_id( 'text', $textoNovo_id ) );

	echo $textoNovo_id;
} else {
	$version_id = $pdo_handler->query( 'INSERT INTO `etc_texts_history` ( `text_id`, `user_id`, `text` )
							VALUES ( :texto_id, :user_id, :conteudo );',
							array( ':texto_id' => $texto_id,
									':user_id' => $user_id,
									':conteudo' => $conteudo ) );

	Events::create( 'text', 'edit', $user_id, $texto_id, $pdo_handler->query_users_ids_of_object_id( 'text', $texto_id ) );

	echo $version_id;
}
?>
