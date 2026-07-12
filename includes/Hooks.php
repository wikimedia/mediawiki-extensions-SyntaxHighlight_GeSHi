<?php

namespace MediaWiki\SyntaxHighlight;

use MediaWiki\Parser\Hook\ParserFirstCallInitHook;
use MediaWiki\Parser\Parser;
use MediaWiki\Specials\Hook\SoftwareInfoHook;

class Hooks implements
	ParserFirstCallInitHook,
	SoftwareInfoHook
{
	public function __construct(
		private readonly SyntaxHighlight $syntaxHighlight,
	) {
	}

	/**
	 * Register parser hook
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'source', $this->syntaxHighlight->parserHookSource( ... ) );
		$parser->setHook( 'syntaxhighlight', $this->syntaxHighlight->parserHook( ... ) );
	}

	/**
	 * Hook to add Pygments version to Special:Version
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SoftwareInfo
	 * @param array &$software
	 */
	public function onSoftwareInfo( &$software ) {
		try {
			$software['[https://pygments.org/ Pygments]'] = Pygmentize::getVersion();
		} catch ( PygmentsException ) {
			// pass
		}
	}
}
