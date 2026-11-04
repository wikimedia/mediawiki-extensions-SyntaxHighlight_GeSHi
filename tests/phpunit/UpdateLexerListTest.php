<?php

use MediaWiki\SyntaxHighlight\Maintenance\UpdateLexerList;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * @covers \MediaWiki\SyntaxHighlight\Maintenance\UpdateLexerList
 */
class UpdateLexerListTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return UpdateLexerList::class;
	}

	public function testExecute() {
		$file = __DIR__ . '/../../SyntaxHighlight.lexers.php';
		$before = file_get_contents( $file );

		$this->expectOutputRegex( '/Updated language list/' );
		$this->maintenance->execute();

		$after = file_get_contents( $file );
		$afterValue = require $file;
		$this->assertIsArray( $afterValue, 'Valid file generated' );
		$this->assertArrayHasKey( 'php', $afterValue, 'Valid file generated' );
		$this->assertArrayHasKey( 'javascript', $afterValue, 'Valid generated' );
		$this->assertSame(
			$after,
			$before,
			'Remember to rebuild the lexers list when updating Pygments'
		);
	}
}
