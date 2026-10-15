module.exports = {
	root: true,
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: { browser: true },
	overrides: [
		{
			files: [ '**/test/**/*.js', '**/*.test.js' ],
			extends: [ 'plugin:@wordpress/eslint-plugin/test-unit' ],
		},
	],
	rules: {
		// Swiper only exposes files through package "exports" (unreadable by the resolver); @wordpress/* are WordPress externals.
		'import/no-unresolved': [ 'error', { ignore: [ '^swiper', '^@wordpress/' ] } ],
	},
};
