<script type="text/javascript">
	'use strict';

	( function () { /* awesome inaccessible local scope */
		var self = this; /* just saving a self reference */
		/*
		self.myObject = { value:0 };
		self.anObjectName = 'myObject';
		self[self.anObjectName].value++;
		console.log( self );
		*/
	} ).call( {} );
</script>
