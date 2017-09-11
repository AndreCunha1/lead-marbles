/* declara uma nova requisição ajax */
var meu_ajax = new XMLHttpRequest();

/* declara um "conteiner" de dados para serem enviados por POST */
var formData = new FormData();

/* adiciona uma variável ao "contêiner", no caso, a variável 'variavel' que contém o dado 'dado' */
formData.append( 'variavel', 'dado' ); /* $_POST['variavel'] === 'dado' */
formData.append( 'variavel2', 'dado' );
formData.append( 'variavel3', 'dado' );
formData.append( 'variavel4', 'dado' );
formData.append( 'variavel5', 'dado' );

/* configuração do ajax: qual o "tipo" (no caso, POST) e qual a página que será acessada (no caso, ajax_page.php) */
/* (o último parâmetro, um booleano, é para especificar se é assíncrono (true) ou síncrono (false)) */
meu_ajax.open( 'POST', './pagina/ajax_page.php', true );

/* configurar a função que será chamada quando a requisição mudar de estado */
meu_ajax.onreadystatechange = function () {
	if ( meu_ajax.readyState === 4 ) { /* readyState === 4: terminou/completou a requisição */
		if ( meu_ajax.status === 200 ) { /* status === 200: sucesso */
			if ( meu_ajax.responseText.length > 0 ) {
				/* resposta não-vazia */
				console.log( meu_ajax.responseText );
			} else {
				/* resposta vazia */
			}
		} else if ( meu_ajax.status !== 0 ) { /* status !== 200: erro (meu_ajax.status === 0: ajax não enviado) */
			console.log( 'DEU ERRO NO AJAX: '+meu_ajax.responseText );
		}
	}
};

/* enviar o ajax/realizar a requisição */
meu_ajax.send( formData );