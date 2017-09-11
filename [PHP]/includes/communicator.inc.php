<?php

class Communicator extends BasicClass { // Communicator & Text Notes

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
	/*
	public static function table () {
		global $pdo_handler;
		$pdo_handler->query( "
			CREATE TABLE IF NOT EXISTS `etc_communicator` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `from_user_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `to_folder_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `to_text_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `message` varchar(10240) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
		" );
		$pdo_handler->query( "
			CREATE TABLE IF NOT EXISTS `etc_text_notes` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `from_user_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `to_text_id` int(10) unsigned NOT NULL DEFAULT '0',
			  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `note` varchar(10240) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
		" );
	}
	*/

	public static function deleteOldEntries () {
		global $pdo_handler;
		$pdo_handler->query( "DELETE FROM `etc_communicator` WHERE `timestamp` < DATE_SUB( NOW(), INTERVAL 1 MONTH );" );
	}

	public static function printHTML ( $text_id = 0 ) {
		global $translator;
		global $pdo_handler;

		?>
		<script type="text/javascript">
			'use strict';

			$( document ).ready( function () {
				$( window ).on( 'load', function () {
					communicator.initialize();
				} );
			} );

			var communicator = {
				initialize : function () {
					if ( typeof( communicator.communicator ) === 'undefined' ) {
						communicator.communicator = document.getElementById( 'communicator' );
					}
					if ( typeof( communicator.communicator_closed ) === 'undefined' ) {
						communicator.communicator_closed = document.getElementById( 'communicator_closed' );
					}
					if ( typeof( communicator.communicator_messages ) === 'undefined' ) {
						communicator.communicator_messages = document.getElementById( 'communicator_messages' );
					}
					if ( typeof( communicator.communicator_input_message ) === 'undefined' ) {
						communicator.communicator_input_message = document.getElementById( 'communicator_input_message' );
					}
					if ( typeof( communicator.room_type ) === 'undefined' ) {
						communicator.room_type = '<?php echo $text_id === 0 ? 'folder' : 'text'; ?>';
					}
					if ( typeof( communicator.room_id ) === 'undefined' ) {
						communicator.room_id = '<?php echo $text_id === 0 ? 'folder' : $text_id; ?>';
						communicator.room_id = communicator.room_id === 'folder' ? folder_tree.pastaSelecionada : communicator.room_id;
					}
					if ( typeof( communicator.room_label ) === 'undefined' ) {
						communicator.room_label = document.getElementById( 'communicator_room_title' ).children.label;
					}
					if ( typeof( communicator.last_timestamp ) === 'undefined' ) {
						/*communicator.last_timestamp = '1970-01-01 00:00:01';*/
						communicator.last_timestamp = '<?php echo $pdo_handler->now(); ?>';
					}
					if ( typeof( communicator.change_room_interval_id ) === 'undefined' ) {
						communicator.change_room_interval_id = 0;
					}
					if ( typeof( communicator.update_interval_id ) === 'undefined' ) {
						communicator.update_interval_id = 0;
					}
					if ( typeof( communicator.receive_all_messages ) === 'undefined' ) {
						communicator.receive_all_messages = false;
					}
					if ( typeof( communicator.ajax_receive_messages ) === 'undefined' ) {
						communicator.ajax_receive_messages = new XMLHttpRequest();
					}
					if ( typeof( communicator.ajax_send_message ) === 'undefined' ) {
						communicator.ajax_send_message = new XMLHttpRequest();
					}
					if ( typeof( communicator.ajax_send_alert ) === 'undefined' ) {
						communicator.ajax_send_alert = new XMLHttpRequest();
					}
					if ( typeof( communicator.unviewed_count ) === 'undefined' ) {
						communicator.unviewed_count = 0;
					}
					if ( typeof( communicator.notification_sound ) === 'undefined' ) {
						communicator.notification_sound = new Audio();
						if ( communicator.notification_sound.canPlayType( 'audio/wav' ) !== '' ) {
							communicator.notification_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/notification.wav';
						} else if ( communicator.notification_sound.canPlayType( 'audio/ogg' ) !== '' ) {
							communicator.notification_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/notification.ogg';
						} else if ( communicator.notification_sound.canPlayType( 'audio/mp3' ) !== '' ) {
							communicator.notification_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/notification.mp3';
						}
					}
					if ( typeof( communicator.alert_sound ) === 'undefined' ) {
						communicator.alert_sound = new Audio();
						if ( communicator.alert_sound.canPlayType( 'audio/wav' ) !== '' ) {
							communicator.alert_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/alert.wav';
						} else if ( communicator.alert_sound.canPlayType( 'audio/ogg' ) !== '' ) {
							communicator.alert_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/alert.ogg';
						} else if ( communicator.alert_sound.canPlayType( 'audio/mp3' ) !== '' ) {
							communicator.alert_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/alert.mp3';
						}
					}

					$( '#communicator' ).draggable( { containment:'window', cancel:'#communicator_content', scroll:false, disabled:false } );

					addEvent( communicator.communicator_closed, 'click', communicator.expand );
					addEvent( document.getElementById( 'communicator_close_button' ), 'click', communicator.retract );
					addEvent( document.getElementById( 'communicator_send_alert_button' ), 'click', communicator.sendAlert );
					addEvent( document.getElementById( 'communicator_save_as_pdf_button' ), 'click', communicator.saveAsPDF );

					/* Message Send Event */
					$( '#communicator_input_message' ).on( 'keydown', function ( event ) {
						var keyCode = event.keyCode ? event.keyCode : event.charCode ? event.charCode : event.which;
						if ( !event.shiftKey && keyCode === 13 ) {
							event.preventDefault();
							communicator.sendMessage();
						}
					} );

					/* Message Fetch Interval */
					communicator.update_interval_id = window.setInterval( function () {
						communicator.receiveMessages();
						document.getElementById( 'communicator_users' ).innerHTML = document.getElementById( 'status' ).innerHTML; /* GAMB */
					}, 5000 );

					/* Initial Display */
					communicator.updateRoomName();
					communicator.clearMessages();
					communicator.retract();
					floatElementOnScroll( 'communicator_closed', document.getElementById( 'menu_esquerda' ).offsetHeight );
				},

				updateLastTimestamp : function () {
					/* HTML5: communicator.communicator_messages.lastElementChild.dataset.timestamp */
					var last_message = communicator.communicator_messages.lastElementChild;
					if ( last_message !== null ) {
						communicator.last_timestamp = last_message.getAttribute( 'data-timestamp' );
					}
				},

				scroll : function ( where ) {
					switch ( where ) {
						case 'top':
							communicator.communicator_messages.scrollTop = 0;
						break;

						case 'bottom':
							communicator.communicator_messages.scrollTop = communicator.communicator_messages.scrollHeight;
						break;
					}
				},

				receiveMessages : function () {
					if ( communicator.ajax_receive_messages.readyState === 0 || communicator.ajax_receive_messages.readyState === 4 ) {
						var initial_receive_all_messages = communicator.receive_all_messages;

						var formData = new FormData();
						formData.append( 'function', 'communicator' );
						switch ( communicator.room_type ) {
							case 'folder':
								formData.append( 'folder_id', communicator.room_id );
							break;
							case 'text':
								formData.append( 'text_id', communicator.room_id );
							break;
						}
						if ( initial_receive_all_messages === true ) {
							formData.append( 'last_timestamp', '1970-01-01 00:00:01' );
						} else {
							formData.append( 'last_timestamp', communicator.last_timestamp );
						}

						communicator.ajax_receive_messages.open( 'POST', '../include/php/ajax_communicator.php', true );
						communicator.ajax_receive_messages.onreadystatechange = function () {
							if ( communicator.ajax_receive_messages.readyState === 4 ) {
								/* console.log( 'COMMUNICATOR RECEIVE STATUS ('+communicator.ajax_receive_messages.status+') > "'+communicator.ajax_receive_messages.responseText+'"' ); */
								if ( communicator.ajax_receive_messages.status === 200 ) {
									if ( communicator.ajax_receive_messages.responseText.length > 0 ) {
										if ( $( '#communicator' ).is( ":hidden" ) ) {
											communicator.playNotificationSound();
											communicator.unviewed_count++;
											document.getElementById( 'communicator_closed_title' ).innerHTML = '<?php echo $translator->getTranslation( 'lbl_communicator' ); ?> ('+communicator.unviewed_count+')';
										}
										if ( initial_receive_all_messages === true ) {
											communicator.communicator_messages.innerHTML = communicator.ajax_receive_messages.responseText;
											communicator.receive_all_messages = false;
											communicator.scroll( 'top' );
										} else {
											communicator.communicator_messages.innerHTML += communicator.ajax_receive_messages.responseText;
											communicator.scroll( 'bottom' );
										}
										communicator.updateLastTimestamp();
									} else if ( initial_receive_all_messages === true ) {
										communicator.communicator_messages.innerHTML = '';
										communicator.receive_all_messages = false;
									}
								} else if ( communicator.ajax_receive_messages.status !== 0 ) {
									console.log( 'COMMUNICATOR RECEIVE ERROR (#'+communicator.ajax_receive_messages.status+') > '+communicator.ajax_receive_messages.responseText );
								}
							}
						};
						communicator.ajax_receive_messages.send( formData );
					}
				},

				sendMessage : function () {
					if ( communicator.ajax_send_message.readyState === 0 || communicator.ajax_send_message.readyState === 4 ) {
						if ( communicator.communicator_input_message.value.length > 0 ) {
							communicator.communicator_input_message.disabled = true;

							var formData = new FormData();
							formData.append( 'function', 'communicator' );
							switch ( communicator.room_type ) {
								case 'folder':
									formData.append( 'folder_id', communicator.room_id );
								break;
								case 'text':
									formData.append( 'text_id', communicator.room_id );
								break;
							}
							formData.append( 'last_timestamp', communicator.last_timestamp );
							formData.append( 'communicator_input_message', communicator.communicator_input_message.value );

							communicator.ajax_send_message.open( 'POST', '../include/php/ajax_communicator.php', true );
							communicator.ajax_send_message.onreadystatechange = function () {
								if ( communicator.ajax_send_message.readyState === 4 ) {
									/* console.log( 'COMMUNICATOR SEND STATUS ('+communicator.ajax_send_message.status+') > "'+communicator.ajax_send_message.responseText+'"' ); */
									communicator.communicator_input_message.disabled = false;
									if ( communicator.ajax_send_message.status === 200 ) {
										communicator.communicator_input_message.value = '';
										if ( communicator.ajax_send_message.responseText.length > 0 ) {
											communicator.ajax_receive_messages.abort();
											communicator.communicator_messages.innerHTML += communicator.ajax_send_message.responseText;
											communicator.updateLastTimestamp();
											communicator.scroll( 'bottom' );
										}
									} else if ( communicator.ajax_send_message.status !== 0 ) {
										console.log( 'COMMUNICATOR SEND ERROR (#'+communicator.ajax_send_message.status+') > '+communicator.ajax_send_message.responseText );
									}
									communicator.communicator_input_message.focus();
								}
							};
							communicator.ajax_send_message.send( formData );
						}
					}
				},

				clearMessages : function () {
					communicator.communicator_messages.innerHTML = ' \
						<div class="selection bg_light" style="padding:4px;" onClick="this.innerHTML = \'<?php echo $translator->getTranslation( 'lbl_communicator_loading_messages' ); ?>\'; communicator.receive_all_messages = true;"> \
							<?php echo $translator->getTranslation( 'lbl_communicator_show_older_messages' ); ?> \
						</div> \
					';
				},

				sendAlert : function () {
					if ( communicator.ajax_send_alert.readyState === 0 || communicator.ajax_send_alert.readyState === 4 ) {
						var formData = new FormData();
						formData.append( 'function', 'communicator' );
						switch ( communicator.room_type ) {
							case 'folder':
								formData.append( 'folder_id', communicator.room_id );
							break;
							case 'text':
								formData.append( 'text_id', communicator.room_id );
							break;
						}

						communicator.ajax_send_alert.open( 'POST', '../include/php/ajax_communicator.php', true );
						communicator.ajax_send_alert.onreadystatechange = function () {
							if ( communicator.ajax_send_alert.readyState === 4 ) {
								/* console.log( 'COMMUNICATOR SEND ALERT STATUS ('+communicator.ajax_send_alert.status+') > "'+communicator.ajax_send_alert.responseText+'"' ); */
								if ( communicator.ajax_send_alert.status === 200 ) {
									communicator.playAlertSound();
									if ( communicator.ajax_send_alert.responseText.length > 0 ) {
										communicator.communicator_messages.innerHTML += communicator.ajax_send_alert.responseText;
										communicator.scroll( 'bottom' );
									}
									communicator.communicator_input_message.focus();
								} else if ( communicator.ajax_send_alert.status !== 0 ) {
									console.log( 'COMMUNICATOR SEND ALERT ERROR (#'+communicator.ajax_send_alert.status+') > '+communicator.ajax_send_alert.responseText );
								}
							}
						};
						communicator.ajax_send_alert.send( formData );
					}
				},

				changeRoom : function () {
					var room_name = document.getElementById( 'communicator_room_title' );
					if ( document.getElementById( 'communicator_room_change_checkbox' ).checked === true ) {
						if ( communicator.change_room_interval_id === 0 ) {
							communicator.change_room_interval_id = window.setInterval( function () {
								switch ( communicator.room_type ) {
									case 'folder':
										if ( communicator.room_id !== folder_tree.pastaSelecionada ) {
											communicator.room_id = folder_tree.pastaSelecionada;
											communicator.clearMessages();
											communicator.updateRoomName();
										}
									break;
									case 'text':
										/*
										¯\_(ツ)_/¯
										*/
									break;
								}
							}, 5000 );
						}
					} else if ( communicator.change_room_interval_id !== 0 ) {
						window.clearInterval( communicator.change_room_interval_id );
						communicator.change_room_interval_id = 0;
					}
				},

				updateRoomName : function () {
					var room_name = document.getElementById( 'communicator_room_title' );
					switch ( communicator.room_type ) {
						case 'folder':
							communicator.room_label.textContent = 'Pasta '+communicator.room_id;
						break;
						case 'text':
							communicator.room_label.textContent = 'Texto '+communicator.room_id;
						break;
					}
				},

				saveAsPDF : function () {
					switch ( communicator.room_type ) {
						case 'folder':
							document.getElementById( 'communicator_pdf_encoded_filename' ).value = encodeURIComponent( 'ETC_-_Conversa_Pasta_'+communicator.room_id );
						break;
						case 'text':
							document.getElementById( 'communicator_pdf_encoded_filename' ).value = encodeURIComponent( 'ETC_-_Conversa_Texto_'+communicator.room_id );
						break;
					}
					document.getElementById( 'communicator_pdf_encoded_html' ).value = encodeURIComponent( communicator.communicator_messages.innerHTML );
					document.getElementById( 'communicator_save_as_pdf_form' ).submit();
				},

				expand : function () {
					$( '#communicator_closed' ).hide();
					$( '#communicator' ).show();
					communicator.unviewed_count = 0;
					document.getElementById( 'communicator_closed_title' ).innerHTML = '<?php echo $translator->getTranslation( 'lbl_communicator' ); ?>';
					communicator.scroll( 'bottom' );
					communicator.communicator_input_message.focus();
				},

				retract : function () {
					$( '#communicator' ).hide();
					$( '#communicator_closed' ).show();
					communicator.unviewed_count = 0;
					document.getElementById( 'communicator_closed_title' ).innerHTML = '<?php echo $translator->getTranslation( 'lbl_communicator' ); ?>';
				},

				playNotificationSound : function () {
					communicator.notification_sound.play();
				},

				playAlertSound : function () {
					communicator.alert_sound.play();
				}
			}
		</script>

		<div id="communicator_closed" class="bg_white bx_border" style="display:none; position:absolute; left:4px; top:360px; width:192px; cursor:pointer;">
			<div id="communicator_closed_title" class="bx_title">
				<?php echo $translator->getTranslation( 'lbl_communicator' ); ?>
			</div>
			<div id="status" class="scrollable" style="max-height:200px; white-space:nowrap; overflow-x:hidden; overflow-y:auto;"></div>
		</div>

		<div id="communicator" class="bx_border" style="display:none; position:fixed; left:200px; top:180px;">
			<div class="bx_title" style="cursor:move;">
				<?php echo $translator->getTranslation( 'lbl_communicator' ); ?>
				<img id="communicator_close_button" src="<?php echo ETC_BASE_URL; ?>images/ico_no.png" class="fill_dark" style="float:right; cursor:pointer;"/>
			</div>
			<div id="communicator_room_title" class="bg_light" style="padding:8px; text-align:center;">
				<img id="communicator_send_alert_button" src="<?php echo ETC_BASE_URL; ?>images/ico_bell.png" title="Chamar atenção (enviar notificação)" class="selection" style="float:left; cursor:pointer;"/>
				<label name="label"></label>
				<?php
				if ( $text_id === 0 ) { /* communicator.room_type === 'folder' */
					?>
					|
					<label for="communicator_room_change_checkbox">Ativar troca de pasta</label>
					<input id="communicator_room_change_checkbox" type="checkbox" onChange="communicator.changeRoom();">
					<?php
				}
				?>
				<img id="communicator_save_as_pdf_button" src="<?php echo ETC_BASE_URL; ?>images/editor/toolbar/btn_salvar_como_pdf.png" title="Salvar conversa em PDF" class="selection" style="float:right; margin:-8px; cursor:pointer;"/>
				<form id="communicator_save_as_pdf_form" name="communicator_save_as_pdf_form" method="post" target="_blank" action="<?php echo ETC_BASE_URL; ?>include/php/generate_pdf.php" class="not_displayed">
					<input type="hidden" id="communicator_pdf_encoded_filename" name="pdf_encoded_filename" />
					<input type="hidden" id="communicator_pdf_encoded_html" name="pdf_encoded_html" />
				</form>
			</div>
			<div id="communicator_content" class="bg_white" style="min-width:360px; min-height:200px; max-width:800px; max-height:600px; overflow:auto; resize:both;">
				<div style="position:absolute; left:8px; right:8px; top:62px; bottom:8px;"><!-- "border padding" must be done here to avoid overlapping the window resizer at bottom right -->
					<!-- Left Column -->
					<div style="position:absolute; left:0px; /*right:0px;*/ top:0px; bottom:0px; width:160px;">
						<div id="communicator_users" class="scrollable" style="position:absolute; left:0px; right:0px; top:0px; bottom:0px; overflow:auto;">
						</div>
					</div>

					<!-- Right Column -->
					<div style="position:absolute; left:160px; right:0px; top:0px; bottom:0px;">
						<div id="communicator_messages" class="scrollable" style="position:absolute; left:0px; right:0px; top:0px; bottom:49px; overflow:auto;">
						</div>

						<div class="tx_small" style="position:absolute; left:0px; right:0px; /*top:0px;*/ bottom:0px; height:49px; overflow:auto;">
							<textarea id="communicator_input_message" placeholder="Digite aqui sua mensagem" maxlength="4096" style="width:100%; height:34px;"></textarea>
							<?php echo $translator->getTranslation( 'lbl_press_enter_to_send' ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function printHTMLNotes ( $text_id ) {
		global $translator;
		global $pdo_handler;
		?>
		<script>
			"use strict";

			$( document ).ready( function () {
				$( window ).on( 'load', function () {
					textNotes.initialize();
				} );
			} );

			var textNotes = {
				initialize : function () {
					if ( typeof( textNotes.text_notes ) === 'undefined' ) {
						textNotes.text_notes = document.getElementById( 'text_notes' );
					}
					if ( typeof( textNotes.text_notes_closed ) === 'undefined' ) {
						textNotes.text_notes_closed = document.getElementById( 'text_notes_closed' );
					}
					if ( typeof( textNotes.text_notes_notes ) === 'undefined' ) {
						textNotes.text_notes_notes = document.getElementById( 'text_notes_notes' );
					}
					if ( typeof( textNotes.text_notes_input_note ) === 'undefined' ) {
						textNotes.text_notes_input_note = document.getElementById( 'text_notes_input_note' );
					}
					if ( typeof( textNotes.last_timestamp ) === 'undefined' ) {
						textNotes.last_timestamp = '1970-01-01 00:00:01';
						/*textNotes.last_timestamp = '<?php echo $pdo_handler->now(); ?>';*/
					}
					if ( typeof( textNotes.update_interval_id ) === 'undefined' ) {
						textNotes.update_interval_id = 0;
					}
					if ( typeof( textNotes.receive_all_notes ) === 'undefined' ) {
						textNotes.receive_all_notes = true;
					}
					if ( typeof( textNotes.ajax_receive_notes ) === 'undefined' ) {
						textNotes.ajax_receive_notes = new XMLHttpRequest();
					}
					if ( typeof( textNotes.ajax_send_note ) === 'undefined' ) {
						textNotes.ajax_send_note = new XMLHttpRequest();
					}
					if ( typeof( textNotes.note_count ) === 'undefined' ) {
						textNotes.note_count = 0;
					}
					if ( typeof( textNotes.notification_sound ) === 'undefined' ) {
						textNotes.notification_sound = new Audio();
						if ( textNotes.notification_sound.canPlayType( 'audio/wav' ) !== '' ) {
							textNotes.notification_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/notification.wav';
						} else if ( textNotes.notification_sound.canPlayType( 'audio/ogg' ) !== '' ) {
							textNotes.notification_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/notification.ogg';
						} else if ( textNotes.notification_sound.canPlayType( 'audio/mp3' ) !== '' ) {
							textNotes.notification_sound.src = '<?php echo ETC_BASE_URL; ?>sounds/notification.mp3';
						}
					}

					addEvent( textNotes.text_notes_closed, 'click', textNotes.expand );
					addEvent( document.getElementById( 'text_notes_close_button' ), 'click', textNotes.retract );

					/* Note Send Event */
					$( '#text_notes_input_note' ).on( 'keydown', function ( event ) {
						var keyCode = event.keyCode ? event.keyCode : event.charCode ? event.charCode : event.which;
						if ( !event.shiftKey && keyCode === 13 ) {
							event.preventDefault();
							textNotes.sendNote();
						}
					} );

					/* Note Fetch Interval */
					textNotes.update_interval_id = window.setInterval( function () {
						textNotes.receiveNotes();
					}, 2000 );

					/* Initial Display */
					textNotes.retract();
				},

				updateLastTimestamp : function () {
					/* HTML5: textNotes.text_notes_notes.lastElementChild.dataset.timestamp */
					var last_note = textNotes.text_notes_notes.lastElementChild;
					if ( last_note !== null ) {
						textNotes.last_timestamp = last_note.getAttribute( 'data-timestamp' );
					}
				},

				updateNotesCount : function () {
					textNotes.note_count = textNotes.text_notes_notes.childElementCount;
					document.getElementById( 'text_notes_closed_title' ).innerHTML = '<?php echo $translator->getTranslation( 'lbl_text_notes' ); ?> ('+textNotes.note_count+')';
					document.getElementById( 'text_notes_title' ).innerHTML = '<?php echo $translator->getTranslation( 'lbl_text_notes' ); ?> ('+textNotes.note_count+')';
				},

				scroll : function ( where ) {
					switch ( where ) {
						case 'top':
							textNotes.text_notes_notes.scrollTop = 0;
						break;

						case 'bottom':
							textNotes.text_notes_notes.scrollTop = textNotes.text_notes_notes.scrollHeight;
						break;
					}
				},

				receiveNotes : function () {
					if ( textNotes.ajax_receive_notes.readyState === 0 || textNotes.ajax_receive_notes.readyState === 4 ) {
						var initial_receive_all_notes = textNotes.receive_all_notes;

						var formData = new FormData();
						formData.append( 'function', 'notes' );
						formData.append( 'text_id', <?php echo $text_id; ?> );
						if ( initial_receive_all_notes === true ) {
							formData.append( 'last_timestamp', '1970-01-01 00:00:01' );
						} else {
							formData.append( 'last_timestamp', textNotes.last_timestamp );
						}

						textNotes.ajax_receive_notes.open( 'POST', '../include/php/ajax_communicator.php', true );
						textNotes.ajax_receive_notes.onreadystatechange = function () {
							if ( textNotes.ajax_receive_notes.readyState === 4 ) {
								/* console.log( 'TEXT_NOTES RECEIVE STATUS ('+textNotes.ajax_receive_notes.status+') > "'+textNotes.ajax_receive_notes.responseText+'"' ); */
								if ( textNotes.ajax_receive_notes.status === 200 ) {
									if ( textNotes.ajax_receive_notes.responseText.length > 0 ) {
										if ( initial_receive_all_notes === true ) {
											textNotes.text_notes_notes.innerHTML = textNotes.ajax_receive_notes.responseText;
											textNotes.receive_all_notes = false;
										} else {
											if ( $( '#text_notes' ).is( ":hidden" ) ) {
												textNotes.playNotificationSound();
											}
											textNotes.text_notes_notes.innerHTML += textNotes.ajax_receive_notes.responseText;
										}
										textNotes.updateLastTimestamp();
										textNotes.updateNotesCount();
										textNotes.scroll( 'bottom' );
									}
								} else if ( textNotes.ajax_receive_notes.status !== 0 ) {
									console.log( 'TEXT_NOTES RECEIVE ERROR (#'+textNotes.ajax_receive_notes.status+') > '+textNotes.ajax_receive_notes.responseText );
								}
							}
						};
						textNotes.ajax_receive_notes.send( formData );
					}
				},

				sendNote : function () {
					if ( textNotes.ajax_send_note.readyState === 0 || textNotes.ajax_send_note.readyState === 4 ) {
						if ( textNotes.text_notes_input_note.value.length > 0 ) {
							textNotes.text_notes_input_note.disabled = true;

							var formData = new FormData();
							formData.append( 'function', 'notes' );
							formData.append( 'text_id', <?php echo $text_id; ?> );
							formData.append( 'last_timestamp', textNotes.last_timestamp );
							formData.append( 'text_notes_input_note', textNotes.text_notes_input_note.value );

							textNotes.ajax_send_note.open( 'POST', '../include/php/ajax_communicator.php', true );
							textNotes.ajax_send_note.onreadystatechange = function () {
								if ( textNotes.ajax_send_note.readyState === 4 ) {
									/* console.log( 'TEXT_NOTES SEND STATUS ('+textNotes.ajax_send_note.status+') > "'+textNotes.ajax_send_note.responseText+'"' ); */
									textNotes.text_notes_input_note.disabled = false;
									if ( textNotes.ajax_send_note.status === 200 ) {
										textNotes.text_notes_input_note.value = '';
										if ( textNotes.ajax_send_note.responseText.length > 0 ) {
											textNotes.ajax_receive_notes.abort();
											textNotes.text_notes_notes.innerHTML += textNotes.ajax_send_note.responseText;
											textNotes.updateLastTimestamp();
											textNotes.updateNotesCount();
											textNotes.scroll( 'bottom' );
										}
									} else if ( textNotes.ajax_send_note.status !== 0 ) {
										console.log( 'TEXT_NOTES SEND ERROR (#'+textNotes.ajax_send_note.status+') > '+textNotes.ajax_send_note.responseText );
									}
									textNotes.text_notes_input_note.focus();
								}
							};
							textNotes.ajax_send_note.send( formData );
						}
					}
				},

				expand : function () {
					$( '#text_notes_closed' ).hide();
					$( '#text_notes' ).show();
					textNotes.scroll( 'bottom' );
					textNotes.text_notes_input_note.focus();
				},

				retract : function () {
					$( '#text_notes' ).hide();
					$( '#text_notes_closed' ).show();
				},

				playNotificationSound : function () {
					textNotes.notification_sound.play();
				}
			}
		</script>

		<div id="text_notes_closed" class="bg_white bx_border" style="display:none; position:fixed; right:4px; bottom:4px; min-width:120px; cursor:pointer;">
			<div id="text_notes_closed_title" class="bx_title">
				<?php echo $translator->getTranslation( 'lbl_text_notes' ); ?>
			</div>
		</div>

		<div id="text_notes" class="bx_border" style="display:none; position:fixed; right:4px; bottom:4px; min-width:140px;">
			<div class="bx_title">
				<div id="text_notes_title" style="display:inline-block; font-weight:bold;">
					<?php echo $translator->getTranslation( 'lbl_text_notes' ); ?>
				</div>
				<img id="text_notes_close_button" src="<?php echo ETC_BASE_URL; ?>images/ico_no.png" class="fill_dark" style="float:right; cursor:pointer;" />
			</div>
			<div class="bg_light tx_small" style="padding:8px; text-align:center;">
				<textarea id="text_notes_input_note" placeholder="Digite aqui sua nota" maxlength="4096" style="width:100%; height:34px;"></textarea>
				<?php echo $translator->getTranslation( 'lbl_press_enter_to_send' ); ?>
			</div>
			<div id="text_notes_notes" class="bg_white scrollable" style="max-width:400px; max-height:300px; padding:4px; overflow:auto; overflow-y:scroll;">
				<?php echo $translator->getTranslation( 'lbl_text_notes_empty' ); ?>
			</div>
		</div>
		<?php
	}

}

?>
