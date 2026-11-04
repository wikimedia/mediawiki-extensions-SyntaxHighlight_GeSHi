<?php

use MediaWiki\SyntaxHighlight\Maintenance\UpdateCSS;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * @covers \MediaWiki\SyntaxHighlight\Maintenance\UpdateCSS
 */
class UpdateCSSTest extends MaintenanceBaseTestCase {

	protected function getMaintenanceClass() {
		return UpdateCSS::class;
	}

	public function testExecute() {
		$file = __DIR__ . '/../../modules/pygments.generated.css';
		$before = file_get_contents( $file );

		$this->expectOutputRegex( '/CSS written/' );
		$this->maintenance->execute();

		$after = file_get_contents( $file );
		$this->assertStringContainsString( '.mw-highlight', $after, 'Valid file generated' );
		$this->assertSame(
			$after,
			$before,
			'Remember to rebuild the stylesheet when updating Pygments'
		);
	}
}
