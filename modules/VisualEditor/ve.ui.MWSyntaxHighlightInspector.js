/*!
 * VisualEditor UserInterface MWSyntaxHighlightInspector class.
 *
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * MediaWiki syntax highlight inspector.
 *
 * @class
 * @extends ve.ui.MWLiveExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWSyntaxHighlightInspector = function VeUiMWSyntaxHighlightInspector() {
	// Parent constructor
	ve.ui.MWSyntaxHighlightInspector.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWSyntaxHighlightInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */

ve.ui.MWSyntaxHighlightInspector.static.name = 'syntaxhighlight';

ve.ui.MWSyntaxHighlightInspector.static.icon = 'alienextension';

ve.ui.MWSyntaxHighlightInspector.static.size = 'large';

ve.ui.MWSyntaxHighlightInspector.static.title = OO.ui.deferMsg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-title' );

ve.ui.MWSyntaxHighlightInspector.static.modelClasses = [ ve.dm.MWSyntaxHighlightNode ];

ve.ui.MWSyntaxHighlightInspector.static.dir = 'ltr';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWSyntaxHighlightInspector.super.prototype.initialize.call( this );

	this.language = new OO.ui.TextInputWidget( {
		validate: function ( value ) {
			return ve.dm.MWSyntaxHighlightNode.static.isLanguageSupported( value );
		}
	} );

	var languageField = new OO.ui.FieldLayout( this.language, {
			align: 'top',
			label: ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-language' )
		} ),
		codeField = new OO.ui.FieldLayout( this.input, {
			align: 'top',
			label: ve.msg( 'syntaxhighlight-visualeditor-mwsyntaxhighlightinspector-code' )
		} );

	// Initialization
	this.$content.addClass( 've-ui-mwSyntaxHighlightInspector-content' );
	this.form.$element.prepend( languageField.$element, codeField.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWSyntaxHighlightInspector.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			if ( this.language.getValue() ) {
				this.input.focus();
			} else {
				this.language.focus();
			}
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWSyntaxHighlightInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var language = this.selectedNode.getAttribute( 'mw' ).attrs.lang || '';
			this.language.setValue( language );
			if ( !language ) {
				this.language.setValidityFlag( true );
			}
			this.language.on( 'change', this.onChangeHandler );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWSyntaxHighlightInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.language.off( 'change', this.onChangeHandler );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWSyntaxHighlightInspector.prototype.updateMwData = function ( mwData ) {
	// Parent method
	ve.ui.MWSyntaxHighlightInspector.super.prototype.updateMwData.call( this, mwData );

	mwData.attrs.lang = this.language.getValue();
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWSyntaxHighlightInspector );
