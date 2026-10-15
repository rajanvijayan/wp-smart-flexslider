/**
 * Live preview frame for the builder. The builder posts the unsaved configuration here; this page
 * renders it with the real front-end renderer and runtime, and reports clicks and layer drags back.
 *
 * Messages in:  slidecraft:render { config, title, id, slideId }, slidecraft:goto { slideId },
 *               slidecraft:select { layerId }
 * Messages out: slidecraft:ready, slidecraft:rendered { height }, slidecraft:height { height },
 *               slidecraft:slide { slideId }, slidecraft:select { slideId, layerId },
 *               slidecraft:move { slideId, layerId, x, y }, slidecraft:error { message }
 */
import apiFetch from '@wordpress/api-fetch';

const settings = window.slideCraftPreview || {};
const parentOrigin = ( () => {
	try {
		return new URL( settings.origin ).origin;
	} catch ( e ) {
		return window.location.origin;
	}
} )();
const root = document.getElementById( 'slidecraft-preview-root' );

let requestId = 0;
let navigating = false;
let current = null;
let selected = '';

function send( type, data = {} ) {
	if ( window.parent !== window ) {
		window.parent.postMessage(
			{ source: 'slidecraft-preview', type, ...data },
			parentOrigin
		);
	}
}

function reportHeight() {
	send( 'slidecraft:height', {
		height: Math.ceil( document.documentElement.scrollHeight ),
	} );
}

function markSelected() {
	root.querySelectorAll( '.is-selected' ).forEach( ( node ) =>
		node.classList.remove( 'is-selected' )
	);
	if ( selected ) {
		root.querySelectorAll(
			`[data-layer-id="${ CSS.escape( selected ) }"]`
		).forEach( ( node ) => node.classList.add( 'is-selected' ) );
	}
}

/**
 * Render a configuration. Only the newest request wins, so fast typing never shows stale output.
 *
 * @param {Object} data Message data.
 */
async function render( data ) {
	const id = ++requestId;
	let response;
	try {
		response = await apiFetch( {
			path: '/slidecraft/v1/preview',
			method: 'POST',
			data: {
				config: data.config,
				title: data.title || '',
				id: data.id || 0,
			},
		} );
	} catch ( error ) {
		send( 'slidecraft:error', {
			message: error.message || 'Preview failed',
		} );
		return;
	}
	if ( id !== requestId ) {
		return;
	}

	const old = root.querySelector( '.slidecraft' );
	if ( old && window.SlideCraft ) {
		window.SlideCraft.unmount( old );
	}
	root.innerHTML =
		response.html || '<div class="slidecraft-preview-empty"></div>';
	current = root.querySelector( '.slidecraft' );
	if ( ! current || ! window.SlideCraft ) {
		send( 'slidecraft:rendered', {
			height: document.documentElement.scrollHeight,
		} );
		return;
	}

	const swiper = await window.SlideCraft.mount( current );
	if ( swiper ) {
		if ( data.slideId ) {
			goTo( data.slideId );
		}
		// Report slides the visitor moves to (not jumps made by goTo), once the move has settled.
		swiper.on( 'slideChangeTransitionEnd', ( s ) => {
			const active = s.slides[ s.activeIndex ];
			if ( active && ! navigating ) {
				send( 'slidecraft:slide', { slideId: active.dataset.slideId } );
			}
		} );
	}
	markSelected();
	send( 'slidecraft:rendered', {
		height: Math.ceil( document.documentElement.scrollHeight ),
	} );
}

/**
 * Show a slide by its ID. Slides generated from posts carry the ID of their template slide
 * followed by "-{post ID}"; the first of them is shown.
 *
 * @param {string} slideId Slide ID.
 */
function goTo( slideId ) {
	const swiper = current && current.slidecraft;
	if ( ! swiper || ! slideId ) {
		return;
	}
	const matches = ( el ) =>
		el.dataset.slideId === slideId ||
		el.dataset.slideId.startsWith( slideId + '-' );
	const active = swiper.slides[ swiper.activeIndex ];
	if ( active && matches( active ) ) {
		return;
	}
	const index = swiper.slides.findIndex( matches );
	if ( index < 0 ) {
		return;
	}
	navigating = true;
	if ( swiper.params.loop ) {
		swiper.slideToLoop(
			Number( swiper.slides[ index ].dataset.swiperSlideIndex ) || 0,
			0
		);
	} else {
		swiper.slideTo( index, 0 );
	}
	// Transition events from this jump fire synchronously or on the next frame.
	window.requestAnimationFrame( () =>
		window.requestAnimationFrame( () => ( navigating = false ) )
	);
}

window.addEventListener( 'message', ( event ) => {
	if (
		event.origin !== parentOrigin ||
		! event.data ||
		'slidecraft-builder' !== event.data.source
	) {
		return;
	}
	const data = event.data;
	switch ( data.type ) {
		case 'slidecraft:render':
			render( data );
			break;
		case 'slidecraft:goto':
			goTo( data.slideId );
			break;
		case 'slidecraft:select':
			selected = data.layerId || '';
			markSelected();
			break;
	}
} );

/* Clicks select layers and slides; links never navigate away from the preview. */

root.addEventListener(
	'click',
	( event ) => {
		const link = event.target.closest( 'a' );
		if ( link ) {
			event.preventDefault();
		}
		if ( dragged ) {
			dragged = false;
			return;
		}
		const layer = event.target.closest( '[data-layer-id]' );
		const slide = event.target.closest( '[data-slide-id]' );
		if (
			slide &&
			! event.target.closest(
				'.slidecraft__arrow, .slidecraft__pagination, .slidecraft__pause'
			)
		) {
			selected = layer ? layer.dataset.layerId : '';
			markSelected();
			send( 'slidecraft:select', {
				slideId: slide.dataset.slideId,
				layerId: selected,
			} );
		}
	},
	true
);

/* Drag a layer to move it. Offsets are a percentage of its group (the slide's content box). */

let drag = null;
let dragged = false;

root.addEventListener(
	'pointerdown',
	( event ) => {
		const layer = event.target.closest( '[data-layer-id]' );
		if ( ! layer || event.button !== 0 ) {
			return;
		}
		const group = layer.closest( '.slidecraft-group' );
		const slide = layer.closest( '[data-slide-id]' );
		if ( ! group || ! slide ) {
			return;
		}
		const style = getComputedStyle( layer );
		const box = group.getBoundingClientRect();
		drag = {
			layer,
			slideId: slide.dataset.slideId,
			startX: event.clientX,
			startY: event.clientY,
			baseX:
				( parseFloat( style.getPropertyValue( '--sc-x' ) ) || 0 ) / 100,
			baseY:
				( parseFloat( style.getPropertyValue( '--sc-y' ) ) || 0 ) / 100,
			width: box.width || 1,
			height: box.height || 1,
			x: null,
			y: null,
		};
		// Stop Swiper from treating the drag as a swipe.
		event.stopPropagation();
		layer.setPointerCapture( event.pointerId );
	},
	true
);

root.addEventListener(
	'pointermove',
	( event ) => {
		if ( ! drag ) {
			return;
		}
		const dx = event.clientX - drag.startX;
		const dy = event.clientY - drag.startY;
		if ( null === drag.x && Math.hypot( dx, dy ) < 4 ) {
			return;
		}
		event.stopPropagation();
		const clamp = ( v ) =>
			Math.max( -50, Math.min( 50, Math.round( v * 10 ) / 10 ) );
		drag.x = clamp( ( drag.baseX + dx / drag.width ) * 100 );
		drag.y = clamp( ( drag.baseY + dy / drag.height ) * 100 );
		drag.layer.style.setProperty( '--sc-x', drag.x + '%' );
		drag.layer.style.setProperty( '--sc-y', drag.y + '%' );
		drag.layer.classList.add( 'is-dragging' );
	},
	true
);

function endDrag( event ) {
	if ( ! drag ) {
		return;
	}
	if ( null !== drag.x ) {
		event.stopPropagation();
		dragged = true;
		drag.layer.classList.remove( 'is-dragging' );
		send( 'slidecraft:move', {
			slideId: drag.slideId,
			layerId: drag.layer.dataset.layerId,
			x: drag.x,
			y: drag.y,
		} );
	}
	drag = null;
}
root.addEventListener( 'pointerup', endDrag, true );
root.addEventListener( 'pointercancel', endDrag, true );

/* Selection outline (editor only, so it lives here rather than in the front-end CSS). */

const style = document.createElement( 'style' );
style.textContent =
	'.slidecraft-preview-page [data-layer-id]{cursor:move;outline:1px dashed transparent;outline-offset:4px}' +
	'.slidecraft-preview-page [data-layer-id]:hover{outline-color:rgba(255,255,255,.7)}' +
	'.slidecraft-preview-page [data-layer-id].is-selected{outline:2px solid #3858e9}' +
	'.slidecraft-preview-page [data-layer-id].is-dragging{opacity:.85}';
document.head.appendChild( style );

if ( 'ResizeObserver' in window ) {
	new window.ResizeObserver( reportHeight ).observe( document.body );
}
send( 'slidecraft:ready' );
