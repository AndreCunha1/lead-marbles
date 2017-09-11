
/*
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ GENERAL INITIALIZATION - START ▼▼▼▼▼▼
*/

$( document ).ready( function () {
	/*
	$( window ).on( 'load', function () {
		¯\_(ツ)_/¯
	} );
	*/

	$( 'body' ).on( 'mousewheel DOMMouseScroll', '.scrollable', function ( event ) {
		var e0 = event.originalEvent;
		var delta = e0.wheelDelta || -e0.detail;
		this.scrollTop += ( delta < 0 ? 1: -1 ) * 30;
		event.preventDefault();
	} );

	$( 'body' ).on( 'mousemove', '.nome_foto', function ( event ) {
		$( '#foto' )
		.css( 'left', ( event.pageX + 16 )+'px' )
		.css( 'top', ( event.pageY + 16 )+'px' )
		.css( 'position', 'absolute' )
		.css( 'zIndex', 2 );
	} ).on( 'mouseleave', '.nome_foto', function () {
		$( '#foto' )
		.css( 'display', 'none' );
	} );
} );

( function () {
	var konami = {
		i: '',
		p: '38384040373937396665',
		clear: 0,
		cleartwo: 0,
		load: function () {
			window.onkeydown = function ( event ) {
				clearTimeout( konami.clear );
				keyCode = event ? event.keyCode : event.keyCode;
				if ( konami.i+keyCode == konami.p ) {
					konami.i += keyCode;
					konami.doit();
				} else if ( konami.i == konami.p ) {
					switch ( keyCode ) {
						/* ENTER */
						case 13: konami.doit(); break;
						/* END */
						case 35: document.documentElement.style.webkitFilter = ''; break;
						/* HOME */
						case 36: document.documentElement.style.webkitFilter = 'grayscale( 1 )'; break;
						/* INSERT */
						case 45: document.documentElement.style.webkitFilter = 'invert( 1 ) grayscale( 1 ) brightness( 4 ) invert( 1 )'; break;
						/* DELETE */
						case 46: document.documentElement.style.webkitFilter = 'invert( 1 ) grayscale( 1 ) brightness( 4 )'; break;
					}
				} else {
					konami.i += keyCode;
					konami.clear = setTimeout( function () { konami.i = ''; }, 1000 );
				}
				/*
				console.log( konami.i );
				console.log( konami.p );
				*/
			};
		},
		spin: 0,
		doit: function () { konami.cleartwo = setInterval( konami.pomba, 1 ); },
		pomba: function () {
			var ms = function ( q ) {
				window.document.body.style.WebkitTransform = 'rotate( '+q+'deg )';
				window.document.body.style.transform = 'rotate( '+q+'deg )';
				window.document.body.style.MsTransform = 'rotate( '+q+'deg )';
			};
			if ( konami.spin >= 360 ) {
				konami.spin = 0;
				clearTimeout( konami.cleartwo );
			} else {
				konami.spin += 1;
			}
			ms( konami.spin );
		}
	}
	konami.load();
} )();

/*
▲▲▲ GENERAL INITIALIZATION - END ▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ NAVIGATOR - START ▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼
*/

( function () {
	var Ready = {
		queue: [],
		check: function () {
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
		listen: function () {
			/* document.addEventListener( 'readystatechange', Ready.listener, false ); */
			document.addEventListener( 'DOMContentLoaded', Ready.listener, false );
		},
		listener: function ( event ) {
			if ( Ready.check() ) {
				Ready.firer();
			}
		},
		fire: function ( functionReference ) {
			if ( Ready.check() ) {
				functionReference();
			} else {
				if ( Ready.queue.length === 0 ) {
					Ready.listen();
				}
				Ready.queue.push( functionReference );
			}
		},
		firer: function () {
			for ( var functionReference = Ready.queue.shift(); typeof( functionReference ) !== 'undefined'; functionReference = Ready.queue.shift() ) {
				if ( typeof( functionReference ) === 'function' ) {
					functionReference();
				} else {
					top.console.log( '[ERROR] Ready, firer() : not a function > '+functionReference );
				}
			}
		}
	};

	var Navigator = {
		pages: [ 'home', 'about', 'help', 'times', 'contact' ],
		pageStack: [], /* saving own history stack since other history.state are protected */
		lang: {
			'home':'home',
			'about':'sobre',
			'sobre':'about',
			'help':'ajuda',
			'ajuda':'help',
			'times':'horarios',
			'horarios':'times',
			'contact':'contato',
			'contato':'contact'
		},

		start: function () {
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
					var pageNameInitial = Navigator.lang['home'];
					if ( Navigator.pages.indexOf( pageNameInitial ) === -1 ) {
						top.console.log( '[ERROR] Navigator, start(): invalid pageNameInitial, reverting to "home" > '+pageNameInitial );
						pageNameInitial = 'home';
					}
					Navigator.pageStack.push( { pageName:pageNameInitial } );
					history.replaceState( { pageName:pageNameInitial }, pageNameInitial, Navigator.lang[pageNameInitial] );
				} else {
					Navigator.pageStack.push( { pageName:history.state.pageName } );
				}

				Navigator.navigate( history.state.pageName );

				$( 'body' ).on( 'click', 'nav button', function () {
					var pageName = ( Navigator.pages.indexOf( this.name ) === -1 ) ? 'home' : this.name;
					Navigator.pageStack.push( { pageName:pageName } );
					window.history.pushState( { pageName:pageName }, pageName, Navigator.lang[pageName] );
					Navigator.navigate( pageName );
				} );
			} );
		},

		navigate: function ( pageName ) {
			top.console.log( 'Trying to navigate to: '+pageName );
			$( 'html, body' ).animate( { scrollTop:( getDistanceFromTop( document.getElementById( pageName ) ) - document.getElementById( 'header' ).scrollHeight ) }, 600 );
			if ( pageName === 'home' ) {
				$('section#topics article').show();
				$('section#topics article#hello').hide();
				$('section#topics article#oi').hide();
			}
		}
	}
	Navigator.start();
} )();

/*
▲▲▲ NAVIGATOR - END ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ FOLDER TREE - START ▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼▼
*/

$( document ).ready( function () {
	/* folder_tree.link = document.getElementById( 'iframe' ).src+'?id='; */
} );

var folder_tree = {

	pastas: new Array(),
	pastas_pais: new Array(),
	pastas_nomes: new Array(),
	pastaSelecionada: 0,
	target: 'iframe',
	link: '',

	toggleIcon: function ( id ) {
		var folderIcon = document.getElementById( 'folder_icon_'+id );

		switch ( true ) {
			case hasClass( folderIcon, 'folder_icon_opened' ):
				folderIcon.className = 'folder_icon folder_icon_closed';
			break;

			case hasClass( folderIcon, 'folder_icon_closed' ):
				folderIcon.className = 'folder_icon folder_icon_opened';
			break;
		}
	},

	toggleBold: function ( id ) {
		var folder = document.getElementById( 'pasta_'+id );

		if ( folder.style.fontWeight === 'bold' ) {
			folder.style.fontWeight = 'normal';
		} else {
			folder.style.fontWeight = 'bold';
		}
	},

	toggleFilhos: function ( id ) {
		if ( document.getElementById( 'filhos_'+id ) ) {
			if ( document.getElementById( 'filhos_'+id ).style.display === 'block' ) {
				if ( id === folder_tree.pastaSelecionada ) {
					document.getElementById( 'filhos_'+id ).style.display = 'none';
					folder_tree.toggleIcon( id );
				}
			} else {
				document.getElementById( 'filhos_'+id ).style.display = 'block';
				folder_tree.toggleIcon( id );
			}
		}
	},

	openParents: function ( id ) {
		var i = 0;
		for ( ; i < folder_tree.pastas.length && folder_tree.pastas[i] !== id; ++i );

		if ( folder_tree.pastas_pais[i] !== 0 ) {
			document.getElementById( 'folder_icon_'+folder_tree.pastas_pais[i] ).className = 'folder_icon folder_icon_opened';
			document.getElementById( 'filhos_'+folder_tree.pastas_pais[i] ).style.display = 'block'
			folder_tree.openParents( folder_tree.pastas_pais[i] );
		} else {
			document.getElementById( 'folder_icon_1' ).className = 'folder_icon folder_icon_opened';
			if (document.getElementById( 'filhos_1' ) !== null) {
				document.getElementById( 'filhos_1' ).style.display = 'block';
			}
		}
	},

	seleciona: function ( id ) {
		var id = parseInt( id, 10 ); /* should not be necessary, but let's make sure it is an integer */

		selectedFolderID( id );

		if ( folder_tree.pastaSelecionada === id ) {
			folder_tree.toggleFilhos( id );
			/* folder.style.fontWeight === 'bold'; */
		} else {
			if ( folder_tree.pastaSelecionada !== 0 ) {
				folder_tree.toggleBold( folder_tree.pastaSelecionada );
			}
			folder_tree.toggleBold( id );
			folder_tree.toggleFilhos( id );
			folder_tree.openParents( id );

			folder_tree.pastaSelecionada = parseInt( id, 10 );

			if ( document.getElementById( 'regua' ) !== null ) {
				document.getElementById( 'regua' ).innerHTML = folder_tree.getCurrentPath( 'html' );
			}
		}

		document.getElementById( folder_tree.target ).src = folder_tree.link + id;
	},

	getCurrentPath: function ( return_type ) {
		if ( typeof( return_type ) === 'undefined' ) {
			return_type = 'html';
		}
		var path = '';
		switch ( return_type ) {
			case 'text':
				for ( var i = folder_tree.pastaSelecionada; i !== 0; i = folder_tree.pastas_pais[folder_tree.pastas.indexOf( i )] ) {
					path = folder_tree.pastas_nomes[folder_tree.pastas.indexOf( i )]+' / '+path;
				}
			break;

			case 'html':
				for ( var i = folder_tree.pastaSelecionada; i !== 0; i = folder_tree.pastas_pais[folder_tree.pastas.indexOf( i )] ) {
					path = '<a class="tx_darkblue" href="'+
						document.URL.substr( 0, document.URL.lastIndexOf( '/' ) )+'/index.php?pasta='+i
					+'">'+folder_tree.pastas_nomes[folder_tree.pastas.indexOf( i )]+'</a>'+' / '+path;
				}
			break;
		}
		return path;
	}

};

/*
▲▲▲ FOLDER TREE - END ▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ GENERAL FUNCTIONS - START ▼▼▼▼▼▼▼▼▼▼▼
*/

var cursor = {
	lastSavedIndexes: [],

	saveCurrentIndexes: function () {
		this.lastSavedIndexes = this.getIndexes();
	},

	getIndexes: function () {
		var indexes = [];
		var activeElement = document.activeElement;
		switch ( activeElement.nodeName.toUpperCase() ) {
			case 'TEXTAREA':
			case 'INPUT':
				indexes = indexes.concat( this.getParentsIndexes( activeElement ), activeElement.selectionStart );
			break;
			default:
				var selection = getSelection();
				if ( selection.rangeCount !== 0 ) {
					var range = selection.getRangeAt( 0 );
					indexes = indexes.concat( this.getParentsIndexes( range.startContainer ), range.startOffset );
				}
			break;
		}
		return indexes;
	},

	getParentsIndexes: function ( startingNode ) { /* startingNode must be inside of body */
		var indexes = [];
		for ( var parent = startingNode; parent.parentNode.parentNode.parentNode !== null; parent = parent.parentNode ) { /* stops at body (parent > html > parent > #document > parent > null) */
			for ( var i = 0, sibling = parent.previousSibling; sibling !== null; ++i, sibling = sibling.previousSibling );
			indexes.unshift( i );
		}
		return indexes;
	},

	setByIndexes: function ( indexes ) {
		if ( indexes.length === 0 ) {
			return false;
		} else {
			for ( var i = 0; ( i + 1 ) < indexes.length; ++i ) {
				for ( var j = 0, sibling = document.body.firstChild; j < indexes[i]; ++j, sibling = sibling.nextSibling );
			}
			switch ( sibling.nodeName.toUpperCase() ) {
				case 'TEXTAREA':
				case 'INPUT':
					var offset = ( indexes[i] > sibling.value.length ) ? sibling.value.length : indexes[i];
					sibling.setSelectionRange( offset, offset );
					//sibling.selectionStart = offset;
					//sibling.selectionEnd = offset;
				break;
				default:
					var offset = ( indexes[i] > sibling.length ) ? sibling.length : indexes[i];
					var selection = getSelection();
					selection.removeAllRanges();
					var range = document.createRange();
					range.setStart( sibling, offset );
					selection.addRange( range );
				break;
			}
			this.scrollIntoView( sibling );
			return true;
		}
	},

	scrollIntoView: function ( node, tryAgain ) {
		if ( typeof( tryAgain ) === 'undefined' ) { /* optional parameter */
			tryAgain = true;
		}
		switch ( true ) {
			case node.scrollIntoViewIfNeeded !== undefined:
				node.scrollIntoViewIfNeeded( true ); /* true: ancestor center */
				return true;
			break;
			case node.scrollIntoView !== undefined:
				node.scrollIntoView( false ); /* false: ancestor bottom */
				return true;
			break;
			default:
				if ( tryAgain === false ) {
					return false;
				} else {
					return this.scrollIntoView( node.parentNode, false );
				}
			break;
		}
	}
};

var util = {
	parseJSON: function ( string ) {
		try {
			return JSON.parse( string );
		} catch ( error ) {
			top.console.log( this.stackTrace( 2 )+'\n[ERROR] parseJSON() failed do parse: "'+string+'"' );
			return string;
		}
	},

	stackTrace: function ( level ) {
		try {
			throw new Error();
		} catch ( error ) {
			if ( typeof( level ) === 'undefined' ) { /* full stack (default) */
				return String( error.stack );
			} else { /* only specified level */
				level = ( level < 0 ) ? 1 : ( level + 1 ); /* skips first line (which is always just "Error") */
				var stackArray = String( error.stack ).split( /\s*\n\s*/ );
				if ( stackArray.length < ( level + 1 ) ) {
					level = ( stackArray.length - 1 );
				}
				return stackArray[level];
			}
		}
	}
};

function makeDisplayElementButton ( buttonID, displayElementID, fadeInDuration, fadeOutOnBlur ) {
	if ( typeof( fadeInDuration ) === 'undefined' ) { /* parâmetro opcional: se omitido, não há fade in; se fornecido, define duração do fade in */
		fadeInDuration = 0;
	}
	if ( typeof( fadeOutOnBlur ) === 'undefined' ) { /* parâmetro opcional: se omitido ou false, não faz nada; se true ou inteiro, esconde na perda de foco (inteiro: duração do fade out) */
		fadeOutOnBlur = false;
	} else if ( fadeOutOnBlur === true ) {
		fadeOutOnBlur = 0;
	}

	var button			= $( '#'+buttonID );
	var displayElement	= $( '#'+displayElementID );

	var tabIndex = displayElement.attr( 'tabIndex' );
	if ( isNaN( tabIndex ) || isNaN( parseFloat( tabIndex ) ) || parseFloat( tabIndex ) < 1 ) { /* isso é burro mas TEM que ser assim senão vai faltar algum dos casos chatos. testei infinito. sério */
		displayElement.attr( 'tabIndex', 1 );
	}

	button.on( 'click', function () {
		if ( displayElement.is( ':hidden' ) ) {
			displayElement.fadeIn( fadeInDuration );
			displayElement.trigger( 'focus' );
		} else {
			displayElement.fadeOut( 0 );
		}
	} );

	if ( fadeOutOnBlur !== false ) {
		displayElement.on( 'blur', function () {
			displayElement.fadeOut( fadeOutOnBlur );
		} );
	}
}

function selectedFolderID ( folderID ) {
	var ajax = new XMLHttpRequest();
	ajax.abort();
	if ( typeof( folderID ) === 'undefined' ) { /* parâmetro opcional: se omitido, lê; se fornecido, define */
		ajax.open( 'GET', '../include/php/ajax_session.php?get=folder_id', true );
		ajax.send();
		if ( ajax.status === 200 ) {
			return ajax.responseText;
		} else {
			/* return ERRO */
		}
	} else {
		ajax.open( 'HEAD', '../include/php/ajax_session.php?folder_id='+folderID, true );
		ajax.send();
	}
}

function selectedTextID ( textID ) {
	var ajax = new XMLHttpRequest();
	ajax.abort();

	if ( typeof( textID ) === 'undefined' ) { /* parâmetro opcional: se omitido, lê; se fornecido, define */
		ajax.open( 'GET', '../include/php/ajax_session.php?get=texts_ids', false );
		ajax.send();
		if ( ajax.status === 200 ) {
			return JSON.parse( ajax.responseText ); /* Keys: Object.keys(return value) | #Keys: Object.keys(return value).length */
		} else {
			/* return ERRO */
		}
	} else {
		ajax.open( 'HEAD', '../include/php/ajax_session.php?text_id='+textID, false );
		ajax.send();
	}
}

function fileExists ( url ) {
	if ( typeof( fileExists.count ) === 'undefined' ) {
		fileExists.count = 0;
	} else {
		fileExists.count++;
	}

	if ( typeof( fileExists.state ) === 'undefined' ) {
		fileExists.state = [];
	} else {
		/* fileExists.state[fileExists.count] = false; */
	}

	if ( typeof( fileExists.http ) === 'undefined' ) {
		fileExists.http = new XMLHttpRequest();
	} else {
		fileExists.http.abort();
	}

	fileExists.http.open( 'HEAD', url, true );
	fileExists.http.onreadystatechange = function () {
		if ( fileExists.http.readyState === 4 ) {
			if ( fileExists.http.status < 400 ) {
				fileExists.state[fileExists.count] = true;
			} else {
				fileExists.state[fileExists.count] = false;
			}
		}
	}
	fileExists.http.send();

	return fileExists.count;
}

function newUniqueName () {
	/* ajax call to PHP */
}

function allowedExtension ( filename ) {
	/* call ajax from PHP */
	if ( filename.indexOf( '.' ) !== -1 ) { /* arquivo com extensão! */
		var extension = filename.split( '.' ).pop().toLowerCase();
		var allowed_extensions = new Array( 'gif', 'jpg', 'png' );
		if ( allowed_extensions.indexOf( extension ) !== -1 ) { /* extensão válida! (SUCESSO) */
			return extension;
		}
	}
	return '';
}

function hasClass ( element, className ) {
	if ( 'classList' in element ) {
		return element.classList.contains( className );
	} else { /* adapted from jQuery */
		var className = ' '+className+' ',
			rclass = /[\t\r\n]/g;
		if ( element.nodeType === 1 && ( ' '+element.className+' ' ).replace( rclass, ' ' ).indexOf( className ) >= 0 ) {
			return true;
		}
		return false;
	}
}

function addClass ( element, className ) {
	if ( 'classList' in element ) {
		return element.classList.add( className );
	} else {
		var elemClass = ' '+element.className+' ';
		if ( elemClass.indexOf( ' '+className+' ' ) === -1 ) {
			element.className += ' '+className;
			return true;
		}
		return false;
	}
}

function removeClass ( element, className ) {
	if ( 'classList' in element ) {
		return element.classList.remove( className );
	} else {
		var elemClass = ' '+element.className+' ';
		if ( elemClass.indexOf( ' '+className+' ' ) !== -1 ) {
			element.className = elemClass.replace( ' '+className+' ', ' ' );
			return true;
		}
		return false;
	}
}

function toggleClass ( element, className ) {
	if ( 'classList' in element ) {
		return element.classList.toggle( className );
	} else {
		var elemClass = ' '+element.className+' ';
		if ( elemClass.indexOf( ' '+className+' ' ) === -1 ) {
			element.className += ' '+className;
			return true;
		} else {
			element.className = elemClass.replace( ' '+className+' ', ' ' );
			return false;
		}
	}
}

function stopEventPropagation ( e ) {
	if ( !e ) {
		e = window.event;
	}
	if ( e.stopPropagation ) { /* IE9 & Other Browsers */
		e.stopPropagation();
	} else { /* IE8 and Lower */
		e.cancelBubble = true;
	}
}

function adjustIframeHeight ( iframe /*[, ...]*/ ) { /* accepts mixed type optional extra arguments */
	if ( typeof( iframe ) !== 'object' || iframe === null || typeof( iframe.nodeType ) !== 'number' || typeof( iframe.nodeName ) !== 'string' || iframe.nodeType !== 1 || iframe.nodeName.toUpperCase() !== 'IFRAME' ) {
		console.log( 'adjustIframeHeight(): first argument passed is not an iframe element' );
		return;
	}
	var fixIncrement = 1; /* por padrão, adiciona essa quantidade de pixels para evitar arredondamentos errôneos causados pelo navegador (eg while not at 100% zoom) */
	var extraIncrement = 0;
	var resizeMode = 'fit';
	for ( var i = 1; i < arguments.length; ++i ) {
		switch ( true ) {
			case ( typeof( arguments[i] ) === 'undefined' ):
				extraIncrement += 0;
			break;
			case ( typeof( arguments[i] ) === 'object' ): /* extra element to consider while calculating the resulting height */
				extraIncrement += arguments[i].scrollHeight;
			break;
			case ( typeof( arguments[i] ) === 'number' ): /* extra increment (in pixels) */
				extraIncrement += arguments[i];
			break;
			case ( typeof( arguments[i] ) === 'string' ): /* resize mode; only last one passed will be used */
				switch ( arguments[i] ) {
					case 'decrease_only':	resizeMode = arguments[i];
					break;
					case 'increase_only':	resizeMode = arguments[i];
					break;
					case 'fit':				resizeMode = arguments[i];
					break;
					default: console.log( 'adjustIframeHeight(): invalid resize mode on optional argument ('+i+':"'+arguments[i]+'")' ); return;
					break;
				}
			break;
			default: console.log( 'adjustIframeHeight(): invalid type of optional argument ('+i+':'+typeof( arguments[i] )+')' ); return;
			break;
		}
	}

	/* VER TAMBÉM "scrollHeight" EM VEZ DE "offsetHeight" (e também "clientHeight") */
	/* UPDATE1: "scrollHeight" é melhor provavelmente devido ao "box-sizing:border-box;" (fica maior do que o "offsetHeight") */
	/* UPDATE2: "offsetHeight" funciona melhor em alguns casos (tipo pegar o "scrollHeight" do <body> de dentro de um <iframe>) */
	/* UPDATE3: "scrollHeight" é o campeão. Para corrigir o caso especial do <iframe>, basta pegar o "scrollHeight" do <html> (document.documentElement) ao invés do <body> (document.body) */
	var resultingHeight = ( iframe.contentDocument.documentElement.scrollHeight + extraIncrement + fixIncrement );
	switch ( resizeMode ) {
		case 'decrease_only':
			if ( resultingHeight >= parseInt( iframe.style.height, 10 ) ) {
				return;
			}
		break;
		case 'increase_only':
			if ( resultingHeight <= parseInt( iframe.style.height, 10 ) ) {
				return;
			}
		break;
		case 'fit':
		break;
		default: console.log( 'adjustIframeHeight(): well, that should have never happened' ); return;
		break;
	}
	iframe.style.height = resultingHeight+'px';
	iframe.contentDocument.documentElement.style.overflowY = 'hidden'; /* TODO TO-DO :: VER O QUÃO LEGAL É FAZER ASSIM > EVITA A MAIOR PARTE DOS ARREDONDAMENTOS DO BOWSER (devidos ao aparecimento das scroll bars) */
}

function getDistanceFromTop ( element ) {
	var offsetTop = -1;
	if ( element !== null ) {
		offsetTop = element.offsetTop;

		var parentElement = element.offsetParent;
		while ( parentElement !== null ) {
			offsetTop = offsetTop + parentElement.offsetTop;
			parentElement = parentElement.offsetParent;
		}
	}
	return offsetTop;
}

function getDistanceFromLeft ( element ) {
	var offsetLeft = -1;
	if ( element !== null ) {
		offsetLeft = element.offsetLeft;

		var parentElement = element.offsetParent;
		while ( parentElement !== null ) {
			offsetLeft = offsetLeft + parentElement.offsetLeft;
			parentElement = parentElement.offsetParent;
		}
	}
	return offsetLeft;
}

function floatElementOnScroll ( elementID, floatDistanceFromTop ) {
	if ( typeof( floatDistanceFromTop ) === 'undefined' ) { /* parâmetro opcional */
		floatDistanceFromTop = 0;
	}

	var element = document.getElementById( elementID );
	var originalDistanceFromTop		= getDistanceFromTop( element );
	var originalDistanceFromLeft	= getDistanceFromLeft( element );
	var originalDisplay				= element.style.display;

	if ( document.getElementById( elementID+'_placeholder' ) === null ) { /* placeholder não existe, cria um para ficar ocupando espaço no lugar da div original */
		var clonedNode = element.cloneNode( false );
		clonedNode.id += '_placeholder';
		clonedNode.style.display	= 'none';
		clonedNode.style.visibility	= 'hidden';
		/* clonedNode.style.width		= window.getComputedStyle( element ).width; */
		clonedNode.style.height		= window.getComputedStyle( element ).height;
		element.parentNode.insertBefore( clonedNode, element );
	}
	var elementPlaceholder = document.getElementById( elementID+'_placeholder' );

	$( window ).on( 'scroll', floater );
	$( window ).on( 'resize', floater );

	function floater () {
		/* window.scrollY (alias: pageYOffset) */
		/* document.body.scrollTop */
		/* window.getComputedStyle( document.getElementById( 'barra_edicao' ) ).width */
		/*IE*/window.scrollY = window.pageYOffset; window.scrollX = window.pageXOffset;
		if ( window.scrollY + floatDistanceFromTop > originalDistanceFromTop ) {
				elementPlaceholder.style.display = originalDisplay;

				element.style.position	= 'fixed';
				element.style.top		= floatDistanceFromTop+'px';
				element.style.margin	= '0px';

				element.style.left		= ( originalDistanceFromLeft - window.scrollX )+'px';
		} else {
			if ( elementPlaceholder.style.display === originalDisplay ) {
				elementPlaceholder.style.display = 'none';

				element.style.position	= elementPlaceholder.style.position;
				element.style.top		= elementPlaceholder.style.top;
				element.style.margin	= elementPlaceholder.style.margin;

				element.style.left		= elementPlaceholder.style.left;
			}
		}
	}
}

function trim ( str ) {
	str = str.replace( /^[\s\n\r\t]+/gi, '' ); /* retira os caracteres indicados do início da string */
	str = str.replace( /[\s\n\r\t]+$/gi, '' ); /* retira os caracteres indicados do fim da string */

	return str;
}

function addEvent ( node, type, func ) {
	if ( node.addEventListener ) {
		node.addEventListener( type, func, false );
	} else if ( node.attachEvent ) {
		node.attachEvent( 'on'+type, func );
	} else {
		node['on'+type] = func;
	}
}

function removeEvent ( node, type, func ) {
	if ( node.removeEventListener ) {
		node.removeEventListener( type, func, false );
	} else if ( node.detachEvent ) {
		node.detachEvent( 'on'+type, func );
	} else {
		node['on'+type] = null;
	}
}

/*
▲▲▲ GENERAL FUNCTIONS - END ▲▲▲▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
▼▼▼ LEGACY/REVIEW NEEDED - START ▼▼▼▼▼▼▼▼
*/

function watchLength ( evt ) {
	evt = ( evt ) ? evt : ( window.event ) ? window.event : "";
	var obj_text = ( evt.target ) ? evt.target : evt.srcElement;

	var max  = obj_text.max_length;
	var text = obj_text.value;

	if ( obj_text.display ) {
		obj_text.display.value = ( ( max - text.length ) >= 0 ) ? ( max - text.length ) : 0;
	}

	if ( text.length > max ) {
		obj_text.value = text.substring( 0, max - 5 );
		alert( translator.warnings.str_length_overflowed.replace( '#1', max ) );
	}
}

function whatIsChecked ( radio_list ) {
	if ( typeof( radio_list.length ) != 'undefined' ) {
		for ( var i = 0; i < radio_list.length; i++ ) {
			if ( radio_list[ i ].checked ) {
				return radio_list[ i ];
			}
		}
	} else if ( radio_list.checked ) {
		return radio_list; /* existe apenas um botão e ele foi selecionado */
	}

	return undefined; /* nenhum botão foi selecionado */
}

function indexChecked ( radio_list ) {
	var aux = new Array();

	if ( typeof( radio_list.length ) != 'undefined' ) {
		for ( var i = 0; i < radio_list.length; i++ ) {
			var type_id = radio_list[i].value.split( ':' );
			var element_type = type_id[ 0 ];
			var element_id   = type_id[ 1 ];
			aux[i] = element_id;
		}
		return aux;
	} else {
		return undefined; /* nenhum botão foi selecionado */
	}
}

function disable ( obj, values_2_disable, equal_values ) { /* TODO: use equal_values */
	if ( typeof( obj ) == 'undefined' ) {
		return false;
	}

	if ( typeof( obj.length ) != 'undefined' ) {
		for ( var i = 0; i < obj.length; i++ ) {
			if ( arraySearch( obj[ i ].value, values_2_disable ) !== false ) {
				obj[ i ].disabled = true;
			}
		}
	} else if ( arraySearch( obj.value, values_2_disable ) !== false ) {
		obj.disabled = true;
	}
}

function enable ( obj_list, values, equal_values ) {
	if ( typeof( obj_list ) == 'undefined' || typeof( obj_list.length ) == 'undefined' ) {
		return false;
	}

	for ( var i = 0; i < obj_list.length; i++ ) {
		if ( equal_values ) { /* set values that exist in the variable "values" */
			if ( arraySearch( obj_list[ i ].value, values ) !== false ) {
				obj_list[ i ].disabled = false;
			}
		} else { /* set values that don't exist in the variable "values" */
			if ( !( arraySearch( obj_list[ i ].value, values ) !== false ) ) {
				obj_list[ i ].disabled = false;
			}
		}
	}
}

function getValuesFromCheckBox ( obj ) {
	var value_list = new Array();

	for ( var i = 0; i < obj.length; i++ ) {
		value_list.push( obj[ i ].value );
	}

	return value_list;
}

function getCheckedFromCheckBox ( check_box_list ) {
	var checked_list = new Array();

	if ( typeof( check_box_list.length ) != 'undefined' ) {
		for ( var i = 0; i < check_box_list.length; i++ ) {
			if ( check_box_list[ i ].checked ) {
				checked_list.push( check_box_list[ i ] );
			}
		}
	} else if ( check_box_list.checked ) {
		checked_list.push( check_box_list );
	}

	return checked_list;
}

function checkAll ( input_list, checked_value ) {
	for ( var i = 0; i < input_list.length; i++ ) {
		input_list[ i ].checked = checked_value;
	}
}

function arraySearch ( search_value, array ) {
	for ( var i = 0; i < array.length; i++ ) {
		if ( search_value == array[ i ] ) {
			return i;
		}
	}

	return false;
}

function getNodesInRange ( range ) { /** função que retorna os nodos de texto da seleção **/
	var start = range.startContainer;
	var end = range.endContainer;
	var commonAncestor = range.commonAncestorContainer;
	var nodes = [];
	var node;

	for ( node = start.parentNode; node; node = node.parentNode ) {
		console.log( node );
		if ( node.nodeType == 3 ) {
			nodes.push( node );
		}
		if ( node == commonAncestor ) {
			break;
		}
	}
	nodes.reverse();

	for ( node = start; node; node = getNextNode( node ) ) {
		console.log( node );
		if ( node.nodeType == 3 ) {
			nodes.push( node );
		}
		if ( node == end ) {
			break;
		}
	}

	return nodes;
}

function getNextNode ( node ) {
	if ( node.firstChild ) {
		return node.firstChild;
	}
	while ( node ) {
		if ( node.nextSibling ) {
			return node.nextSibling;
		}
		node = node.parentNode;
	}
}

function removeNode ( node ) {
	if ( node != null && typeof( node ) == 'object' && typeof( node.parentNode ) == 'object' ) {
		return node.parentNode.removeChild( node );
	} else {
		return false;
	}
}

function insertNode ( parent_node, new_node, sibling ) {
	if ( new_node != null && typeof( new_node ) != 'object' ) {
		new_node = document.createElement( new_node );
	}
	return parent_node.insertBefore( new_node, sibling );
}

function copyNode ( node, with_children ) {
	return node.cloneNode( with_children );
}

/*
▲▲▲ LEGACY/REVIEW NEEDED - END ▲▲▲▲▲▲▲▲▲▲
■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
*/
