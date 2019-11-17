var page = require( 'webpage' ).create();
var system = require( 'system' );
var address, output, size;

if ( system.args.length < 3 || system.args.length > 4 ) {
	console.log( 'Usage: generate_pdf.js URL filename [paperwidth*paperheight|paperformat]' );
	console.log( 'paper (pdf output) examples: "5in*7.5in", "10cm*20cm", "A4", "Letter"' );
	phantom.exit( 1 );
} else {
	address = system.args[1];
	output = system.args[2];
	page.viewportSize = { width:600, height:600 };
	if ( ( system.args.length > 3 ) && ( system.args[2].substr( -4 ) === '.pdf' ) ) {
		size = system.args[3].split( '*' );
		page.paperSize = ( size.length === 2 ) ? { width:size[0], height:size[1], margin:'0px' }
											   : { format:system.args[3], orientation:'portrait', margin:'1cm' };
	}
	page.open( address, function ( status ) {
		if ( status !== 'success' ) {
			console.log( 'ERR' );
			phantom.exit();
		} else {
			window.setTimeout( function () {
				page.render( output );
				console.log( 'OK' );
				phantom.exit();
			}, 200 );
		}
	} );
}
