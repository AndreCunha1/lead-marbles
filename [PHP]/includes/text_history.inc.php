<?php

class TextHistory extends BasicClass {

	/* Properties */

	/*
	¯\_(ツ)_/¯
	*/


	/* Constructor & Destructor */

	public function __construct () {
		parent::__construct( get_class( $this ) );
		//array_unshift( $this->properties_to_set, 'ID', 'name' );
	}

	public function __destruct () {
		/*
		¯\_(ツ)_/¯
		*/
	}


	/* Public Static Methods */

	public static function table () {
		global $pdo_handler;
		$pdo_handler->query( "
			CREATE TABLE IF NOT EXISTS `etc_texts_history` (
			  `text_id` int(11) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `text` mediumtext COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (`text_id`,`user_id`,`timestamp`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		" );
	}

	public static function deleteOldEntries () {
		global $pdo_handler;
		// delete entries older than X keeping a minimum of Y entries for each
		// http://stackoverflow.com/questions/6092287/mysql-limit-rows-per-user-in-login-log-table/6092738#6092738
		$pdo_handler->query( "DELETE `etc_texts_history`.*
							FROM `etc_texts_history`
							INNER JOIN (
								SELECT l.text_id, l.timestamp
								FROM `etc_texts_history` AS l
								LEFT JOIN `etc_texts_history` AS t
									ON l.text_id = t.text_id
									AND l.timestamp <= t.timestamp
									AND l.timestamp < DATE_SUB( NOW(), INTERVAL 1 MONTH )
								GROUP BY 1, 2
								HAVING COUNT( 1 ) > 10
							) old_entries
								ON `etc_texts_history`.text_id = old_entries.text_id
								AND `etc_texts_history`.timestamp = old_entries.timestamp;" );
	}

	public static function printHTMLButton () {
		global $translator;
		?>
		<img id="text_history_button" class="pega" src="<?php echo ETC_BASE_URL; ?>images/editor/toolbar/btn_historico.png" alt="<?php echo $translator->getTranslation( 'hov_history' ); ?>" title="<?php echo $translator->getTranslation( 'hov_history' ); ?>" />
		<?php
	}

	public static function printHTML ( $text_id = 0 ) {
		global $translator;
		global $pdo_handler;
		?>
		<script type="text/javascript">
			'use strict';

			$( document ).ready( function () {
				$( window ).on( 'load', function () {
					textHistory.initialize();
				} );
			} );

			var textHistory = {
				initialize : function () {
					if ( typeof( textHistory.window ) === 'undefined' ) {
						textHistory.window = document.getElementById( 'text_history' );
					}
					if ( typeof( textHistory.reverted ) === 'undefined' ) {
						textHistory.reverted = false;
					}
					if ( typeof( textHistory.html_diff ) === 'undefined' ) {
						textHistory.html_diff = document.getElementById( 'text_history_html_diff' );
					}
					if ( typeof( textHistory.select_newer ) === 'undefined' ) {
						textHistory.select_newer = document.getElementById( 'text_history_select_newer' );
					}
					if ( typeof( textHistory.select_older ) === 'undefined' ) {
						textHistory.select_older = document.getElementById( 'text_history_select_older' );
					}
					if ( typeof( textHistory.selected_newer_version ) === 'undefined' ) {
						textHistory.selected_newer_version = -1;
					}
					if ( typeof( textHistory.selected_older_version ) === 'undefined' ) {
						textHistory.selected_older_version = -1;
					}
					if ( typeof( textHistory.button_show_diff ) === 'undefined' ) {
						textHistory.button_show_diff = document.getElementById( 'text_history_button_show_diff' );
					}
					if ( typeof( textHistory.button_revert ) === 'undefined' ) {
						textHistory.button_revert = document.getElementById( 'text_history_button_revert' );
					}
					if ( typeof( textHistory.text_id ) === 'undefined' ) {
						textHistory.text_id = <?php echo $text_id; ?>;
					}
					if ( typeof( textHistory.versions ) === 'undefined' ) {
						textHistory.versions = [];
					}
					if ( typeof( textHistory.revert_confirmation_count_initial ) === 'undefined' ) {
						textHistory.revert_confirmation_count_initial = 4;
					}
					if ( typeof( textHistory.revert_confirmation_count ) === 'undefined' ) {
						textHistory.revert_confirmation_count = textHistory.revert_confirmation_count_initial;
					}
					if ( typeof( textHistory.ajax ) === 'undefined' ) {
						textHistory.ajax = new XMLHttpRequest();
					}

					$( '#text_history' ).draggable( {
						addClasses: false,
						containment: 'window',
						handle: '.bx_title',
						/*cancel: '#text_history_toolbar, #text_history_content',*/
						scroll: false,
						disabled: false,
						stop: function () { this.style.width = this.style.height = 'initial'; }
					} );

					addEvent( document.getElementById( 'text_history_button' ), 'click', textHistory.show );
					addEvent( document.getElementById( 'text_history_button_close' ), 'click', textHistory.hide );
					addEvent( textHistory.button_show_diff, 'click', textHistory.receiveHTMLDiff );
					addEvent( textHistory.select_newer, 'change', textHistory.selectNewerVersion );
					addEvent( textHistory.select_older, 'change', textHistory.selectOlderVersion );
					addEvent( textHistory.button_revert, 'click', textHistory.revertConfirmation );
					addEvent( textHistory.button_revert, 'blur', textHistory.revertConfirmationReset );
					/* addEvent( textHistory.button_revert, 'mouseout', textHistory.revertConfirmationReset ); */
					addEvent( document.getElementById( 'text_history_button_save_as_pdf' ), 'click', textHistory.saveAsPDF );

					/* Initial Display */
					$( '#text_history_button_save_as_pdf' ).hide();
				},

				receiveVersions : function () {
					if ( textHistory.ajax.readyState === 0 || textHistory.ajax.readyState === 4 ) {

						var formData = new FormData();
						formData.append( 'function', 'versions' );
						formData.append( 'text_id', textHistory.text_id );

						textHistory.ajax.open( 'POST', '<?php echo ETC_BASE_URL; ?>include/php/ajax_text_history.php', true );
						textHistory.ajax.onreadystatechange = function () {
							if ( textHistory.ajax.readyState === 4 ) {
								/* console.log( 'TEXT HISTORY RECEIVE STATUS ('+textHistory.ajax.status+') > "'+textHistory.ajax.responseText+'"' ); */
								if ( textHistory.ajax.status === 200 ) {
									textHistory.versions = JSON.parse( textHistory.ajax.responseText );
									textHistory.processVersions();
								} else if ( textHistory.ajax.status !== 0 ) {
									console.log( 'TEXT HISTORY RECEIVE ERROR (#'+textHistory.ajax.status+') > '+textHistory.ajax.responseText );
								}
							}
						};
						textHistory.ajax.send( formData );
					}
				},

				processVersions : function () {
					if ( Object.prototype.toString.call( textHistory.versions ) === '[object Array]' ) {
						for ( var i = 0, j = textHistory.versions.length; i < j; ++i ) {
							var option = document.createElement( 'option' );
							option.value = i;
							option.innerHTML = 'Versão '+( j - i )+' '+textHistory.versions[i]['timestamp']+' por '+textHistory.versions[i]['user_name'];
							textHistory.select_newer.appendChild( option );
							textHistory.select_older.appendChild( option.cloneNode( true ) );
						}
						if ( textHistory.versions.length > 0 ) {
							textHistory.select_older.options[0].outerHTML = ''; /* Remove element LIKE A BOSS */
							/* textHistory.select_older.options.remove( 0 ); */
							if ( textHistory.versions.length >= 2 ) {
								textHistory.select_older.selectedIndex = 0;
								textHistory.select_older.value = parseInt( textHistory.select_older.options[0].value, 10 );
							}
							textHistory.selectNewerVersion();
						}
					} else {
						console.log( 'TEXT HISTORY ERROR: textHistory.versions is not an Array.' )
						console.log( textHistory.versions );
					}
				},

				selectNewerVersion : function () {
					if ( textHistory.select_newer.selectedIndex >= 0 ) {
						textHistory.selected_newer_version = parseInt( textHistory.select_newer.options[textHistory.select_newer.selectedIndex].value, 10 );
					} else {
						textHistory.selected_newer_version = -1;
					}
					textHistory.reprintOlderOptions();
				},


				selectOlderVersion : function () {
					if ( textHistory.select_older.selectedIndex >= 0 ) {
						textHistory.selected_older_version = parseInt( textHistory.select_older.options[textHistory.select_older.selectedIndex].value, 10 );
					} else {
						textHistory.selected_older_version = -1;
					}
					textHistory.checkViability();
				},

				checkViability : function () {
					if ( textHistory.selected_newer_version >= textHistory.selected_older_version || textHistory.selected_newer_version === -1 || textHistory.selected_older_version === -1 ) {
						textHistory.button_show_diff.disabled = true;
						textHistory.button_revert.disabled = true;
					} else {
						textHistory.button_show_diff.disabled = false;
						textHistory.button_revert.disabled = false;
					}
					if ( textHistory.selected_older_version >= 0 ) {
						textHistory.button_revert.disabled = false;
					}
				},

				reprintOlderOptions : function () {
					var initial_value;
					if ( textHistory.select_older.selectedIndex >= 0 ) {
						initial_value = parseInt( textHistory.select_older.options[textHistory.select_older.selectedIndex].value, 10 );
						if ( textHistory.selected_newer_version >= initial_value ) {
							if ( textHistory.selected_newer_version < textHistory.versions.length  ) {
								initial_value = textHistory.selected_newer_version + 1;
							} else {
								initial_value = -1;
							}
						}
					}
					textHistory.select_older.innerHTML = '';
					textHistory.select_older.selectedIndex = -1;
					textHistory.select_older.value = -1;
					for ( var i = textHistory.selected_newer_version + 1, j = textHistory.versions.length; i < j; ++i ) {
						var option = document.createElement( 'option' );
						option.value = i;
						option.innerHTML = 'Versão '+( j - i )+' '+textHistory.versions[i]['timestamp']+' por '+textHistory.versions[i]['user_name'];
						textHistory.select_older.appendChild( option );
					}
					textHistory.select_older.value = initial_value;
					textHistory.selectOlderVersion();
				},

				receiveHTMLDiff : function () {
					if ( textHistory.selected_newer_version >= textHistory.selected_older_version || textHistory.selected_newer_version === -1 || textHistory.selected_older_version === -1 ) {
						alert( 'Selecione versões apropriadas primeiro' );
						return;
					}
					if ( textHistory.ajax.readyState === 0 || textHistory.ajax.readyState === 4 ) {
						textHistory.disableElements();

						textHistory.html_diff.innerHTML = '<img src="<?php echo ETC_BASE_URL; ?>images/ico_loading_blue.gif">';

						var formData = new FormData();
						formData.append( 'function', 'htmldiff' );
						formData.append( 'text_id', textHistory.text_id );
						formData.append( 'selected_newer', textHistory.selected_newer_version );
						formData.append( 'selected_older', textHistory.selected_older_version );

						textHistory.ajax.open( 'POST', '<?php echo ETC_BASE_URL; ?>include/php/ajax_text_history.php', true );
						textHistory.ajax.onreadystatechange = function () {
							if ( textHistory.ajax.readyState === 4 ) {
								/* console.log( 'TEXT HISTORY RECEIVE STATUS ('+textHistory.ajax.status+') > "'+textHistory.ajax.responseText+'"' ); */
								if ( textHistory.ajax.status === 200 ) {
									if ( textHistory.ajax.responseText.length > 0 ) {
										textHistory.html_diff.innerHTML = textHistory.ajax.responseText;
										$( '#text_history_button_save_as_pdf' ).show();
									} else {
										textHistory.html_diff.innerHTML = 'Não existem diferenças entre as versões selecionadas';
										$( '#text_history_button_save_as_pdf' ).hide();
									}
									textHistory.enableElements();
								} else if ( textHistory.ajax.status !== 0 ) {
									console.log( 'TEXT HISTORY RECEIVE ERROR (#'+textHistory.ajax.status+') > '+textHistory.ajax.responseText );
								}
							}
						};
						textHistory.ajax.send( formData );
					}
				},

				revertVersion : function () {
					if ( textHistory.ajax.readyState === 0 || textHistory.ajax.readyState === 4 ) {

						var formData = new FormData();
						formData.append( 'function', 'revert' );
						formData.append( 'text_id', textHistory.text_id );
						formData.append( 'version_number_revert', textHistory.selected_older_version );

						textHistory.ajax.open( 'POST', '<?php echo ETC_BASE_URL; ?>include/php/ajax_text_history.php', true );
						textHistory.ajax.onreadystatechange = function () {
							if ( textHistory.ajax.readyState === 4 ) {
								/* console.log( 'TEXT HISTORY RECEIVE STATUS ('+textHistory.ajax.status+') > "'+textHistory.ajax.responseText+'"' ); */
								if ( textHistory.ajax.status === 200 ) {
									switch ( textHistory.ajax.responseText ) {
										case 'OK':
											textHistory.html_diff.innerHTML = 'Atualize a página caso seu navegador não atualize automaticamente';
											var root = window;
											while ( root.parent != root ) {
												root = root.parent;
											}
											root.location.reload( true );
										break;

										case 'FAIL':
										default:
											/* Error! */
											textHistory.html_diff.innerHTML = 'Error, sinto muito ):';
										break;
									}
								} else if ( textHistory.ajax.status !== 0 ) {
									console.log( 'TEXT HISTORY RECEIVE ERROR (#'+textHistory.ajax.status+') > '+textHistory.ajax.responseText );
								}
							}
						};
						textHistory.ajax.send( formData );
					}
				},

				revertConfirmationReset : function () {
					textHistory.revert_confirmation_count = textHistory.revert_confirmation_count_initial;
					if ( textHistory.reverted === false ) {
						document.getElementById( 'text_history_button_revert' ).textContent = 'Reverter à anterior';
					}
				},

				revertConfirmation : function () {
					--textHistory.revert_confirmation_count;
					if ( textHistory.revert_confirmation_count <= 0 ) {
						textHistory.reverted = true;
						document.getElementById( 'text_history_button_revert' ).textContent = 'Revertendo...';;
						$( '#text_history_button_save_as_pdf' ).hide();
						textHistory.disableElements();
						textHistory.revertVersion();
					} else {
						if ( textHistory.revert_confirmation_count > 1 ) {
							document.getElementById( 'text_history_button_revert' ).textContent = 'Tem certeza? ('+textHistory.revert_confirmation_count+')';
						} else {
							document.getElementById( 'text_history_button_revert' ).textContent = 'Tem certeza?';
						}
					}
				},

				disableElements : function () {
					textHistory.select_newer.disabled = true;
					textHistory.select_older.disabled = true;
					textHistory.button_show_diff.disabled = true;
					textHistory.button_revert.disabled = true;
				},

				enableElements : function () {
					textHistory.select_newer.disabled = false;
					textHistory.select_older.disabled = false;
					textHistory.button_show_diff.disabled = false;
					textHistory.button_revert.disabled = false;
				},

				saveAsPDF : function () {
					document.getElementById( 'text_history_pdf_encoded_filename' ).value = encodeURIComponent( 'ETC_-_Historico_Texto_'+textHistory.text_id );
					document.getElementById( 'text_history_pdf_encoded_html' ).value = encodeURIComponent( textHistory.html_diff.innerHTML );
					document.getElementById( 'text_history_save_as_pdf_form' ).submit();
				},

				show : function () {
					if ( textHistory.versions.length === 0 ) {
						textHistory.receiveVersions();
					}
					$( '#text_history' ).show();
				},

				hide : function () {
					$( '#text_history' ).hide();
				},
			}
		</script>

		<div id="text_history" class="flex_col bx_border" style="flex-wrap:nowrap; display:none; position:fixed; left:200px; top:180px;">
			<div class="flex_fixed bx_title" style="cursor:move;">
				<?php echo $translator->getTranslation( 'lbl_history' ); ?>
				<img id="text_history_button_close" src="<?php echo ETC_BASE_URL; ?>images/ico_no.png" class="fill_dark" style="float:right; cursor:pointer;" />
			</div>
			<div id="text_history_toolbar" class="flex_fixed flex_col bg_light" style="text-align:center;">
				<div class="flex_row" style="flex-wrap:nowrap;">
					<div class="flex_fixed" style="padding:8px;">
						<label name="label" style="display:block; height:20px;">Versão recente</label>
						<label name="label" style="display:block; height:20px; margin-top:8px;">Versão anterior</label>
					</div>
					<div class="flex_col" style="padding:8px;">
						<select id="text_history_select_newer"></select>
						<select id="text_history_select_older" style="margin-top:8px;"></select>
					</div>
				</div>
				<div style="text-align:center; padding:8px;">
					<button id="text_history_button_show_diff" type="button" style="margin:0 32px;">Exibir diferenças</button>
					<button id="text_history_button_revert" type="button" style="margin:0 32px;">Reverter à anterior</button>
					<img id="text_history_button_save_as_pdf" src="<?php echo ETC_BASE_URL; ?>images/editor/toolbar/btn_salvar_como_pdf.png" title="Salvar diferenças em PDF" class="selection" style="float:right; cursor:pointer;"/>
					<form id="text_history_save_as_pdf_form" name="text_history_save_as_pdf_form" method="post" target="_blank" action="<?php echo ETC_BASE_URL; ?>include/php/generate_pdf.php" class="not_displayed">
						<input type="hidden" id="text_history_pdf_encoded_filename" name="pdf_encoded_filename" />
						<input type="hidden" id="text_history_pdf_encoded_html" name="pdf_encoded_html" />
					</form>
				</div>
			</div>
			<div id="text_history_content" class="bg_dark resizable" style="min-width:600px; min-height:260px; max-width:801px; max-height:600px; padding:8px;">
				<div class="scrollable" style="height:100%; overflow:auto;">
					<!-- HTML Diff -->
					<div id="text_history_html_diff" class="text_view bg_white">
						[Verificar Diferenças]<br />
						1. Selecione nas listas acima duas versões diferentes<br />
						2. Clique em "Exibir diferenças" para mostrar as diferenças<br />
						<br />
						[Restaurar Versão]<br />
						1. Selecione na segunda lista ("Versão anterior") a versão desejada<br />
						2. Clique em "Reverter à anterior" e, se necessário, novamente para confirmar
					</div>
				</div>
			</div>
		</div>
		<?php
	}

}

?>
