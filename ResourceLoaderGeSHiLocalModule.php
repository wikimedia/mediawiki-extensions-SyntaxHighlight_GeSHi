<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Custom ResourceLoader module that loads a Geshi.css per-wiki.
 */
class ResourceLoaderGeSHiLocalModule extends ResourceLoaderWikiModule {

	/** @var string Position on the page to load this module at */
	protected $position = 'bottom';

	public function __construct( $options ) {
		foreach ( $options as $member => $option ) {
			switch ( $member ) {
				case 'position':
					$this->isPositionDefined = true;
					$this->{$member} = (string)$option;
					break;
			}
		}
	}

	/**
	 * @param $context ResourceLoaderContext
	 * @return array
	 */
	protected function getPages( ResourceLoaderContext $context ) {
		global $wgUseSiteCss;
		if ( !$wgUseSiteCss ) {
			return array();
		}

		return array(
			'MediaWiki:Geshi.css' => array( 'type' => 'style' ),
		);
	}

	public function getPosition() {
		return $this->position;
	}
}
