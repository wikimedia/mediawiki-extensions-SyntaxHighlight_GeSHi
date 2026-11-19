'use strict';

const { defineConfig, globalIgnores } = require( 'eslint/config' );
const wikimedia = require( 'eslint-config-wikimedia/flat' );

// Directories with their own config, which other configs don't apply to.
// These were directories with `"root": true` in .eslintrc.json.
const rootDirs = {
	modules: [ 'modules/**' ]
};

module.exports = defineConfig( [
	globalIgnores( [
		// Build
		'vendor/'
	] ),

	// Gruntfile.js, etc.
	{
		ignores: Object.values( rootDirs ).flat(),
		extends: [ wikimedia( 'server' ) ]
	},

	// Code which runs in MediaWiki
	{
		files: rootDirs.modules,
		extends: [ wikimedia( 'client', 'jquery', 'mediawiki' ) ],
		rules: {
			'max-len': 'off'
		}
	},
	{
		files: [ 'modules/ve-syntaxhighlight/**' ],
		languageOptions: {
			globals: {
				ve: 'writable'
			}
		}
	}
] );
