<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

// consulta o nome dos participantes de uma pasta
// retorna seu id, nome e endereço da foto, em uma estrutura de dados organizada com JSON.

$sql = '';
$bind = array();

// espera os seguintes parâmetros:
// nome - nome a ser pesquisado (string)
// pid - id da pasta que conterá o grupo onde a consulta será executada (inteiro)

// padrões
$PID	= 1;
$TIPO	= 0;
$ID		= 0;
$MODE	= 0;
$ACHASI	= 0;

// parâmetros
$nome = isset( $_POST['nome'] ) ? $_POST['nome'] : '';
//$email = isset( $_POST['email'] ) ? $_POST['email'] : ''; // Mexendo Aqui
$pid = isset( $_POST['pid'] ) ? $_POST['pid'] : $PID;
$achasi = isset( $_POST['achasi'] ) ? $_POST['achasi'] : $ACHASI;

// tipo:	0 - id passado é uma pasta
//			1 - id passado é um texto
$tipo	= isset( $_POST['tipo'] )	? $_POST['tipo']	: $TIPO;

// id:	 0 - utilizado para casos de Nova Pasta e Novo Texto
//		>0 - id usado para excluir da pesquisa os usuários que já pertencem a isto (pasta ou texto)
$id		= isset( $_POST['id'] )		? $_POST['id']	: $ID;

// mode:	0 - Pesquisa por nome
//			1 - Pesquisa por email
$mode	= isset($_POST['mode'])		? $_POST['mode']	: $MODE;

// CONSULTA
// id, nome e foto dos usuários cujo nome contenha a string $nome
// os nomes virão da pasta que possui o id $pid

//$nome = $nome;
if ( $achasi == 0 ) {
	$sql = ' AND etc_users.user_id <> :user_id';
	$bind[':user_id'] = $user_id;
}

if ( $id != 0 ) { // pesquisa para elemento já existente, ou seja, pesquisa com NOT IN
	if ( $tipo == 0 ) {
		$sql = $sql." AND etc_users.user_id NOT IN (
						SELECT user_id FROM pastas_user
						WHERE pasta_id = :id
					)";
	} else {
		$sql = $sql." AND etc_users.user_id
					NOT IN (
						SELECT user_id FROM textos_user
						WHERE texto_id = :id
					)";
	}
	$bind[':id'] = $id;
}

if ( $mode == '0' ) {
	// parte funcional que realiza a pesquisa pelos nomes
	if ( $pid == 1 ) { // MEUS DOCUMENTOS
		$sql = 'SELECT etc_users.user_id, name, photo, excluido
				FROM etc_users
				WHERE name LIKE :nome
				AND excluido = 0 '.$sql;
	} else { // qualquer outra pasta mãe
		$sql = 'SELECT etc_users.user_id, name, photo, pasta_id, excluido
				FROM etc_users, pastas_user
				WHERE name LIKE :nome
				AND excluido = 0
				AND etc_users.user_id = pastas_user.user_id
				AND pastas_user.pasta_id = :pid '.$sql;
		$bind[':pid'] = $pid;
	}
} else {
	// atualização para realização da pesquisa por email
	if ( $pid == 1 ) { // MEUS DOCUMENTOS
		$sql = "SELECT etc_users.user_id, name, photo, email, excluido
				FROM etc_users
				WHERE email LIKE :nome
				AND excluido = '0'".$sql;
	} else { // qualquer outra pasta mãe
		$sql = 'SELECT etc_users.user_id, name, photo, pasta_id, email, excluido
				FROM etc_users, pastas_user
				WHERE email LIKE :nome
				AND excluido = 0
				AND etc_users.user_id = pastas_user.user_id
				AND pastas_user.pasta_id = :pid '.$sql;
		$bind[':pid'] = $pid;
	}
}
$bind[':nome'] = '%'.$nome.'%';
$sql .= ' ORDER BY `etc_users`.`name` ASC LIMIT 15;';
$results = $pdo_handler->query( $sql, $bind );

// ajeita os dados na estrutura
/*
usuarios[] = {
	id:		id_do_usuario,
	nome:	nome_do_usuario,
	foto:	endereco_da_foto
}
*/

$dados = array();

foreach ( $results as $user ) {
	$id		= $user['user_id'];
	$nome	= $user['name'];
	$foto	= $user['photo'];

	$dados[] = array( 'id' => $id, 'nome' => $nome, 'foto' => $foto );
}

echo json_encode( array( 'usuarios' => $dados ) );
?>
