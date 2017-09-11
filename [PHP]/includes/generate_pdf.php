<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );
Create_HTMLPurifier();

// disables all error reporting
@error_reporting( 0 );
@ini_set( 'display_errors ', '0' );
@ini_set( 'display_startup_errors', '0' );

// disables server compression
@apache_setenv( 'no-gzip', 1 );
@ini_set( 'zlib.output_compression', 'Off' );

$html_header = '<html><head>'.
					'<meta charset="UTF-8">'.
					'<title></title>'.
					'<link type="text/css" rel="stylesheet" href="'.ETC_BASE_URL.'/include/css/etc_style.css" />'.
					'<link type="text/css" rel="stylesheet" href="'.ETC_BASE_URL.'/include/css/jquery-ui.css" />'.
					'<script type="text/javascript" charset="UTF-8" src="'.ETC_BASE_URL.'/include/js/jquery.js"></script>'.
					'<script type="text/javascript" charset="UTF-8" src="'.ETC_BASE_URL.'/include/js/jquery-ui.js"></script>'.
					'<script type="text/javascript" charset="UTF-8" src="'.ETC_BASE_URL.'/include/js/jquery.mb.browser.js"></script>'.
					'<script type="text/javascript" charset="UTF-8" src="'.ETC_BASE_URL.'/include/js/jquery.webkitresize.js"></script>'.
					'<script type="text/javascript" charset="UTF-8" src="'.ETC_BASE_URL.'/include/js/jquery.wysiwyg-resize.js"></script>'.
					'<script type="text/javascript" charset="UTF-8" src="'.ETC_BASE_URL.'/include/js/general_purpose.js"></script>'.
				'</head><body>';
$html_footer = '</body></html>';

$html = $html_header.$HTMLPurifier->purify( rawurldecode( $_POST['pdf_encoded_html'] ) ).$html_footer;

$encoded_unhandled_characters = array( '%2F', '%5C' ); // [%2F : /] [%5C : \]
$pdf_filename = ( empty( $_POST['pdf_encoded_filename'] ) ? 'ETC_-_Save_as_PDF' : rawurldecode( str_replace( $encoded_unhandled_characters, '-', $_POST['pdf_encoded_filename'] ) ) ).'.pdf';
$temp_name = newUniqueName( 'html2pdf_' );

$temp_html_file = sys_get_temp_dir().'/'.$temp_name.'.html';
$temp_pdf_file = sys_get_temp_dir().'/'.$temp_name.'.pdf';

file_put_contents( $temp_html_file, $html );
chmod( $temp_html_file, 0666 );

exec( 'phantomjs generate_pdf.js '.$temp_html_file.' '.$temp_pdf_file.' "A4"' );

header( 'Accept-Ranges: bytes' );
header( 'Cache-Control: no-cache, no-store, must-revalidate' );
header( 'Content-Description: File Transfer' );
//header( 'Content-Disposition: attachment; size='.filesize( $temp_pdf_file ).'; filename="'.$pdf_filename.'"' ); // ESSE É QUASE PERFEITO, MAS CARACTERES COMO ' E " ESTRAGAM TUDO
header( 'Content-Disposition: attachment; size='.filesize( $temp_pdf_file ).'; filename*=UTF-8\'\''.rawurlencode( $pdf_filename ) ); // ESSE PARECE SER PERFEITO, SÓ PECA NO SUPORTE DOS NAVEGADORES
header( 'Content-Length: '.filesize( $temp_pdf_file ) );
header( 'Content-Transfer-Encoding: binary' );
//header( 'Content-Type: application/octet-stream' );
header( 'Content-Type: application/pdf' );
header( 'Expires: 0' );
header( 'Pragma: no-cache' );

@readfile( $temp_pdf_file );

@unlink( $temp_html_file );
@unlink( $temp_pdf_file );
?>
