<?php

function random_string ( $stringLength ) {
	$charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'; // 0-9 A-Z a-z == 10 + 26 + 26 == 62 characters available
	switch ( TRUE ) {
		case !is_int( $stringLength ): // ERROR: $stringLength not an integer
		case ( $stringLength < 0 ): // ERROR: $stringLength < 0
		case ( $stringLength > 1024 ): // ERROR: $stringLength > 1024
		case ( !ctype_alnum( $charset ) ): // ERROR: $charset contains non-alphanumeric characters
			return FALSE;
		default:
			$charset = count_chars( $charset, 3 ); // count_chars mode 3 - a string containing all unique characters is returned
			$charsetLength = mb_strlen( $charset, 'UTF-8' );
	}
	$string = '';
	for ( $i = 0; $i < $stringLength; ++$i ) {
		$string .= $charset[random_int( 0, ( $charsetLength - 1 ) )];
	}
	return $string;
}

function newUniqueName ( $prefix = '', $suffix = '' ) {
	$prefix = trim( $prefix );
	$suffix = trim( $suffix );
	// uniqid(): 13 characters long based on the current time in microseconds
	return $prefix.uniqid().random_string( 11 ).$suffix; // 13 + 11 == 24 characters long (at least, because of prefix and suffix)
}

function hashPassword ( $input_password ) {
	if ( function_exists( 'password_hash' ) ) {
		return password_hash( $input_password, PASSWORD_DEFAULT );
	} else { // FALLBACK: crypt()
		// Blowfish (PHP >= 5.3.7)
		$salt = '$2y$07$'.random_string( 22 );
		$input_password_hash = crypt( $input_password, $salt );
		if ( !( $input_password_hash === crypt( $input_password, $input_password_hash ) ) ) {
			// FALLBACK: Blowfish (PHP 4)
			$salt = '$2a$07$'.random_string( 22 );
			$input_password_hash = crypt( $input_password, $salt );
			if ( !( $input_password_hash === crypt( $input_password, $input_password_hash ) ) ) {
				// FALLBACK: md5 (PHP 4)
				$salt = '$1$'.random_string( 12 );
				$input_password_hash = crypt( $input_password, $salt );
				if ( !( $input_password_hash === crypt( $input_password, $input_password_hash ) ) ) {
					// FALLBACK: md5 unsalted
					$input_password_hash = md5( $input_password );
				}
			}
		}
		return $input_password_hash;
	}
}

function verifyPassword ( $input_password, $correct_hash ) {
	if ( function_exists( 'password_verify' ) ) {
		if ( password_verify( $input_password, $correct_hash ) === TRUE ) {
			return 'password_valid';
		}
	} else {
		if ( $correct_hash === crypt( $input_password, $correct_hash ) ) { // FALLBACK: crypt()
			return 'password_valid';
		}
	}
	if ( strcmp( md5( $input_password ), $correct_hash ) === 0 ) { // FALLBACK: md5()
		return 'password_valid_old';
	} else {
		return 'password_invalid';
	}
}

function sanitize ( $string, $mode = 'default' ) {
	$char_alphanumeric = 'a-z0-9';
	$char_extra = '\(\)\[\]\'\-\_\.\,\&';
	$char_danger = '\=\+\#\%';
	$cleaned = $string;
	switch ( $mode ) {
		case 'relaxed':
			$cleaned = preg_replace( '/[^'.$char_alphanumeric.$char_extra.$char_danger.']+/i', ' ', $cleaned );
		break;
		case 'default':
			$cleaned = preg_replace( '/[^'.$char_alphanumeric.$char_extra.']+/i', ' ', $cleaned );
		break;
		case 'alphanumeric':
			$cleaned = preg_replace( '/[^'.$char_alphanumeric.']+/i', ' ', $cleaned );
		break;
		default:
			// TODO TO-DO TO DO - LOG ERROR: INVALID $mode
		break;
	}
	$cleaned = preg_replace( '/\s+/', '_', $cleaned ); // spaces to underscore
	return $cleaned;
}

function getChangedValues ( $current_values, $new_values ) {
	// $new_values not in $current_values are NOT returned
	if ( !is_array( $current_values ) || !is_array( $new_values ) ) {
		return FALSE;
	}
	$new_values = array_intersect_key( $new_values, $current_values );
	$changed_values = array();
	foreach ( $new_values as $key => $new ) {
		$new = trim( $new );
		if ( empty( $new ) ) {
			if ( !empty( $current_values[$key] ) ) {
				$changed_values[$key] = NULL; // parameter deleted/cleared
			}
		} else if ( empty( $current_values[$key] ) || ( $new !== $current_values[$key] ) ) {
			$changed_values[$key] = $new;     // parameter created/defined or changed
		}
	}
	return $changed_values;
}

function formatTimestamp ( $timestamp = '' ) {
	// timestamp format : YYYY-MM-DD HH:MM:SS (= date( 'Y-m-d H:i:s' ))
	// DD/MM/YYYY às HH:MM:SS	: 'd/m/Y \à\s H:i:s'
	// DD/MM/YYYY - HH:MM		: 'd/m/Y - H:i'
	return empty( $timestamp ) ? date( 'd/m/Y - H:i' ) : date( 'd/m/Y - H:i', strtotime( $timestamp ) );
}

function is_assoc ( $var ) {
	return is_array( $var ) && ( bool )count( array_filter( array_keys( $var ), 'is_string' ) );
}

function getExtension ( $filename ) {
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	if ( $extension === '' ) {
		return '';
	} else {
		return '.'.$extension;
	}
}

function getAllowedExtension ( $filename, $mode = 'whitelist' ) {
	$file_extension = getExtension( $filename );

	$extensions_document	= array( '.txt', '.doc', '.docx', '.ppt', '.pptx', '.xls', '.xlsx', '.pdf' );
	$extensions_images		= array( '.bmp', '.gif', '.jpg', '.jpeg', '.png' );
	$extensions_audio		= array( '.mid', '.midi', '.avi', '.mp3', '.wma', '.ogg', '.wav' );
	$extensions_video		= array( '.avi', '.flv', '.webm', '.wmv', '.mp4', '.divx', '.xvid' );
	$extensions_whitelist	= array_merge( $extensions_document, $extensions_images, $extensions_audio, $extensions_video );

	$extensions_script		= array( '.php', '.js', '.bat', '.bin', '.cmd', '.com', '.reg', '.vb', '.vbe', '.vbs', '.wsc', '.wsf', '.wsh', '.shb', '.pif', '.lnk' );
	$extensions_binary		= array( '.dll', '.ocx' );
	$extensions_executable	= array( '.exe', '.msi', '.scr', '.msp', '.pps', '.ppsx' );
	$extensions_blacklist	= array_merge( $extensions_script, $extensions_binary, $extensions_executable );

	switch ( $mode ) {
		case 'whitelist':	return ( in_array( $file_extension, $extensions_whitelist ) === TRUE ) ? $file_extension : '' ;
		break;

		case 'blacklist':	return ( in_array( $file_extension, $extensions_blacklist ) === TRUE ) ? '' : $file_extension;
		break;

		default:			return '';
		break;
	}
}

function getImageExtension ( $filename ) {
	$file_extension = getExtension( $filename );

	$image_extensions = array( '.bmp', '.gif', '.png', '.jpg', '.jpeg' );

	if ( in_array( $file_extension, $image_extensions ) === TRUE ) { // extensão corresponde a uma imagem válida!
		return $file_extension;
	} else {
		return '';
	}
}

function validFullName ( $full_name ) {
	// não é uma string
	if ( !is_string( $full_name ) )
		return FALSE;

	$full_name = trim( $full_name );

	// nome está vazio
	if ( empty( $full_name ) )
		return FALSE;

	// nome contém apenas um nome (não está completo)
	if ( count( explode( ' ', $full_name, 2 ) ) < 2 )
		return FALSE;

	return TRUE;
}

function validEmail ( $email ) {
	// não é uma string
	if ( !is_string( $email ) ) {
		return FALSE;
	}

	$email = trim( $email );

	// email está vazio
	if ( empty( $email ) ) {
		return FALSE;
	}

	// email contém espaços
	if ( stripos( $email, ' ' ) !== FALSE ) {
		return FALSE;
	}

	// email contém quantidade de '@' diferente de 1
	$email_exploded = explode( '@', $email, 3 );
	if ( count( $email_exploded ) != 2 ) {
		return FALSE;
	}

	// email não contém '.' após o '@'
	$email_exploded_right = explode( '.', $email_exploded[1] );
	if ( count( $email_exploded_right ) < 2 ) {
		return FALSE;
	}

	// email inconsistente após o '@' (contém '.' que não possui caracteres antes e depois)
	if ( in_array( '', $email_exploded_right ) === TRUE ) {
		return FALSE;
	}

	// email inconsistente antes do '@' (contém '.' que não possui caracteres antes e depois)
	$email_exploded_left = explode( '.', $email_exploded[0] );
	if ( in_array( '', $email_exploded_left ) === TRUE ) {
		return FALSE;
	}

	return TRUE;
}

function countEmailAttachments ( $emailAttachmentString ) {
	$emailAttachmentString = trim( $emailAttachmentString );
	if ( empty( $emailAttachmentString ) === TRUE ) {
		return 0;
	} else {
		return count( explode( ';', $emailAttachmentString ) );
	}
}

// dados esperados em UTF-8 (codificação nativa do ETC)
function multi_attach_email ( $to, $subject, $message, $senderemail, $anexos = array() ) {
	if ( is_array( $to ) ) {
		require_once( dirname( __FILE__ ).'/PHPMailer/PHPMailerAutoload.php' );
		$mail = new PHPMailer();
		$mail->setFrom( $senderemail, 'ETC' );
		$mail->addReplyTo( 'email@project.com', 'Project - Contact' );
		foreach ( $to as $destinatario ) {
			$mail->addAddress( $destinatario['address'], $destinatario['name'] );
		}
		$mail->WordWrap = 78;
		foreach ( $anexos as $anexo ) {
			$mail->addAttachment( $anexo['tmp_name'], $anexo['name'] );
		}
		$mail->isHTML( TRUE );
		$mail->Subject = $subject;
		$mail->Body = $message;
		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'quoted-printable';
		return $mail->send();
	} else {
		$random_hash	= md5( uniqid( '', TRUE ) );
		$mime_boundary	= "==Multipart_Boundary_x{$random_hash}x";

		$subject	= utf8_decode( $subject );
		$headers	= "From: ETC <".$senderemail.">\n".
					  "MIME-Version: 1.0\n".
					  "Content-Type: multipart/mixed;\n".
					  " boundary=\"{$mime_boundary}\"";
		$message	= "--{$mime_boundary}\n".
					  "Content-Type: text/html; charset=\"utf-8\"\n".
					  "Content-Transfer-Encoding: 7bit\n\n".
					  $message."\n\n";

		// $anexos é um array de arquivos do tipo de $_FILES[]
		for ( $i = 0; $i < count( $anexos ); $i++ ) {
			if ( is_file( $anexos[$i]['tmp_name'] ) ) {
				$fp			 = @fopen( $anexos[$i]['tmp_name'], 'rb' );
				$data		 = @fread( $fp, filesize($anexos[$i]['tmp_name'] ) );
				@fclose( $fp );
				$data		 = chunk_split( base64_encode( $data ) );
				$message	.= "--{$mime_boundary}\n".
								"Content-Type: application/octet-stream; name=\"".basename( $anexos[$i]['name'] )."\"\n".
								"Content-Description: ".basename( $anexos[$i]['name'] )."\n".
								"Content-Disposition: attachment;\n".
								"filename=\"".basename( $anexos[$i]['name'] )."\"; size=".filesize( $anexos[$i]['tmp_name'] ).";\n".
								"Content-Transfer-Encoding: base64\n\n".$data."\n\n";
			}
		}
		$message		.= "--{$mime_boundary}--";

		return mail( $to, $subject, $message, $headers, '-f'.$senderemail );
	}
}

// dados esperados em UTF-8 (codificação nativa do ETC)
function OLD_multi_attach_email ( $to, $subject, $message, $senderemail, $anexos = array() ) {
	$random_hash	= md5( uniqid( '', TRUE ) );
	$mime_boundary	= "==Multipart_Boundary_x{$random_hash}x";

	$subject	= utf8_decode( $subject );
	$headers	= "From: ETC <".$senderemail.">\n".
				  "MIME-Version: 1.0\n".
				  "Content-Type: multipart/mixed;\n".
				  " boundary=\"{$mime_boundary}\"";
	$message	= "--{$mime_boundary}\n".
				  "Content-Type: text/html; charset=\"utf-8\"\n".
				  "Content-Transfer-Encoding: 7bit\n\n".
				  $message."\n\n";

	// $anexos é um array de arquivos do tipo de $_FILES[]
	for ( $i = 0; $i < count( $anexos ); $i++ ) {
		if ( is_file( $anexos[$i]['tmp_name'] ) ) {
			$fp			 = @fopen( $anexos[$i]['tmp_name'], 'rb' );
			$data		 = @fread( $fp, filesize($anexos[$i]['tmp_name'] ) );
			@fclose( $fp );
			$data		 = chunk_split( base64_encode( $data ) );
			$message	.= "--{$mime_boundary}\n".
							"Content-Type: application/octet-stream; name=\"".basename( $anexos[$i]['name'] )."\"\n".
							"Content-Description: ".basename( $anexos[$i]['name'] )."\n".
							"Content-Disposition: attachment;\n".
							"filename=\"".basename( $anexos[$i]['name'] )."\"; size=".filesize( $anexos[$i]['tmp_name'] ).";\n".
							"Content-Transfer-Encoding: base64\n\n".$data."\n\n";
		}
	}
	$message		.= "--{$mime_boundary}--";

	return mail( $to, $subject, $message, $headers, '-f'.$senderemail );
}

function alert ( $message, $parameters = '' ) {
	?>
	<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<link rel="shortcut icon" href="../<?php echo ETC_BASE_URL; ?>images/favicon.ico" />
			<script type="text/javascript">
				for ( var etc_root = window; etc_root.parent != etc_root && etc_root.location.href != '<?php echo ETC_BASE_URL; ?>'; etc_root = etc_root.parent );
				<?php
				echo 'alert( "'.addslashes( $message ).'" );';
				switch ( strtolower( trim( $parameters ) ) ) {
					case '':
						return;
					break;

					case 'close':
						echo 'etc_root.close();';
					break;

					case 'back':
					case 'return':
						echo 'etc_root.history.go( -1 );'; //history.back()
					break;

					case 'reload':
					case 'refresh':
						echo 'etc_root.location.reload( true );';
					break;

					case 'home':
					case 'root':
					case 'start':
					case 'initial':
						echo 'etc_root.location.href = "'.ETC_LOGGED_PAGE.'";';
					break;

					default:
						echo 'etc_root.location.href = "'.$parameters.'";';
					break;
				}
				?>
			</script>
		</head>
	</html>
	<?php
	exit;
}

function setHeaderLocation ( $destinationURL, $redirectInMainWindow = FALSE, $statusCode = 303 ) {
	$destinationURL = HTMLPage::prettifyURL( $destinationURL );
	if ( ( headers_sent() === FALSE ) && ( $redirectInMainWindow === FALSE ) ) {
		header( 'Location: '.$destinationURL, TRUE, $statusCode );
	} else { // headers already sent OR redirection in main window/iframe, perform JavaScript redirection instead
		?>
		<script type="text/javascript">
			for ( var etc_root = window; etc_root.parent != etc_root && etc_root.location.href != '<?php echo ETC_BASE_URL; ?>'; etc_root = etc_root.parent );
			etc_root.location.href = '<?php echo $destinationURL; ?>';
		</script>
		<?php
	}
	exit;
}

function trim_url ( $url, $mode = 'spaces' ) {
	switch ( strtolower( $mode ) ) {
		case 'spaces and slashes':
			$match = '( |%20|\\|/)';
		break;

		case 'spaces':
		default:
			$match = '( |%20)';
		break;
	}
	return preg_replace( '#^('.$match.')+|('.$match.')+$#is', '', $url );
}

function DEBUG_LOG ( $message = 'DEBUG_LOG' ) {
	global $session;
	$ob_quantity = count( ob_get_status( TRUE ) );
	if ( $message === 'DEBUG_LOG' ) {
		$session->registerLog( 'debug', __FUNCTION__.'()', 1 );
	} else {
		if ( ob_start() === TRUE ) { // try to use var_dump() with output buffering
			if ( ( $ob_quantity + 1 ) !== count( ob_get_status( TRUE ) ) ) {
				$session->registerLog( 'error', __FUNCTION__.'(): inconsistent ob_quantity; expected "'.( $ob_quantity + 1 ).'", had "'.count( ob_get_status( TRUE ) ).'"', 1 );
				ob_end_flush();
			} else {
				var_dump( $message );
				$session->registerLog( 'debug', __FUNCTION__.'(): '.ob_get_clean(), 1 ); // ob_get_clean() = ob_get_contents() + ob_end_clean()
				if ( $ob_quantity !== count( ob_get_status( TRUE ) ) ) {
					$session->registerLog( 'error', __FUNCTION__.'(): inconsistent ob_quantity; expected "'.$ob_quantity.'", had "'.count( ob_get_status( TRUE ) ).'"', 1 );
					ob_end_flush();
				}
			}
		} else { // FALLBACK: use print_r()
			$session->registerLog( 'debug', __FUNCTION__.'(): '.print_r( $message, TRUE ), 1 );
		}
	}
}

$encode_order_string = 'UTF-8,ISO-8859-1,UTF-7,ASCII,EUC-JP,SJIS,eucJP-win,SJIS-win,JIS,ISO-2022-JP';
function file_get_contents_utf8 ( $filename ) {
	//global $session;
	global $encode_order_string;
	$content = @file_get_contents( $filename );
	if ( $content === FALSE ) {
		//$session->registerLog( 'debug', __FUNCTION__.'(): error accessing "'.$filename.'"', 1 );
		return '';
	} else {
		$contentUTF8 = mb_convert_encoding( $content, 'HTML-ENTITIES', mb_detect_encoding( $content, $encode_order_string, TRUE ) );
		//$contentUTF8 = html_entity_decode( $contentUTF8, ENT_NOQUOTES, 'UTF-8' );
		return $contentUTF8;
	}
}
?>
