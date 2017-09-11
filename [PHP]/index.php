<?php
// fallback to older system version, case it exists and the current one is unavailable
if ( !is_file( dirname( __FILE__ ).'/system.inc.php' ) ) {
	if ( is_dir( dirname( __FILE__ ).'/_previous' ) ) {
		header( 'Location: ./_previous', TRUE, 303 );
	} else {
		?><!DOCTYPE html>
		<html>
			<head>
				<meta charset="UTF-8" />
				<title>Under maintenance!</title>
			</head>
			<body>
				Under maintenance, please return later.
			</body>
		</html>
		<?php
	}
	exit;
}

require_once( dirname( __FILE__ ).DIRECTORY_SEPARATOR.'system.inc.php' );

$page = new HTMLPage( ETC_BASE_URL, $translator, 'Home', array( 'HEADER', 'CONTACT', 'FOOTER' ) );
$page->openHeader();
?>

<script type="text/javascript">
$( window ).on( 'load', function () {
	/* document.getElementsByTagName( 'body' )[0].style.opacity = '1'; */
	floatElementOnScroll( 'header' );
} );
</script>

<?php
$page->closeHeader();
?>

<section id="forum">
	<article>
		<h1 class="selection">Nope</h1>
	</article>
</section>

<section id="hubs">
	<article>
		<h1 class="selection">Nope</h1>
	</article>
</section>

<?php
$page->setFooterUp();
?>
