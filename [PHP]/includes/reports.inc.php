<?php

class Reports extends BasicClass {

	/* [ CONSTANTS ] */

	//const VALID_UNITS = array( 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR' ); // const array(): AVAILABLE IN PHP 5.6


	/* [ PROPERTIES ] */

	protected static $valid_units = array( 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR' );
	protected static $available_units = array( 'DAY', 'MONTH', 'YEAR' ); // subset of $valid_units
	protected static $available_actions = array( 'edit', 'access', 'alter', 'comment', 'create', 'reply', 'send' );


	/* [ CONSTRUCTOR & DESTRUCTOR ] */

	public function __construct () {
		parent::__construct( get_class( $this ) );
		//array_unshift( $this->properties_to_set, 'ID', 'name' );
	}

	public function __destruct () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* [ PROTECTED METHODS ] */

	protected static function error () {
		/*
		¯\_(ツ)_/¯
		*/
	}

	protected static function table () {
		/*
		¯\_(ツ)_/¯
		*/
	}

	protected static function deleteOldEntries () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* [ PUBLIC METHODS ] */

	public static function printHTMLButton () {
		global $translator;
		HTMLPage::insertLabelledButton( array( 'id' => 'reports_button', 'icon-left' => ETC_BASE_URL.'images/menu/ico_reports.png', 'icon-left-style' => 'height:16px;', 'label' => $translator->getTranslation( 'lbl_reports' ), 'background-image' => ETC_BASE_URL.'images/btn_base_white.png', 'class' => 'tx_small tx_darkblue', 'style' => 'margin-right:4px; padding:4px; font-weight:bold;' ) );
	}

	public static function printHTML ( $function = '' ) {
		global $translator;
		global $pdo_handler;
		?>
		<script>
			'use strict';

			$( document ).ready( function () {
				$( window ).on( 'load', function () {
					reports.initialize();
				} );

				$( '#entrada_consulta' ).keyup( function () {
					reports.selected_user_id = 0;
					var entrada = document.getElementById( 'entrada_consulta' ).value;
					var modo = document.querySelector( 'input[name="searchMode"]:checked' );
					/* console.log( modo.value ); */
					if ( entrada.length > 2 ) {
						fazConsulta( document.getElementById( 'entrada_consulta' ).value, modo.value );
					} else {
						document.getElementById( 'ul_consulta' ).style.display = 'none';
					}
				} );
			} );

			var http_re = new XMLHttpRequest();
			function fazConsulta ( nome, modo ) {
				http_re.abort();
				var consultaDados = new FormData();
				consultaDados.append( 'nome', nome );
				consultaDados.append( 'pid', folder_tree.pastaSelecionada );
				consultaDados.append( 'achasi', 1 );
				http_re.open( 'POST', '../include/php/consulta_nomes.php', true );
				http_re.onreadystatechange = function () {
					if ( ( http_re.readyState === 4 ) && ( http_re.status === 200 ) ) {
						var dados = JSON.parse( http_re.responseText );
						document.getElementById( 'ul_consulta' ).innerHTML = '';
						for ( var i in dados.usuarios ) {
							/* pega dados do usuário */
							var id    = dados.usuarios[i].id;
							var nome  = dados.usuarios[i].nome;
							var foto  = dados.usuarios[i].foto;
							var email = dados.usuarios[i].email;

							/* verifica se o id do usuário já está incluído */
							/*
							var checkboxes = document.getElementsByTagName( 'input' );
							var id_encontrado = false;
							for ( i = 0; i < checkboxes.length; i++ ) {
								if ( ( checkboxes[i].type == 'checkbox' ) && ( !id_encontrado ) ) {
									if ( checkboxes[i].id.split( ':' )[1] == id ) {
										id_encontrado = true;
									}
								}
							}
							*/
							var id_encontrado = false; /* hack do comment acima */
							if ( !id_encontrado ) {
								/* clona o modelo da div de apresentação de cada usuário */
								var usuario = document.getElementById( 'modelo_li_0' ).cloneNode( true );
								/* prepara as características próprias desta div de apresentação */
								var li_id = 'li_'+id;
								usuario.id = li_id;
								usuario.onClick = 'selecionaUsuario( '+id+', "'+nome+'" )';
								usuario.getElementsByTagName( 'img' )[0].src = '../archive/avatar/'+foto;
								usuario.style.display = 'block';
								usuario.setAttribute( 'onclick', 'selecionaUsuario( '+id+', "'+nome+'" )');
								usuario.setAttribute( 'class', 'selection');
								if ( nome.length > 40 ) {		/* corta o nome para caber na visualização da busca */
									nome = nome.substring( 0, 37 );
									nome = nome+'...';
								}
								usuario.getElementsByTagName( 'div' )[1].innerHTML = nome;

								/* coloca na lista de resultados */
								document.getElementById( 'ul_consulta' ).appendChild( usuario );
							}
						}

						if ( document.getElementById( 'ul_consulta' ).children.length == 0 ) {
							document.getElementById( 'ul_consulta' ).innerHTML = 'Sem resultados';
							setTimeout( function () {
								document.getElementById( 'ul_consulta' ).style.display = 'none';
							}, 1000 );
						}

						document.getElementById( 'ul_consulta' ).style.display = 'block';
					}
				}
				http_re.send( consultaDados );
			}

			function selecionaUsuario ( id, nome ) {
				reports.selected_user_id = parseInt( id, 10 );
				document.getElementById( 'entrada_consulta' ).value = nome;
				document.getElementById( 'ul_consulta' ).style.display = 'none';
			}

			var reports = {
				initialize : function () {
					if ( typeof( reports.initialized ) === 'undefined' ) {
						/* Initialization Errors */
						if ( document.getElementById( 'reports_select_action' ).options.length < 1 ) {
							addEvent( document.getElementById( 'reports_button' ), 'click', function () { top.alert( 'reports.inc.php: no available actions, check $available_actions' ) } );
							return;
						}

						reports.initialized = true;

						/* Attributes */
						reports.window = document.getElementById( 'reports' );
						reports.report = document.getElementById( 'reports_report' );
						reports.function = '<?php echo $function; ?>';
						reports.select_action = document.getElementById( 'reports_select_action' );
						reports.select_date_how_many_units_ago = document.getElementById( 'reports_select_date_how_many_units_ago' );
						reports.select_date_unit_ago = document.getElementById( 'reports_select_date_unit_ago' );
						reports.select_date_group_by = document.getElementById( 'reports_select_date_group_by' );
						reports.selected_action = reports.select_action.options[reports.select_action.selectedIndex].value;
						reports.checkbox_search_by_user = document.getElementById( 'reports_checkbox_search_by_user' );
						reports.search_by_user = reports.checkbox_search_by_user.checked;
						reports.style = '';
						reports.selected_user_id = 0;
						reports.selected_object_id = 0;
						reports.selected_date_how_many_units_ago = reports.select_date_how_many_units_ago.options[reports.select_date_how_many_units_ago.selectedIndex].value;
						reports.selected_date_unit_ago = reports.select_date_unit_ago.options[reports.select_date_unit_ago.selectedIndex].value;
						reports.selected_date_group_by = reports.select_date_group_by.options[reports.select_date_group_by.selectedIndex].value;
						reports.button_show_report_chart = document.getElementById( 'reports_button_show_report_chart' );
						reports.button_show_report_table = document.getElementById( 'reports_button_show_report_table' );
						reports.ajax = new XMLHttpRequest();

						/* Elements Properties */
						$( '#reports' ).draggable( { containment:'window', cancel:'#reports_toolbar, #reports_report', scroll:false, disabled:false } );

						/* Events */
						addEvent( document.getElementById( 'reports_button' ), 'click', reports.show );
						addEvent( document.getElementById( 'reports_button_close' ), 'click', reports.hide );
						addEvent( reports.checkbox_search_by_user, 'click', reports.searchByUserCheckboxControl );
						addEvent( reports.checkbox_search_by_user, 'change', reports.searchByUserCheckboxControl );
						addEvent( reports.button_show_report_chart, 'click', reports.receiveChartReport );
						addEvent( reports.button_show_report_table, 'click', reports.receiveTableReport );
						addEvent( document.getElementById( 'reports_button_save_as_pdf' ), 'click', reports.saveAsPDF );

						/* Initial Display */
						$( '#reports_button_save_as_pdf' ).hide();
						reports.searchByUserCheckboxControl();
					}
				},

				receiveChartReport : function () {
					reports.style = 'chart';
					reports.receiveHTMLReport();
				},

				receiveTableReport : function () {
					reports.style = 'table';
					reports.receiveHTMLReport();
				},

				receiveHTMLReport : function () {
					if ( reports.ajax.readyState === 0 || reports.ajax.readyState === 4 ) {
						reports.gatherParameters();
						if ( reports.validateParameters() === false ) {
							return;
						}
						$( '#reports_toolbar' ).find( 'button, input, select' ).prop( 'disabled', true );

						var formData = new FormData();
						formData.append( 'function', reports.function );
						formData.append( 'action', reports.selected_action );
						formData.append( 'user_id', ( ( reports.search_by_user === true ) ? reports.selected_user_id : 0 ) );
						formData.append( 'object_id', reports.selected_object_id );
						formData.append( 'date_how_many_units_ago', reports.selected_date_how_many_units_ago );
						formData.append( 'date_unit_ago', reports.selected_date_unit_ago );
						formData.append( 'date_group_by', reports.selected_date_group_by );
						formData.append( 'style', reports.style );

						reports.ajax.open( 'POST', '../include/php/ajax_reports.php', true );
						reports.ajax.onreadystatechange = function () {
							if ( reports.ajax.readyState === 4 ) {
								/* console.log( 'REPORTS RECEIVE STATUS ('+reports.ajax.status+') > "'+reports.ajax.responseText+'"' ); */
								if ( reports.ajax.status === 200 ) {
									if ( reports.ajax.responseText.length > 0 ) {
										reports.report.innerHTML = reports.ajax.responseText;
										$( '#reports_button_save_as_pdf' ).show();
									} else {
										reports.report.innerHTML = 'Não existem registros para a configuração selecionada';
										$( '#reports_button_save_as_pdf' ).hide();
									}
								} else if ( reports.ajax.status !== 0 ) {
									console.log( 'REPORTS RECEIVE ERROR (#'+reports.ajax.status+') > '+reports.ajax.responseText );
								}
								$( '#reports_toolbar' ).find( 'button, input, select' ).prop( 'disabled', false );
								reports.searchByUserCheckboxControl();
							}
						};
						reports.ajax.send( formData );
					}
				},

				gatherParameters : function () {
					reports.search_by_user = reports.checkbox_search_by_user.checked;
					/* reports.function */
					reports.selected_action = reports.select_action.options[reports.select_action.selectedIndex].value;
					/* reports.selected_user_id; */
					switch ( reports.function ) {
						case 'folder':
						break;
						case 'text':	reports.selected_object_id = parseInt( Object.keys( selectedTextID() )[0], 10 );
						break;
						case 'library':
						break;
						case 'forum':	reports.selected_object_id = parseInt( get_radio_value(), 10 );
						break;
						case 'message':
						break;
					}
					reports.selected_date_how_many_units_ago = reports.select_date_how_many_units_ago.options[reports.select_date_how_many_units_ago.selectedIndex].value;
					reports.selected_date_unit_ago = reports.select_date_unit_ago.options[reports.select_date_unit_ago.selectedIndex].value;
					reports.selected_date_group_by = reports.select_date_group_by.options[reports.select_date_group_by.selectedIndex].value;
				},

				validateParameters : function () {
					if ( reports.search_by_user === true ) {
						if ( reports.selected_user_id === 0 ) {
							alert( 'Usuário não selecionado corretamente' );
							return false;
						}
					}
					if ( reports.selected_object_id === 0 || isNaN( reports.selected_object_id ) === true || typeof( reports.selected_object_id ) === 'undefined' ) {
						switch ( reports.function ) {
							case 'folder':
							break;
							case 'text':	alert( 'Texto não selecionado corretamente' );
							break;
							case 'library':
							break;
							case 'forum':	alert( 'Tópico não selecionado corretamente' );
							break;
							case 'message':
							break;
						}
						return false;
					}
					return true;
				},

				saveAsPDF : function () {
					document.getElementById( 'reports_pdf_encoded_filename' ).value = encodeURIComponent( 'ETC_-_Relatorio_'+reports.selected_object_id );
					document.getElementById( 'reports_pdf_encoded_html' ).value = encodeURIComponent( reports.report.innerHTML );
					document.getElementById( 'reports_save_as_pdf_form' ).submit();
				},

				searchByUserCheckboxControl : function () {
					if ( reports.checkbox_search_by_user.checked === true ) {
						$( 'input#entrada_consulta, input[name="searchMode"]' ).prop( 'disabled', false );
					} else {
						$( 'input#entrada_consulta, input[name="searchMode"]' ).prop( 'disabled', true );
					}
				},

				show : function () {
					$( '#reports' ).show();
				},

				hide : function () {
					$( '#reports' ).hide();
				},
			}
		</script>

		<div id="reports" class="scrollable bx_border" style="display:none; position:fixed; left:600px; top:180px;">
			<div class="bx_title" style="cursor:move;">
				<?php echo $translator->getTranslation( 'lbl_reports' ); ?>
				<img id="reports_button_close" src="<?php echo ETC_BASE_URL; ?>images/ico_no.png" class="fill_dark" style="float:right; cursor:pointer;" />
			</div>
			<div class="flex_col">
				<div id="reports_toolbar" class="flex_fixed bg_light" style="padding:8px;">
					<div class="flex_row flex_spacing">
						<div class="flex_fixed" style="text-align:right;">
							<label style="display:block; height:20px;">Função</label>
							<label style="display:block; height:20px; margin-top:8px;">Ação</label>
							<label style="display:block; height:20px; margin-top:8px;">Idade máxima</label>
							<label style="display:block; height:20px; margin-top:8px;">Agrupar por</label>
							<label style="display:block; height:20px; margin-top:8px;">
								<input type="checkbox" id="reports_checkbox_search_by_user">
								Usuário
							</label>
						</div>

						<div>
							<label style="display:block; height:20px; text-align:center;"><?php echo $translator->getTranslation( 'lbl_reports_function_'.$function ); ?></label>
							<select id="reports_select_action" style="width:100%; margin-top:8px;">
								<?php
								foreach ( array_intersect( Events::getActionsOfFunction( $function ), self::$available_actions ) as $action ) {
									?>
									<option value="<?php echo $action; ?>"><?php echo $translator->getTranslation( 'lbl_reports_'.$action ); ?></option>
									<?php
								}
								?>
							</select>
							<div style="position:relative; margin-top:8px;">
								<div style="width:60px;">
									<select id="reports_select_date_how_many_units_ago" style="width:100%;">
										<?php
										for ( $i = 1; $i <= 24; ++$i ) {
											?>
											<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
											<?php
										}
										?>
									</select>
								</div>
								<div style="position:absolute; left:60px; right:0px; top:0px; bottom:0px;">
									<select id="reports_select_date_unit_ago" style="width:100%;">
										<?php
										foreach ( array_intersect( self::$valid_units, self::$available_units ) as $unit ) {
											?>
											<option value="<?php echo $unit; ?>"><?php echo $translator->getTranslation( 'lbl_reports_'.$unit ); ?></option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
							<select id="reports_select_date_group_by" style="width:100%; margin-top:8px;">
								<?php
								foreach ( array_intersect( self::$valid_units, self::$available_units ) as $unit ) {
									?>
									<option value="<?php echo $unit; ?>"><?php echo $translator->getTranslation( 'lbl_reports_'.$unit ); ?></option>
									<?php
								}
								?>
							</select>
							<div style="margin-top:8px;">
								<input type="text" id="entrada_consulta" name="part_search_word" placeholder="Busque por um usuário" style="width:100%;" />
								<div id="resultado_consulta" class="bg_white" style="position:absolute; z-index:1;">
									<ul id="ul_consulta" style="display:none; padding:2px; margin:0px; border:1px solid silver;">
									</ul>
									<!-- modelo para ser clonado... preguiça desde o início com métodos DOM -->
									<li id="modelo_li_0" style="display:none; margin:2px; padding:2px;">
										<div class="div_thumb" style="display:inline" >
											<img src="" width="30" height="30" />
										</div>
										<div class="div_nome" style="display:inline;"></div>
									</li>
								</div>
								<label><input type="radio" name="searchMode" value="0" checked="checked"><?php echo $translator->getTranslation( 'lbl_name' ); ?></label>
								<label><input type="radio" name="searchMode" value="1"><?php echo $translator->getTranslation( 'lbl_email' ); ?></label>
							</div>
						</div>
					</div>

					<div style="padding:8px; text-align:center;">
						<button id="reports_button_show_report_chart" type="button">Gerar gráfico</button>
						<button id="reports_button_show_report_table" type="button">Gerar tabela</button>
						<img id="reports_button_save_as_pdf" src="<?php echo ETC_BASE_URL; ?>images/editor/toolbar/btn_salvar_como_pdf.png" title="Salvar gráfico em PDF" class="selection" style="float:right; zoom:1.4; max-height:20px;"/>
						<form id="reports_save_as_pdf_form" name="reports_save_as_pdf_form" method="post" target="_blank" action="<?php echo ETC_BASE_URL; ?>include/php/generate_pdf.php" class="not_displayed">
							<input type="hidden" id="reports_pdf_encoded_filename" name="pdf_encoded_filename" />
							<input type="hidden" id="reports_pdf_encoded_html" name="pdf_encoded_html" />
						</form>
					</div>
				</div>

				<div id="reports_report" class="scrollable bg_white" style="min-width:380px; min-height:120px; max-width:800px; max-height:600px; overflow:auto; text-align:center; resize:both;">
					[Exibir Relatório]<br />
					1. Selecione a ação a ser considerada<br />
					2. Selecione a idade máxima para os registros<br />
					3. Selecione a unidade de tempo para agrupamento dos registros<br />
					4. Se desejar, busque e selecione o usuário autor das ações<br />
					5.1. Clique em "Gerar gráfico" para exibir em modo gráfico<br />
					5.2. Clique em "Gerar tabela" para exibir em modo texto<br />
				</div>
			</div>
		</div>
		<?php
	}


	/* [ CRUD ] */

	public static function create () {}

	public static function read () {}

	public static function update () {}

	public static function delete () {}

}

?>
