<?php

class ResourceLoaderGeSHiModule extends ResourceLoaderModule {

	/**
	 * @var string
	 */
	protected $lang;

	/**
	 * @param array $info Module definition.
	 */
	function __construct( $info ) {
		$this->lang = $info['lang'];
	}

	/**
	 * @param ResourceLoaderContext $context
	 * @return array
	 */
	public function getStyles( ResourceLoaderContext $context ) {
		$geshi = SyntaxHighlight_GeSHi::prepare( '', $this->lang );
		if ( !$geshi->error ) {
			$css = SyntaxHighlight_GeSHi::getCSS( $geshi );
		} else {
			$css = ResourceLoader::makeComment( $geshi->error() );
		}

		return array( 'all' => $css );
	}

	/**
	 * @param ResourceLoaderContext $context
	 * @return int
	 */
	public function getModifiedTime( ResourceLoaderContext $context ) {
		return max( array(
			$this->getDefinitionMtime( $context ),
			self::safeFilemtime( __FILE__ ),
			self::safeFilemtime( __DIR__ . '/SyntaxHighlight_GeSHi.class.php' ),
			self::safeFilemtime( __DIR__ . '/geshi/geshi.php' ),
			self::safeFilemtime( GESHI_LANG_ROOT . "/{$this->lang}.php" ),
		) );
	}

	/**
	 * @param $context ResourceLoaderContext
	 * @return array
	 */
	public function getDefinitionSummary( ResourceLoaderContext $context ) {
		return array(
			'class' => get_class( $this ),
			'lang' => $this->lang,
		);
	}
}
