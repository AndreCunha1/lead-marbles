<?php

class HTMLPage extends BasicClass {

	/*
	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
	▼▼▼ Properties - START ▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼
	*/

	protected $name;
	protected $title;
	protected $model;			// array (unordered) with page elements to include
	protected $state;
	protected $buffer;
	protected $base_loc;
	protected $logged_page;
	protected $translator;

	protected $css_files;		// array com folhas de estilo que devem ser carregadas
	protected $js_files;		// array com arquivos javascript que devem ser carregados

	protected $inner_css;
	protected $inner_js;
	protected $inner_js_translations;

	/*
	▲▲▲ Properties - END ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
	▼▼▼ Public Methods - START ▼▼▼▼▼▼▼▼▼▼▼▼▼▼
	*/

	public function __construct ( $base_loc, & $translator, $name = '', $model = array() ) {
		parent::__construct( get_class( $this ) );

		global $session_timeout;
		global $session_timeout_warning;

		$this->name						= ( trim( $name ) == FALSE ) ? '' : trim( $name ); // empty( trim( $name ) ) não funciona em PHP<5.5
		$this->title					= ( empty( $this->name ) === TRUE ) ? 'Project' : 'Project - '.$this->name;
		$this->model					= ( is_array( $model ) === FALSE ) ? array() : $model;
		$this->state					= 1;
		$this->buffer					= '';
		$this->base_loc					= $base_loc;
		$this->logged_page				= ETC_LOGGED_PAGE;
		$this->translator				= $translator;

		$this->css_files				= array();
		$this->js_files					= array();

		$this->inner_css				= '';
		$this->inner_js					= '';
		$this->inner_js_translations	= '';

		// DEFAULT INCLUDES
		$this->addCssToLoad( $this->base_loc.'include/css/style.css' );
		$this->addCssToLoad( $this->base_loc.'include/css/jquery-ui.css' );
		$this->addJsToLoad( $this->base_loc.'include/js/jquery.js' );
			$this->addJsToLoad( $this->base_loc.'include/js/jquery-ui.js' );
			$this->addJsToLoad( $this->base_loc.'include/js/jquery.mb.browser.js' );
				$this->addJsToLoad( $this->base_loc.'include/js/jquery.webkitresize.js' );
				$this->addJsToLoad( $this->base_loc.'include/js/jquery.wysiwyg-resize.js' );
		$this->addJsToLoad( $this->base_loc.'include/js/general_purpose.js' );
		$this->addInnerJs( '
			if ( false ) { /* TIMEOUT DISABLED */
				if ( window.location.href !== "'.$this->base_loc.'" ) { /* not in login page */
					setTimeout( function () { alert( "'.$this->translator->getTranslation( 'war_session_timeout_is_near' ).'" ); }, '.( ( $session_timeout - $session_timeout_warning ) * 1000 ).' );
				}
			}
		' );

		// MODEL INCLUDES
		if ( count( array_intersect( $this->model, array( 'MENU' ) ) ) > 0 ) {
			$this->addInnerJs( '$( document ).ready( function () { floatElementOnScroll( "menu_esquerda" ); } );' );
		}
		if ( count( array_intersect( $this->model, array( 'FOLDERS' ) ) ) > 0 ) {
			$this->addInnerJs( 'folder_tree.pastas			= JSON.parse( \''.addslashes( json_encode( $this->getFolderTreeProperty( 'folders' ) ) ).'\' );' );
			$this->addInnerJs( 'folder_tree.pastas_pais		= JSON.parse( \''.addslashes( json_encode( $this->getFolderTreeProperty( 'parents' ) ) ).'\' );' );
			$this->addInnerJs( 'folder_tree.pastas_nomes	= JSON.parse( \''.addslashes( json_encode( $this->getFolderTreeProperty( 'names' ) ) ).'\' );' );
		}
	}

	public function __destruct () {
		if ( $this->state !== 3 ) {
			$this->registerLog( 'error', __METHOD__.'() bad formed page' );
		} else {
			/*
			¯\_(ツ)_/¯
			*/
		}
	}

	public function parseBuffer ( $parameter = '' ) {
		if ( empty( $parameter ) ) {
			$parameter = ( $this->debug === TRUE ) ? 'copy' : 'trim';
		}
		if ( count( ob_get_status( TRUE ) ) !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() invalid page buffer count' );
		} else {
			switch ( $parameter ) {
				case 'trim':
					$to_parse = ob_get_contents();
					function remover_comentarios ( $matches ) {
						// $pattern match comentários
						$pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/';
						$search = array( ' (', '( ', ' )', ' {', '{ ', ' }', '[ ', ' ]', '; <' );
						$replace = array( '(', '(', ')', '{', '{', '}', '[' ,']', ';<' );
						return str_replace( $search, $replace, preg_replace( $pattern, '', $matches[0] ) );
					}
					$pattern = array( '/<script type=\"text\/javascript\">(.*?)<\/script>/si',
										'/<script>(.*?)<\/script>/si' );
					$to_parse = preg_replace_callback( $pattern, 'remover_comentarios', $to_parse );
					$search = array( "\t", "\n", "\r\n", "\r", '> <' );
					$replace = array( '', '', '', '', '> <' );
					$to_parse = str_replace( $search, $replace, $to_parse );
					$to_parse = preg_replace( '/\s+/', ' ', $to_parse );
					$this->buffer .= $to_parse;
				break;

				case 'discard':
				case 'clear':
				case 'drop':
					// do nothing, ob_clean() will simply discard it
				break;

				case 'copy':
				case '':
				default:
					$this->buffer .= ob_get_contents();
				break;
			}
			ob_clean();
		}
	}

	public function getJSONTranslations ( $translations ) {
		return $this->translator->getJSONTranslations( $translations );
	}

	public function getOptimalFilePath ( $path ) {
		$path = str_replace( ETC_BASE_URL, ETC_BASE_DIR, trim( $path ) );
		if ( ( strpos( $path, 'http://' ) !== 0 ) && ( strpos( $path, 'https://' ) !== 0 ) && ( strpos( $path, '../' ) !== 0 ) && ( strpos( $path, './' ) !== 0 ) ) {
			if ( $this->debug === FALSE ) {
				$first_extension = pathinfo( $path, PATHINFO_EXTENSION );
				if ( empty( $first_extension ) ) { // não tem nenhuma extensão, tenta adicionar '.min' no final
					if ( realpath( $path.'.min' ) ) {
						$path = realpath( $path .'.min' );
					}
				} else {
					$second_extension = pathinfo( pathinfo( $path, PATHINFO_FILENAME ), PATHINFO_EXTENSION );
					if ( $second_extension !== 'min' ) { // segunda extensão não existe ou é DIFERENTE de 'min'
						$directory = ltrim( dirname( $path ), '.' ); // remove o '.' no caso de não especificar diretório
						$minified_path = realpath( $directory.'/'.pathinfo( $path, PATHINFO_FILENAME ).'.min.'.$first_extension);
						if ( $minified_path ) {
							$path = $minified_path;
						}
					}
				}
			}
		}
		$path = str_replace( ETC_BASE_DIR, ETC_BASE_URL, trim( $path ) );
		return $path;
	}

	public function addCssToLoad ( $css_loc ) {
		$css_loc = $this->getOptimalFilePath( $css_loc );
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() this function must be called before '.__CLASS__.'::setHeaderUp() or '.__CLASS__.'::openHeader()' );
		} else {
			$this->css_files[] = trim( $css_loc );
		}
	}

	public function addInnerCss ( $css_code ) {
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() this function must be called before '.__CLASS__.'::setHeaderUp() or '.__CLASS__.'::openHeader()' );
		} else {
			$this->inner_css .= trim( $css_code )."\r\n";
		}
	}

	public function addJsToLoad ( $js_loc ) {
		$js_loc = $this->getOptimalFilePath( $js_loc );
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() this function must be called before '.__CLASS__.'::setHeaderUp() or '.__CLASS__.'::openHeader()' );
		} else {
			$this->js_files[] = trim( $js_loc );
		}
	}

	public function addInnerJs ( $js_code ) {
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() this function must be called before '.__CLASS__.'::setHeaderUp() or '.__CLASS__.'::openHeader()' );
		} else {
			$this->inner_js .= trim( $js_code )."\r\n";
		}
	}

	public function addJsTranslations ( $translations ) {
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() this function must be called before '.__CLASS__.'::setHeaderUp() or '.__CLASS__.'::openHeader()' );
		} else {
			$this->inner_js .= 'var translator = '.$this->getJSONTranslations( $translations )."\r\n";
		}
	}

	public function openHeader () {
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() duplicate header opening' );
		} else {
			ob_start();

			$this->printBeginOfHeader();
			$this->printCssToLoad();
			$this->printInnerCss();
			$this->printJsToLoad();
			$this->printInnerJs();

			$this->state = 11;
		}
	}

	public function closeHeader () {
		if ( $this->state !== 11 ) {
			$this->registerLog( 'error', __METHOD__.'() header not opened or duplicate header closing' );
		} else {
			$this->printEndOfHeader();

			$this->state = 2;
		}
	}

	public function setHeaderUp () {
		if ( $this->state !== 1 ) {
			$this->registerLog( 'error', __METHOD__.'() duplicate header' );
		} else {
			$this->openHeader();
			$this->closeHeader();
		}
	}

	public function setFooterUp () {
		if ( $this->state !== 2 ) {
			$this->registerLog( 'error', __METHOD__.'() header not set or duplicate footer' );
		} else {
			$this->printFooter();
			$this->state = 3;
			$this->parseBuffer();
			ob_end_clean();
			echo $this->buffer;
		}
	}

	/*
	▲▲▲ Public Methods - END ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
	■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
	▼▼▼ Protected Methods - START ▼▼▼▼▼▼▼▼▼▼▼
	*/

	protected function printBeginOfHeader () {
		// TODO make html lang dynamic instead of hardcoded
		?><!DOCTYPE html>
		<html lang="pt-BR">
		<head>
			<meta charset="UTF-8" />
			<meta name="referrer" content="no-referrer" />
			<!-- meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no" /-->
			<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1" />
			<meta name="author" content="Author" />
			<meta name="description" content="Website Name | Address | Postal code - City - State | Phone: +number | Email: email@project.com" />
			<title><?php echo $this->title; ?></title>
			<!-- base target="_blank" / -->
			<link rel="icon" href="<?php echo $this->base_loc; ?>images/favicon.png" />
		<?php
	}

	protected function printCssToLoad () {
		foreach ( $this->css_files as $css_file ) {
			?>
			<link type="text/css" rel="stylesheet" href="<?php echo $css_file; ?>" />
			<?php
		}
		?>
		<noscript>
			<link type="text/css" rel="stylesheet" href="<?php echo $this->base_loc.'include/css/noscript.css'; ?>" />
		</noscript>
		<?php
	}

	protected function printInnerCss () {
		if ( !empty( $this->inner_css ) ) {
			?>
			<style type="text/css">
				<?php echo $this->inner_css; ?>
			</style>
			<?php
		}
	}

	protected function printJsToLoad () {
		foreach ( $this->js_files as $js_file ) {
			?>
			<script type="text/javascript" charset="UTF-8" src="<?php echo $js_file; ?>"></script>
			<?php
		}
	}

	protected function printInnerJs () {
		$this->inner_js = trim( $this->inner_js );
		if ( !empty( $this->inner_js ) ) {
			?>
			<script type="text/javascript">
				<?php echo $this->inner_js; ?>
			</script>
			<?php
		}
	}

	protected function printEndOfHeader () {
		$classes = array( 'flex_col' );
		if ( count( array_intersect( $this->model, array( 'CENTERED' ) ) ) > 0 ) {
			$classes[] = 'flex_center';
		}
		?>
		</head>
		<body<?php echo empty( $classes ) ? '' : ' class="'.implode( ' ', $classes ).'"'; ?>>
			<?php
			if ( count( array_intersect( $this->model, array( 'HEADER' ) ) ) > 0 ) {
				$this->putHeader();
			}
			/*
			if ( count( array_intersect( $this->model, array( 'MENU' ) ) ) > 0 ) {
				$this->putMenu();
			}
			*/
			?>
			<main role="main">
		<?php
	}

	protected function printFooter () {
		?>
		</main>
		<?php
		if ( count( array_intersect( $this->model, array( 'FOOTER' ) ) ) > 0 ) {
			$this->putFooter();
		}
		?>
		</body>
		</html>
		<?php
	}

	protected function putHeader () {
		?>
		<header id="header" class="flex_fixed">
			<span id="title">Project</span>

			<nav id="menu" class="flex_fixed">
				<span class="nowrap">
					<button name="home">Home</button>
					<button name="about">About</button>
					<button name="help">Help</button>
				</span>
				<span class="nowrap">
					<button name="times">Opening Times</button>
					<button name="contact">Contact</button>
				</span>
			</nav>
		</header>
		<?php
	}

	protected function putContact () {
		?>
		<section id="contact" class="flex_fixed flex_row">
			<article class="flex_fixed">
				<p>Address</p>
				<p>Postal code - City - State</p>
				<p>+number - email</p>
			</article>
		</section>
		<?php
	}

	protected function putFooter () {
		?>
		<footer class="flex_fixed">
			<?php
			if ( count( array_intersect( $this->model, array( 'CONTACT' ) ) ) > 0 ) {
				$this->putContact();
			}
			?>
			<span id="disclaimer"><!-- Icons made by <a href="http://www.flaticon.com/authors/dave-gandy" title="Dave Gandy">Dave Gandy</a> from <a href="http://www.flaticon.com" title="Flaticon">flaticon.com</a> are licensed by <a href="http://creativecommons.org/licenses/by/3.0/" title="Creative Commons BY 3.0">CC 3.0 BY</a> --></span>
		</footer>
		<?php
	}

	public function putHorizontalSeparator () {
		?>
		<hr style="margin:0; border:0.1rem solid white;" />
		<?php
	}

	public function putSectionHeader ( $name = '' ) {
		if ( empty( $name ) ) {
			$name = $this->name;
		}
		?>
		<div id="section_header" class="bg_light tx_darkblue tx_bigger" style="height:30px; padding:4px; font-weight:bold; white-space:nowrap; overflow:hidden;"><?php echo $name; ?></div>
		<?php
	}

	public function putPathRuler ( $initial_inner_html = '' ) {
		?>
		<div id="regua" class="bg_light" style="height:22px; padding:4px; white-space:nowrap; overflow:hidden;"><?php echo $initial_inner_html; ?></div>
		<?php
	}

	protected function getFolderTreeProperty ( $type ) {
		global $user_id;
		global $pdo_handler;

		static $pastas_ids		= array();
		static $pastas_pais		= array();
		static $pastas_nomes	= array();

		if ( empty( $pastas_ids ) || empty( $pastas_pais ) || empty( $pastas_nomes ) ) {
			$pastas_ids[] = 1;
			$pastas_pais[] = 0;
			$pastas_nomes[] = $this->translator->getTranslation( 'lbl_my_documents' );
			$result = $pdo_handler->query( 'SELECT `pastas`.`name`, `pastas`.`pasta_id`, `pastas`.`pasta_pai`
											FROM `pastas`, `pastas_user`
											WHERE `pastas_user`.`user_id` = :user_id
											AND `pastas`.`pasta_id` = `pastas_user`.`pasta_id`
											AND ( `pastas`.`pasta_pai` = 0 OR `pastas`.`pasta_pai` = 1 )
											ORDER BY `pastas`.`name` ASC',
											array( ':user_id' => $user_id ) );
			if ( !empty( $result ) ) {
				foreach ( $result as $result_pastas ) {
					$pasta_id = intval( $result_pastas['pasta_id'], 10 );
					$pastas_ids[] = $pasta_id;
					$pastas_pais[] = 1;
					if ( strlen( $result_pastas['name'] ) > 14 ) {
						$pastas_nomes[] = substr( $result_pastas['name'], 0, 11 )."...";
					} else {
						$pastas_nomes[] = $result_pastas['name'];
					}
					$select_subpastas = $pdo_handler->query( 'SELECT `pastas`.`name`, `pastas`.`pasta_id`, `pastas`.`pasta_pai`
																FROM `pastas`, `pastas_user`
																WHERE `pastas_user`.`user_id` = :user_id
																AND `pastas_user`.`pasta_id` = `pastas`.`pasta_id`
																AND `pastas`.`pasta_pai` = :pasta_id
																ORDER BY `pastas`.`name`',
																array( ':user_id' => $user_id,
																		':pasta_id' => $pasta_id ) );
					if ( $select_subpastas ) {
						foreach( $select_subpastas as $result_subpastas ) {
							$subpasta_id = intval( $result_subpastas['pasta_id'], 10 );
							$pastas_ids[] = $subpasta_id;
							$pastas_pais[] = $pasta_id;
							if ( strlen( $result_subpastas['name'] ) > 15 ) {
								$pastas_nomes[] = substr( $result_subpastas['name'], 0, 12 ).'...';
							} else {
								$pastas_nomes[] = $result_subpastas['name'];
							}
						}
					}
				}
			}
		}

		switch ( strtolower( trim( $type ) ) ) {
			case 'folders':	return $pastas_ids;
			break;
			case 'parents':	return $pastas_pais;
			break;
			case 'names':	return $pastas_nomes;
			break;
		}
	}

	public function putFolderTree () {
		static $pastas_ids		= array();
		static $pastas_pais		= array();
		static $pastas_nomes	= array();

		if ( empty( $pastas_ids ) || empty( $pastas_pais ) || empty( $pastas_nomes ) ) {
			$pastas_ids		= $this->getFolderTreeProperty( 'folders' );
			$pastas_pais	= $this->getFolderTreeProperty( 'parents' );
			$pastas_nomes	= $this->getFolderTreeProperty( 'names' );
		}

		?>
		<div style="position:absolute; width:200px; white-space:nowrap;">
			<div class="not_displayed" style="position:absolute; width:350px; left:200px; background-color:#FFFFFF;">
				<?php
				if ( count( $pastas_ids ) === count( $pastas_pais ) && count( $pastas_pais ) === count( $pastas_nomes ) ) {
					?>
					<table class="list">
						<colgroup>
							<col span="1" style="width:50px;">
							<col span="1" style="width:50px;">
							<col span="1" style="width:50px;">
							<col span="1" style="width:200px;">
						</colgroup>
						<tr>
							<th>i</th>
							<th>ids</th>
							<th>pais</th>
							<th>nomes</th>
						</tr>
						<?php
						for ( $i = 0; $i < count( $pastas_ids ); ++$i ) {
							?>
							<tr>
								<td><?php echo $i; ?></td>
								<td><?php echo $pastas_ids[$i]; ?></td>
								<td><?php echo $pastas_pais[$i]; ?></td>
								<td><?php echo $pastas_nomes[$i]; ?></td>
							</tr>
							<?php
						}
					?>
					</table>
					<?php
				} else {
					echo 'ERROR: count mismatch in '.$pastas_ids.', '.$pastas_pais.', '.$pastas_nomes;
				}
				?>
			</div>
			<?php
			$lastLevel = 0;
			foreach ( $pastas_ids as $key => $value ) {
				if ( $pastas_pais[$key] > 0 ) { // É FILHO
					if ( $pastas_pais[array_search( $pastas_pais[$key], $pastas_ids )] > 0 ) { // É FILHO DE FILHO (E NÃO É PRA TER FILHO, porque é o limite)
						if ( $lastLevel > 2 ) {
							echo '</div>';
						}
						$lastLevel = 2;
						?>
						<div id="pasta_<?php echo $value; ?>" class="folder selection" title="<?php echo $pastas_nomes[$key]; ?>" onclick="folder_tree.seleciona( <?php echo $value; ?> );">
						<?php
						if ( in_array( $value, $pastas_pais ) ) { // TEM FILHO
							?>
							<div id="folder_icon_<?php echo $value; ?>" class="folder_icon folder_icon_closed"><!-- div just as an image placeholder --></div>
							<?php echo $pastas_nomes[$key]; ?>
							</div>
							<div id="filhos_<?php echo $value; ?>" class="folder folder_sub" style="display:none;">
							<?php
						} else { // NÃO TEM FILHO
							?>
							<div id="folder_icon_<?php echo $value; ?>" class="folder_icon folder_icon_no_child"><!-- div just as an image placeholder --></div>
							<?php echo $pastas_nomes[$key]; ?>
							</div>
							<?php
						}
					} else { // É FILHO
						if ( $lastLevel > 1 ) {
							echo '</div>';
						}
						$lastLevel = 1;
						?>
						<div id="pasta_<?php echo $value; ?>" class="folder selection" title="<?php echo $pastas_nomes[$key]; ?>" onclick="folder_tree.seleciona( <?php echo $value; ?> );">
						<?php
						if ( in_array( $value, $pastas_pais ) ) { // TEM FILHO
							?>
							<div id="folder_icon_<?php echo $value; ?>" class="folder_icon folder_icon_closed"><!-- div just as an image placeholder --></div>
							<?php echo $pastas_nomes[$key]; ?>
							</div>
							<div id="filhos_<?php echo $value; ?>" class="folder folder_sub" style="display:none;">
							<?php
						} else { // NÃO TEM FILHO
							?>
							<div id="folder_icon_<?php echo $value; ?>" class="folder_icon folder_icon_no_child"><!-- div just as an image placeholder --></div>
							<?php echo $pastas_nomes[$key]; ?>
							</div>
							<?php
						}
					}
				} else { // NÃO É FILHO (Meus Documentos)
					$lastLevel = 0;
					?>
					<div id="pasta_<?php echo $value; ?>" class="folder selection" title="<?php echo $pastas_nomes[$key]; ?>" onclick="folder_tree.seleciona( 1 );">
					<?php
					if ( in_array( $value, $pastas_pais ) ) { // TEM FILHO
						?>
						<div id="folder_icon_<?php echo $value; ?>" class="folder_icon folder_icon_opened"><!-- div just as an image placeholder --></div>
						<?php echo $pastas_nomes[$key]; ?>
						</div>
						<div id="filhos_<?php echo $value; ?>" class="folder folder_sub">
						<?php
					} else { // NÃO TEM FILHO
						?>
						<div id="folder_icon_<?php echo $value; ?>" class="folder_icon folder_icon_no_child"><!-- div just as an image placeholder --></div>
						<?php echo $pastas_nomes[$key]; ?>
						</div>
						<?php
					}
				}
			}
			for ( $i = $lastLevel; $i > 0; --$i ) {
				echo '</div>';
			}
			?>
		</div>
		<?php
	}
}

?>
