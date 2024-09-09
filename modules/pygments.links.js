/**
 * Adapted from https://en.wiktionary.org/wiki/MediaWiki:Gadget-CodeLinks.js
 * Original authors: Kephir, Erutuon
 * License: CC-BY-SA 4.0
 */

$( () => {
	// by John Gruber, from https://daringfireball.net/2010/07/improved_regex_for_matching_urls
	const URLRegExp = /\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()[\]{};:'".,<>?«»“”‘’]))/i;

	function processComment( node ) {
		let wikilinkMatch, templateMatch, URLMatch;
		const textNode = node.firstChild; // always a text node.

		while (
			( wikilinkMatch = /\[\[([^|{}[\]\n]+)?(?:\|.*?)?]]/.exec( textNode.data ) ) ||
			( templateMatch = /\{\{([^|{}[\]\n#]+)(?=\||}})/i.exec( textNode.data ) ) ||
			( URLMatch = URLRegExp.exec( textNode.data ) )
		) {
			const link = document.createElement( 'a' );
			let start = ( wikilinkMatch || templateMatch || URLMatch ).index;
			let linkText;
			link.classList.add( 'code-link' );

			if ( URLMatch ) {
				const URL = URLMatch[ 0 ];
				link.href = URL;
				linkText = URL;
			} else {
				let fullPageName;
				if ( wikilinkMatch ) {
					linkText = wikilinkMatch[ 0 ];
					fullPageName = wikilinkMatch[ 1 ];
				} else if ( templateMatch ) {
					const pageName = templateMatch[ 1 ];
					linkText = pageName;
					fullPageName = mw.config.get( 'wgFormattedNamespaces' )[ 10 ] + ':' + pageName;
					link.title = fullPageName;
					start += 2; // opening braces "{{"
				}
				link.href = mw.util.getUrl( fullPageName );
			}

			const beforeLink = textNode.data.slice( 0, Math.max( 0, start ) ),
				afterLink = textNode.data.slice( Math.max( 0, start + linkText.length ) );

			textNode.data = afterLink;
			link.appendChild( document.createTextNode( linkText ) );
			node.insertBefore( link, textNode );
			node.insertBefore( document.createTextNode( beforeLink ), link );
		}
	}

	const commentClasses = [ 'c', 'c1', 'cm' ];
	Array.from( document.getElementsByClassName( 'mw-highlight' ) ).forEach( ( codeBlock ) => {
		commentClasses.forEach( ( commentClass ) => {
			Array.from( codeBlock.getElementsByClassName( commentClass ) ).forEach( processComment );
		} );
	} );

} );
