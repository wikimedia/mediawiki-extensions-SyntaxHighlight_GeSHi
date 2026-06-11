$( () => {
	function addLink( element, title ) {
		const link = document.createElement( 'a' );
		link.href = title.getUrl();
		link.title = title.toText();
		// put text node from element inside link
		const firstChild = element.firstChild;
		if ( !( firstChild instanceof Text ) ) {
			throw new TypeError( 'Expected Text object' );
		}
		link.appendChild( firstChild );
		element.appendChild( link ); // put link inside syntax-highlighted string
	}

	// List of functions whose parameters should be linked if they meet the given condition
	const parametersToLink = {
		require: ( title ) => title.getNamespaceId() === 828,
		'mw.loadData': ( title ) => title.getNamespaceId() === 828,
		'mw.loadJsonData': () => true
	};

	mw.hook( 'wikipage.content' ).add( ( $content ) => {

		// s1 is the class applied by Pygments to single-quoted strings
		// s2 is the class applied by Pygments to double-quoted strings
		const stringNodes = $content.find( '.s1' ).get()
			.concat( $content.find( '.s2' ).get() );

		stringNodes.forEach( ( node ) => {
			let nextNode = node.nextElementSibling,
				prevNode = node.previousElementSibling;

			// Skip over whitespace
			if ( nextNode && nextNode.classList.contains( 'w' ) ) {
				nextNode = nextNode.nextElementSibling;
			}
			if ( prevNode && prevNode.classList.contains( 'w' ) ) {
				prevNode = prevNode.previousElementSibling;
			}

			if ( !nextNode || !nextNode.firstChild || !nextNode.firstChild.nodeValue ||
				!nextNode.firstChild.nodeValue.startsWith( ')' ) ) {
				return;
			}
			if ( !prevNode || !prevNode.firstChild || prevNode.firstChild.nodeValue !== '(' ) {
				return;
			}
			Object.keys( parametersToLink ).forEach( ( invocation ) => {
				const parts = invocation.split( '.' );
				let partIdx = parts.length - 1;
				let curNode = prevNode.previousElementSibling;
				while ( partIdx >= 0 ) {
					if ( !curNode || curNode.firstChild.nodeValue !== parts[ partIdx ] ) {
						return;
					}
					if ( partIdx === 0 ) {
						break;
					}
					const prev = curNode.previousElementSibling;
					if ( !prev || prev.firstChild.nodeValue !== '.' ) {
						return;
					}
					curNode = prev.previousElementSibling;
					partIdx--;
				}
				const page = node.firstChild.nodeValue.slice( 1, -1 );
				const condition = parametersToLink[ invocation ];
				const title = mw.Title.newFromText( page );
				if ( title && condition( title ) ) {
					addLink( node, title );
				}
			} );
		} );

	} );
} );
