/*!
 * VisualEditor DataModel MWSyntaxHighlightNode class.
 *
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * DataModel MediaWiki syntax highlight node.
 *
 * @class
 * @extends ve.dm.MWBlockExtensionNode
 *
 * @constructor
 * @param {Object} [element]
 */
ve.dm.MWSyntaxHighlightNode = function VeDmMWSyntaxHighlightNode() {
	// Parent constructor
	ve.dm.MWSyntaxHighlightNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWSyntaxHighlightNode, ve.dm.MWBlockExtensionNode );

/* Static members */

ve.dm.MWSyntaxHighlightNode.static.name = 'mwSyntaxHighlight';

ve.dm.MWSyntaxHighlightNode.static.tagName = 'div';

ve.dm.MWSyntaxHighlightNode.static.extensionName = 'syntaxhighlight';

ve.dm.MWSyntaxHighlightNode.static.getMatchRdfaTypes = function () {
	return [ 'mw:Extension/syntaxhighlight', 'mw:Extension/source' ];
};

/* Static methods */

( function () {
	var supportedLanguages = [];

	/**
	 * Register supported languages.
	 *
	 * @param {Array} languages
	 */
	ve.dm.MWSyntaxHighlightNode.static.addLanguages = function ( languages ) {
		ve.batchPush( supportedLanguages, languages );
	};

	/**
	 * Check if a language is supported
	 *
	 * @param {string} language Language name
	 * @return {boolean} The language is supported
	 */
	ve.dm.MWSyntaxHighlightNode.static.isLanguageSupported = function ( language ) {
		return supportedLanguages.indexOf( language ) !== -1;
	};

	/**
	 * Get an array of all languages
	 *
	 * @return {Array} All currently supported languages
	 */
	ve.dm.MWSyntaxHighlightNode.static.getLanguages = function () {
		return supportedLanguages.slice();
	};
}() );

/* Methods */

/**
 * Check if the node's current language is supported
 *
 * @return {boolean} The language is supported
 */
ve.dm.MWSyntaxHighlightNode.prototype.isLanguageSupported = function () {
	return this.constructor.static.isLanguageSupported( this.getLanguage() );
};

ve.dm.MWSyntaxHighlightNode.prototype.getLanguage = function () {
	return this.getAttribute( 'mw' ).attrs.lang;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWSyntaxHighlightNode );
