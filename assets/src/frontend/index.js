/**
 * SlideCraft front-end runtime. Starts every slider on the page as it nears the viewport, and any
 * slider added later (block editor, live preview, AJAX-loaded content).
 */
import 'swiper/css';
import './frontend.scss';
import { mountSlider, unmountSlider } from './slider';

const SELECTOR = '.slidecraft[data-slidecraft]';

const observer =
	'IntersectionObserver' in window
		? new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.isIntersecting ) {
							observer.unobserve( entry.target );
							mountSlider( entry.target );
						}
					} );
				},
				{ rootMargin: '300px 0px' }
		  )
		: null;

/**
 * Start sliders inside a container.
 *
 * @param {Element|Document} scope Container (default: document).
 * @param {boolean}          now   Start immediately instead of when visible.
 */
function init( scope = document, now = false ) {
	const nodes =
		scope.matches && scope.matches( SELECTOR )
			? [ scope ]
			: scope.querySelectorAll( SELECTOR );
	nodes.forEach( ( node ) => {
		if ( now || ! observer ) {
			mountSlider( node );
		} else {
			observer.observe( node );
		}
	} );
}

function start() {
	init( document );
	new MutationObserver( ( mutations ) => {
		mutations.forEach( ( mutation ) =>
			mutation.addedNodes.forEach( ( node ) => {
				if ( node.nodeType === 1 ) {
					init( node );
				}
			} )
		);
	} ).observe( document.body, { childList: true, subtree: true } );
}

window.SlideCraft = { init, mount: mountSlider, unmount: unmountSlider };

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', start );
} else {
	start();
}
