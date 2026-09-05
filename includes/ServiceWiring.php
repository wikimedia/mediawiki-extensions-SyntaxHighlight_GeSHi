<?php

namespace MediaWiki\SyntaxHighlight;

use MediaWiki\MediaWikiServices;

/** @phpcs-require-sorted-array */
return [
	'SyntaxHighlight.Pygmentize' => static function ( MediaWikiServices $services ): Pygmentize {
		return new Pygmentize(
			$services->getMainConfig(),
			$services->getLocalServerObjectCache(),
			$services->getMainWANObjectCache(),
			$services->getShellCommandFactory(),
			$services->getStatsFactory()
		);
	},
	'SyntaxHighlight.SyntaxHighlight' => static function ( MediaWikiServices $services ): SyntaxHighlight {
		return new SyntaxHighlight(
			$services->getMainConfig(),
			$services->getMainWANObjectCache(),
			$services->getService( 'SyntaxHighlight.Pygmentize' )
		);
	},
];
