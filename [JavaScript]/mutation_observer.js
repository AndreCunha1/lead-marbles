<script type="text/javascript">
	'use strict';

	$( document ).ready( function () {
		var observer = new MutationObserver ( function ( mutations ) {
			$( '.listaElementos' ).each( function () { listaElementos.lista( this ); } );
			$( '.categoriaSelect' ).each( function () { categoria.listImmediateChildren( this ); } );
			$( '.listaTecnicos' ).keypress( function () {
				console.log( this.value );
				//listaElementos.lista( this );
			} );
		} );
		if ( document.getElementsByTagName( 'article' ).length > 0 ) {
			observer.observe( document.getElementsByTagName( 'article' )[0], { childList:true, subtree:false, attributes:false, characterData:false } );
		}
		if ( document.getElementsByTagName( 'section' ).length > 0 ) {
			observer.observe( document.getElementsByTagName( 'section' )[0], { childList:true, subtree:false, attributes:false, characterData:false } );
		}
	}
</script>
