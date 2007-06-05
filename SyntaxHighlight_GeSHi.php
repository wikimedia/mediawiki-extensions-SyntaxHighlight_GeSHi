<?php
/**
 * Syntax highlighting extension for MediaWiki 1.5 using GeSHi
 * Copyright (C) 2005 Brion Vibber <brion@pobox.com>
 * http://www.mediawiki.org/
 *
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
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * @addtogroup Extensions
 * @author Brion Vibber
 *
 * This extension wraps the GeSHi highlighter: http://qbnz.com/highlighter/
 *
 * Unlike the older GeSHi MediaWiki extension floating around, this makes
 * use of the new extension parameter support in MediaWiki 1.5 so it only
 * has to register one tag, <source>.
 *
 * A language is specified like: <source lang="c">void main() {}</source>
 * If you forget, or give an unsupported value, the extension spits out
 * some help text and a list of all supported languages.
 *
 * The extension has been tested with GeSHi 1.0.7 and MediaWiki 1.5 CVS
 * as of 2005-06-22.
 */

if( !defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionFunctions[] = 'syntaxHighlightSetup';
$wgExtensionCredits['parserhook']['SyntaxHighlight_GeSHi'] = array(
	'name'          => 'SyntaxHighlight',
	'author'        => 'Brion Vibber',
	'description'   => 'Provides syntax highlighting using [http://qbnz.com/highlighter/ GeSHi Highlighter]',
	'url'           => 'http://www.mediawiki.org/wiki/Extension:SyntaxHighlight_GeSHi',
);
$wgHooks['LoadAllMessages'][] = 'syntaxHighlightLoadMessages';

function syntaxHighlightSetup() {
	global $wgParser;
	$wgParser->setHook( 'source', 'syntaxHighlightHook' );
}

function syntaxHighlightLoadMessages() {
	static $loaded = false;
	if ( $loaded ) {
		return true;
	}
	global $wgMessageCache;
	require_once( dirname( __FILE__ ) . '/SyntaxHighlight_GeSHi.i18n.php' );
	foreach( efSyntaxHighlight_GeSHiMessages() as $lang => $messages )
		$wgMessageCache->addMessages( $messages, $lang );
	return true;
}

function syntaxHighlightHook( $text, $params = array(), $parser ) {
	if ( !class_exists( 'GeSHi' ) ) {
		require( 'geshi/geshi.php' );
	}
	syntaxHighlightLoadMessages();
	return isset( $params['lang'] )
		? syntaxHighlightFormat( trim( $text ), $params, $parser )
		: syntaxHighlightHelp();
}

function syntaxHighlightFormat( $text, $params, $parser ) {
	$lang = $params['lang'];
	if ( !preg_match( '/^[A-Za-z_0-9-]*$/', $lang ) ) {
		return syntaxHighlightHelp( wfMsgHtml( 'syntaxhighlight-err-language' ) );
	}

	$geshi = new GeSHi( $text, $lang );
	if ( $geshi->error == GESHI_ERROR_NO_SUCH_LANG ) {
		return syntaxHighlightHelp( wfMsgHtml( 'syntaxhighlight-err-language' ) );
	}

	$geshi->set_encoding( 'UTF-8' );
	$geshi->enable_classes();
	$geshi->set_overall_class( "source source-$lang" );
	$geshi->enable_keyword_links(false);

	if ( isset( $params['enclose'] ) && $params['enclose'] == 'div' ) {
		$enclose = GESHI_HEADER_DIV;
	} else {
		$enclose = GESHI_HEADER_PRE;
	}

	if ( isset( $params['line'] ) ) {
		// Pre mode with line numbers generates invalid HTML, need div mode
		// http://sourceforge.net/tracker/index.php?func=detail&aid=1201963&group_id=114997&atid=670231
		$enclose = GESHI_HEADER_DIV;
		$geshi->enable_line_numbers( GESHI_FANCY_LINE_NUMBERS );
	}
	if ( isset( $params['start'] ) ) {
		$geshi->start_line_numbers_at( $params['start'] );
	}
	$geshi->set_header_type( $enclose );

	if ( isset( $params['strict'] ) ) {
		$geshi->enable_strict_mode();
	}

	$out   = $geshi->parse_code();
	$error = $geshi->error();

	if ( $error ) {
		return syntaxHighlightHelp( $error );
	} else {
		// Armour for doBlockLevels
		if ( $enclose == GESHI_HEADER_DIV ) {
			$out = str_replace( "\n", '', $out );
		}

		// Per-language class for stylesheet
		$geshi->set_overall_class( "source-$lang" );

		// Enable [[MediaWiki:Geshi.css]] (bug #9951)
		$sitecss = '';
		global $wgUseSiteCss;
		if( $wgUseSiteCss ) {
			global $wgUser, $wgSquidMaxage ;
			$query = "usemsgcache=yes&action=raw&ctype=text/css&smaxage=$wgSquidMaxage";
			$sk = $wgUser->getSkin();
			$sitecss =
				"<style type=\"text/css\">/*<![CDATA[*/\n" .
				'@import "' . $sk->makeNSUrl( 'Geshi.css', $query, NS_MEDIAWIKI ) . "\";\n" .
				"/*]]>*/</style>\n" ;
		}

		$parser->mOutput->addHeadItem(
			"<style type=\"text/css\">/*<![CDATA[*/\n" .
			".source-$lang {line-height: normal;}\n" .
			".source-$lang li {line-height: normal;}\n" .
			$geshi->get_stylesheet( false ) .
			"/*]]>*/</style>\n$sitecss",
			"source-$lang" );
		return $out;
	}
}

/**
 * Return a syntax help message
 * @param string $error HTML error message
 */
function syntaxHighlightHelp( $error = false ) {
	return syntaxHighlightError(
		( $error ? "<p>$error</p>" : '' ) .
		'<p>' . wfMsg( 'syntaxhighlight-specify' ) . ' ' .
		'<samp>&lt;source lang=&quot;html&quot;&gt;...&lt;/source&gt;</samp></p>' .
		'<p>' . wfMsg( 'syntaxhighlight-supported' ) . '</p>' .
		syntaxHighlightFormatList( syntaxHighlightLanguageList() ) );
}

/**
 * Put a red-bordered div around an HTML message
 * @param string $contents HTML error message
 * @return HTML
 */
function syntaxHighlightError( $contents ) {
	return "<div style=\"border:solid red 1px; padding:.5em;\">$contents</div>";
}

function syntaxHighlightFormatList( $list ) {
	return empty( $list )
		? wfMsg( 'syntaxhighlight-err-loading' )
		: '<p style="padding:0em 1em;">' .
			implode( ', ', array_map( 'syntaxHighlightListItem', $list ) ) .
			'</p>';
}

function syntaxHighlightListItem( $item ) {
	return "<samp>" . htmlspecialchars( $item ) . "</samp>";
}

function syntaxHighlightLanguageList() {
	$langs = array();
	$langroot = @opendir( GESHI_LANG_ROOT );
	if( $langroot ) {
		while( $item = readdir( $langroot ) ) {
			if( preg_match( '/^(.*)\\.php$/', $item, $matches ) ) {
				$langs[] = $matches[1];
			}
		}
		closedir( $langroot );
	}
	sort( $langs );
	return $langs;
}

?>
