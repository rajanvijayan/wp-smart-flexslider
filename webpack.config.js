/**
 * @wordpress/scripts webpack config, plus an alias so the front-end runtime can load individual Swiper
 * modules on demand (Swiper's package exports only the combined `swiper/modules` entry).
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	resolve: {
		...defaultConfig.resolve,
		alias: {
			...( defaultConfig.resolve && defaultConfig.resolve.alias ),
			'swiper-modules': path.resolve( __dirname, 'node_modules/swiper/modules' ),
		},
	},
};
