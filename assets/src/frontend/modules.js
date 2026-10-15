/**
 * Swiper modules. The common ones ship in the main bundle; effects and rarer features load on demand,
 * so a simple fade slider downloads only what it uses.
 */
import {
	A11y,
	Autoplay,
	Keyboard,
	Navigation,
	Pagination,
} from 'swiper/modules';

const bundled = {
	a11y: A11y,
	autoplay: Autoplay,
	keyboard: Keyboard,
	navigation: Navigation,
	pagination: Pagination,
};

const lazy = {
	'effect-fade': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-effect-fade" */ 'swiper-modules/effect-fade.mjs'
			),
			import(
				/* webpackChunkName: "sc-effect-fade" */ 'swiper/css/effect-fade'
			),
		] ),
	'effect-coverflow': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-effect-coverflow" */ 'swiper-modules/effect-coverflow.mjs'
			),
			import(
				/* webpackChunkName: "sc-effect-coverflow" */ 'swiper/css/effect-coverflow'
			),
		] ),
	'effect-cube': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-effect-cube" */ 'swiper-modules/effect-cube.mjs'
			),
			import(
				/* webpackChunkName: "sc-effect-cube" */ 'swiper/css/effect-cube'
			),
		] ),
	'effect-flip': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-effect-flip" */ 'swiper-modules/effect-flip.mjs'
			),
			import(
				/* webpackChunkName: "sc-effect-flip" */ 'swiper/css/effect-flip'
			),
		] ),
	'effect-cards': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-effect-cards" */ 'swiper-modules/effect-cards.mjs'
			),
			import(
				/* webpackChunkName: "sc-effect-cards" */ 'swiper/css/effect-cards'
			),
		] ),
	'effect-creative': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-effect-creative" */ 'swiper-modules/effect-creative.mjs'
			),
			import(
				/* webpackChunkName: "sc-effect-creative" */ 'swiper/css/effect-creative'
			),
		] ),
	thumbs: () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-thumbs" */ 'swiper-modules/thumbs.mjs'
			),
			import( /* webpackChunkName: "sc-thumbs" */ 'swiper/css/thumbs' ),
		] ),
	grid: () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-grid" */ 'swiper-modules/grid.mjs'
			),
			import( /* webpackChunkName: "sc-grid" */ 'swiper/css/grid' ),
		] ),
	'free-mode': () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-free-mode" */ 'swiper-modules/free-mode.mjs'
			),
			import(
				/* webpackChunkName: "sc-free-mode" */ 'swiper/css/free-mode'
			),
		] ),
	mousewheel: () =>
		Promise.all( [
			import(
				/* webpackChunkName: "sc-mousewheel" */ 'swiper-modules/mousewheel.mjs'
			),
		] ),
};

/**
 * Resolve module names to Swiper module functions, loading lazy ones.
 *
 * @param {string[]} names Module names from the server (e.g. "effect-fade").
 * @return {Promise<Function[]>} Swiper modules.
 */
export async function loadModules( names ) {
	const result = [];
	const pending = [];
	names.forEach( ( name ) => {
		if ( bundled[ name ] ) {
			result.push( bundled[ name ] );
		} else if ( lazy[ name ] ) {
			pending.push( lazy[ name ]().then( ( [ mod ] ) => mod.default ) );
		}
	} );
	return result.concat( await Promise.all( pending ) );
}
