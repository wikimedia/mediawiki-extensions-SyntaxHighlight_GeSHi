$( function () {

	var $lastLine;

	function onHashChange() {
		var id = location.hash.slice( 1 ),
			// Don't assume location.hash will be parseable as an ID (T271572)
			// and avoid warning when id is empty (T272844)
			$line = id ? $( document.getElementById( id ) || [] ) : $( [] );

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
