<script type="text/javascript">
	'use strict';

	( function () {
		var Navigator = {
			pages : [ 'home', 'about', 'products', 'contact' ],
			pageStack : [], /* saving own history stack since other history.state are protected */
			start : function () {
				var Ready = {
					queue : [],
					check : function () {
						switch ( document.readyState ) {
							case 'loading':
								return false;
							break;

							case 'interactive':
							case 'complete':
								return true;
							break;

							default:
								top.console.log( '[ERROR] Ready, check() : unexpected document.readyState > '+document.readyState );
								return false;
							break;
						}
					},
					listen : function () {
						//document.addEventListener( 'readystatechange', Ready.listener, false );
						document.addEventListener( 'DOMContentLoaded', Ready.listener, false );
					},
					listener : function ( event ) {
						if ( Ready.check() ) {
							Ready.firer();
						}
					},
					fire : function ( functionReference ) {
						if ( Ready.check() ) {
							functionReference();
						} else {
							Ready.queue.push( functionReference );
							Ready.listen();
						}
					},
					firer : function () {
						for ( var functionReference = Ready.queue.shift(); typeof( functionReference ) !== 'undefined'; functionReference = Ready.queue.shift() ) {
							if ( typeof( functionReference ) === 'function' ) {
								functionReference();
							} else {
								top.console.log( '[ERROR] Ready, firer() : not a function > '+functionReference );
							}
						}
					}
				};

				window.addEventListener( 'popstate', function ( event ) {
					if ( event.state === null ) {
						top.console.log( '[ERROR] Navigator, start(): null popstate' );
					} else {
						Ready.fire( function () {
							Navigator.pageStack.pop();
							Navigator.navigate( event.state.pageName );
						} );
					}
				}, false );

				Ready.fire( function () {
					if ( history.state === null ) {
						var pageNameInitial = Navigator.translate( '<?php echo empty( $_GET['pageName'] ) ? '' : $_GET['pageName']; ?>' );
						if ( Navigator.pages.indexOf( pageNameInitial ) === -1 ) {
							top.console.log( '[ERROR] Navigator, start(): invalid pageNameInitial, reverting to "home" > '+pageNameInitial );
							pageNameInitial = 'home';
						}
						Navigator.pageStack.push( { pageName:pageNameInitial } );
						history.replaceState( { pageName:pageNameInitial }, pageNameInitial, Navigator.translate( pageNameInitial ) );
					} else {
						Navigator.pageStack.push( { pageName:history.state.pageName } );
					}

					Navigator.navigate( history.state.pageName );

					$( 'body' ).on( 'click', 'nav button', function () {
						var pageName = ( Navigator.pages.indexOf( this.name ) === -1 ) ? 'home' : this.name;
						Navigator.pageStack.push( { pageName:pageName } );
						window.history.pushState( { pageName:pageName }, pageName, Navigator.translate( pageName ) );
						Navigator.navigate( pageName );
					} );
				} );
			},

			translate : function ( pageName ) {
				var translation = '';
				switch ( pageName ) {
					case 'home':		translation = 'home'; break;

					case 'about':		translation = 'sobre'; break;
					case 'sobre':		translation = 'about'; break;

					case 'products':	translation = 'produtos'; break;
					case 'produtos':	translation = 'products'; break;

					case 'contact':		translation = 'contato'; break;
					case 'contato':		translation = 'contact'; break;

					default:			translation = 'TRANSLATE_ERROR'; break;
				}
				return translation;
			},

			mapReloadIfNeeded : function () {
				if ( Navigator.pageStack.length > 1 ) {
					if ( ( Navigator.pageStack[Navigator.pageStack.length - 1].pageName === 'contact' ) || ( Navigator.pageStack[Navigator.pageStack.length - 2].pageName === 'contact' ) ) {
						if ( Navigator.pageStack[Navigator.pageStack.length - 1].pageName !== Navigator.pageStack[Navigator.pageStack.length - 2].pageName ) {
							document.getElementById( 'map' ).src += '';
							top.console.log('RELOAD');
						}
					}
				}
			},

			navigate : function ( pageName ) {
				top.console.log( 'Trying to navigate to: '+pageName );
				Navigator.mapReloadIfNeeded();
				switch ( pageName ) {
					default:
					case 'home':
						document.getElementById( 'banner' ).getElementsByClassName( 'heading' )[0].style.padding = '';
						document.getElementById( 'intro' ).style.display = '';
						document.getElementById( 'about' ).style.display = 'none';
						document.getElementById( 'products' ).style.display = 'none';
						document.getElementById( 'partners' ).style.display = 'none';
						document.getElementById( 'contact' ).style.minHeight = '';
					break;
					case 'about':
						document.getElementById( 'banner' ).getElementsByClassName( 'heading' )[0].style.padding = '2rem';
						document.getElementById( 'intro' ).style.display = 'none';
						document.getElementById( 'about' ).style.display = '';
						document.getElementById( 'products' ).style.display = 'none';
						document.getElementById( 'partners' ).style.display = 'none';
						document.getElementById( 'contact' ).style.minHeight = '';
					break;
					case 'products':
						document.getElementById( 'banner' ).getElementsByClassName( 'heading' )[0].style.padding = '2rem';
						document.getElementById( 'intro' ).style.display = 'none';
						document.getElementById( 'about' ).style.display = 'none';
						document.getElementById( 'products' ).style.display = '';
						document.getElementById( 'partners' ).style.display = '';
						document.getElementById( 'contact' ).style.minHeight = '';
					break;
					case 'contact':
						document.getElementById( 'banner' ).getElementsByClassName( 'heading' )[0].style.padding = '2rem';
						document.getElementById( 'intro' ).style.display = 'none';
						document.getElementById( 'about' ).style.display = 'none';
						document.getElementById( 'products' ).style.display = 'none';
						document.getElementById( 'partners' ).style.display = 'none';
						document.getElementById( 'contact' ).style.minHeight = '96vh';
					break;
				}
				$( 'html, body' ).animate( { scrollTop:( getDistanceFromTop( document.getElementById( pageName ) ) - document.getElementById( 'menu' ).scrollHeight ) }, 600 );
			}
		}
		Navigator.start();
	} )();
</script>
