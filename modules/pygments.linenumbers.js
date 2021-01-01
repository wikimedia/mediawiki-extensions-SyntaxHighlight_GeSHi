$( function () {

	var $lastLine;

	function onHashChange() {
		var $line = $( location.hash );

		if ( $lastLine ) {
			$lastLine.removeClass( 'hll' );
		}

		if ( $line.length ) {
			$line.addClass( 'hll' );
		}

		$lastLine = $line;
	}

	$( window ).on( 'hashchange', onHashChange );

	// Check hash on load
	onHashChange();

}() );
