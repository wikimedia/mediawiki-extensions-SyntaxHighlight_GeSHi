$( function () {

	var $lastLine;

	function onHashChange() {
		// Don't assume location.hash will be parseable as an ID (T271572)
		var $line = $( document.getElementById( location.hash.slice( 1 ) ) || [] );

		if ( !$line.closest( '.mw-highlight' ).length ) {
			// Matched ID wasn't in a highlight block
			$line = $( [] );
		}

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
