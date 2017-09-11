<?php
require_once( dirname( __FILE__ ).DIRECTORY_SEPARATOR.'system.inc.php' );

$page = new HTMLPage( ETC_BASE_URL, $translator, 'Token Authentication', 'HEADER' );
$page->setHeaderUp();
$page->putSectionHeader();
$page->putHorizontalSeparator();

Token::act();

$page->setFooterUp();
?>
