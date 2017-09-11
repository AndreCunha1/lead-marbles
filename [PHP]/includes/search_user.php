<?php
require_once( dirname( dirname( dirname( __FILE__ ) ) ).DIRECTORY_SEPARATOR.'system.inc.php' );

$result = $pdo_handler->query( 'SELECT pastas_user.pasta_id, etc_users.name, etc_users.user_id
								FROM pastas_user, etc_users
								WHERE pastas_user.pasta_id = '.$selectedFolderID.'
								AND pastas_user.user_id = etc_users.user_id
								AND etc_users.user_id != '.$user_id.'
								ORDER BY etc_users.name ASC;' );

$values_labels = $ids = array();

foreach ( $result as $user ) {
	$user_id = $user['user_id'];
	$values_labels[$user_id] = $user['name'];
	$ids[$user_id] = 'user_id:'.$user_id;
}

$page = new HTMLPage( ETC_BASE_URL, $translator, 'Busca usuários', 'EMPTY' );
$page->addJsToLoad( ETC_BASE_URL.'include/js/search_user.js' );
$page->setHeaderUp();
?>

<div class="content_users_list" style="width:600px; padding:20px 10px;">
	<form id="form_1" name="form_1">
		<p>
			<label>
				<input type="checkbox" value="0" id="selTodos" onclick="selecionaTudo( this );"/>
				<?php echo $translator->getTranslation( 'lbl_select_subject' ); ?>
			</label>
		</p>
		<?php
		if ( !empty( $result ) ) {
			?>
			<p>
				<?php echo $translator->getTranslation('lbl_search_result'); ?>
			</p>
			<div id="users">
				<?php
				$check_box = new XHTML_CheckBox( $values_labels, 'user', $ids );
				$check_box->printCheckBox();
				?>
			</div>
			<p>
				<?php echo $translator->getTranslation( 'lbl_text_user_disabled' ); ?>
			</p>
			<p>
				<?php
				$page->insertLabelledButton( array( 'href' => 'JavaScript: callInsertUser();', 'icon-left' => ETC_BASE_URL.'images/ico_yes.png', 'label' => $translator->getTranslation( 'lbl_confirm' ), 'background-image' => ETC_BASE_URL.'images/btn_base_white.png', 'class' => 'tx_small tx_darkblue', 'style' => 'margin-right:4px; padding:4px; font-weight:bold;' ) );
				?>
			</p>
			<?php
		} else {
			?>
			<p>
				<?php echo $translator->getTranslation( 'lbl_failed_search' ); ?>
			</p>
			<?php
		}
		?>
		<input type="hidden" name="who" value="<?php echo $_GET['who']; ?>">
	</form>
</div>

<?php
$page->setFooterUp();
?>
