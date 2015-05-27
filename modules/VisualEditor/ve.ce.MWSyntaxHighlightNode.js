/*!
 * VisualEditor ContentEditable MWSyntaxHighlightNode class.
 *
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * ContentEditable MediaWiki syntax highlight node.
 *
 * @class
 * @extends ve.ce.MWBlockExtensionNode
 *
 * @constructor
 * @param {ve.dm.MWSyntaxHighlightNode} model Model to observe
 * @param {Object} [config] Configuration options
 */
ve.ce.MWSyntaxHighlightNode = function VeCeMWSyntaxHighlightNode() {
	// Parent constructor
	ve.ce.MWSyntaxHighlightNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ce.MWSyntaxHighlightNode, ve.ce.MWBlockExtensionNode );

/* Static Properties */

ve.ce.MWSyntaxHighlightNode.static.name = 'mwSyntaxHighlight';

ve.ce.MWSyntaxHighlightNode.static.primaryCommandName = 'syntaxhighlight';

/* Methods */

/** */
ve.ce.MWSyntaxHighlightNode.prototype.generateContents = function () {
	if ( !this.getModel().isLanguageSupported() ) {
		return $.Deferred().reject().promise();
	}
	// Parent method
	return ve.ce.MWSyntaxHighlightNode.super.prototype.generateContents.apply( this, arguments );
};

/** */
ve.ce.MWSyntaxHighlightNode.prototype.onSetup = function () {
	// Parent method
	ve.ce.MWSyntaxHighlightNode.super.prototype.onSetup.call( this );

	// DOM changes
	this.$element.addClass( 've-ce-mwSyntaxHighlightNode' );
};

/* Registration */

ve.ce.nodeFactory.register( ve.ce.MWSyntaxHighlightNode );
