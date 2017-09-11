<?php

class Recommender extends BasicClass {

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

	public static function printHTML ( $text, $user ) {
		global $translator;
		global $pdo_handler;
		$kw_user = '[]';
		$kw_del = '[]';
		$results = $pdo_handler->query( 'SELECT `kw_user`, `kw_del`
										FROM `etc_recommender_keywords`
										WHERE `id_user` = :id_user AND `id_text` = :id_text
										ORDER BY `timestamp` DESC
										LIMIT 1',
										array( ':id_user' => $user, ':id_text' => $text ) );
		if ( !empty( $results ) ) {
			$kw_user = $results[0]['kw_user'];
			$kw_del = $results[0]['kw_del'];
		}
		?>
		<script type="text/javascript">
			'use strict';

			$( document ).ready( function () {
				$( window ).on( 'load', function () {
					$( '#Recommender' ).draggable( {
						addClasses: false,
						containment: 'window',
						handle: '.bx_title',
						/*cancel: '#botoes_recomends, #recomendador-conteudo, #keyword_conteudo',*/
						scroll: false,
						disabled: false,
						stop: function () { this.style.width = this.style.height = 'initial'; }
					} );
				} );
			} );
		</script>

		<!-- RECOMENDADOR -->
		<div id="Recommender" style="display:none; position:fixed; left:200px; top:180px; z-index:2;">
			<div id="recomendador-body" class="flex_fixed bg_white bx_border" style="width:300px;">
				<div id="recomendador-cabecalho" class="bx_title" style="cursor:move; text-align:center;">
					<?php echo $translator->getTranslation( 'lbl_recommender' ); ?>
					<img src="<?php echo ETC_BASE_URL; ?>images/ico_no.png" class="fill_dark" style="float:right; cursor:pointer;" onclick="showRecommender();">
				</div>
				<div class="flex_col">
					<div id="botoes_recomends" class="flex_row">
						<div id="bot_recom_web"			class="bot_recom tab_active"	style="padding:2px; text-align:center; background-color:#EDC31A;"><img style="border:1px solid #FFFFFF;" src="<?php echo ETC_BASE_URL; ?>images/editor/recomendador/tab_web.png" /></div>
						<div id="bot_recom_imagem"		class="bot_recom"				style="padding:2px; text-align:center; background-color:#409344;"><img style="border:1px solid #FFFFFF;" src="<?php echo ETC_BASE_URL; ?>images/editor/recomendador/tab_image.png" /></div>
						<div id="bot_recom_video"		class="bot_recom"				style="padding:2px; text-align:center; background-color:#1F4390;"><img style="border:1px solid #FFFFFF;" src="<?php echo ETC_BASE_URL; ?>images/editor/recomendador/tab_video.png" /></div>
						<div id="bot_recom_avail"		class="bot_recom"				style="padding:2px; text-align:center; background-color:#AA44FF;"><img class="glow_white no_hover" src="<?php echo ETC_BASE_URL; ?>images/editor/recomendador/tab_review.png" /></div>
						<div id="bot_recom_favoritos"	class="bot_recom"				style="padding:2px; text-align:center; background-color:#F05B5F;"><img class="glow_white no_hover" src="<?php echo ETC_BASE_URL; ?>images/editor/recomendador/tab_favorite.png" /></div>
					</div>

					<div id="recomendador-conteudo" class="scrollable" style="max-height:240px; overflow:auto;">
						<div id="web_borda" class="recom_borda"><div id="web_conteudo" class="recom_conteudo">Não existem recomendações no momento</div></div>
						<div id="imagem_borda" class="recom_borda" style="display:none"><div id="imagem_conteudo" class="recom_conteudo">Não existem recomendações no momento</div></div>
						<div id="video_borda" class="recom_borda" style="display:none"><div id="video_conteudo" class="recom_conteudo" >Não existem recomendações no momento</div></div>
						<div id="avail_borda" class="recom_borda" style="display:none"><div id="avail_conteudo" class="recom_conteudo">Não existem avaliações no momento</div></div>
						<div id="favoritos_borda" class="recom_borda" style="display:none"><div id="favoritos_conteudo" class="recom_conteudo">Não existem favoritos no momento</div></div>
						<!-- PAGINAS RELACIONADAS AO RESULTADO -->
						<div id="relacionados_borda" class="recom_borda" style="display:none"><div id="relacionados_conteudo" class="recom_conteudo">Não existem recomendações no momento</div></div>
					</div>

					<div id="keyword_conteudo" class="bg_dark" style="padding:8px;">
						<span class="tx_small" style="font-weight:bold;">Termos mais frequentes</span>
						<div id="lista_kw_auto"></div>
						<div id="lista_kw_user"></div>
						<div id="lista_kw_del"></div>
						<input id="add_keyword" type="text" style="margin:2px;" onkeypress="verifica_tecla_kword( event );" />
					</div>
				</div>
			</div>

			<div id="recom_visualizador" class="bg_white bx_border" style="display:none; position:absolute; left:300px; bottom:0px; padding:8px; min-width:128px;">
				<span id="conteudo_titulo" class="tx_big" style="font-weight:bold;">Visualizador</span>
				<img id="fecha_visualizador" src="<?php echo ETC_BASE_URL; ?>images/ico_no.png" class="fill_darkest" style="float:right; cursor:pointer;">
				<div id="sobre_visualizador"></div>
				<div id="conteudo_visualizador"></div>
			</div>
		</div>
		<script>
			recommender_id_user = <?php echo $user ?>;
			recommender_id_text = <?php echo $text ?>;
			recommender_etc_base_url = "<?php echo ETC_BASE_URL ?>";
			kw_user = JSON.parse( '<?php echo $kw_user; ?>' );
			minerador_removidas = JSON.parse('<?php echo $kw_del; ?>');
		</script>
		<link rel="stylesheet" type="text/css" href="<?php echo ETC_BASE_URL; ?>recomendador/recomendador.css" />
		<?php
	}

}
