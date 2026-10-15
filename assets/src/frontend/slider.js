/**
 * One slider: reads the server config and starts Swiper with the modules it needs.
 */
import Swiper from 'swiper';
import { loadModules } from './modules';
import { startCountdowns } from './features/countdown';
import { syncVideos } from './features/video';
import { enableLightbox } from './features/lightbox';
import { createThumbs } from './features/thumbs';

const reducedMotion = () =>
	window.matchMedia &&
	window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

const CREATIVE = {
	'zoom-out': {
		prev: { shadow: true, translate: [ 0, 0, -400 ] },
		next: { translate: [ '100%', 0, 0 ] },
	},
	push: {
		prev: { translate: [ '-20%', 0, -1 ] },
		next: { translate: [ '100%', 0, 0 ] },
	},
	rotate: {
		prev: {
			shadow: true,
			translate: [ '-120%', 0, -500 ],
			rotate: [ 0, 0, -15 ],
		},
		next: {
			shadow: true,
			translate: [ '120%', 0, -500 ],
			rotate: [ 0, 0, 15 ],
		},
	},
	stack: {
		prev: {
			shadow: true,
			origin: 'left center',
			translate: [ '-5%', 0, -200 ],
			rotate: [ 0, 100, 0 ],
		},
		next: {
			origin: 'right center',
			translate: [ '5%', 0, -200 ],
			rotate: [ 0, -100, 0 ],
		},
	},
};

/**
 * Swiper options from the runtime config.
 *
 * @param {HTMLElement} root   Slider element.
 * @param {Object}      config Runtime config.
 * @param {boolean}     calm   Reduced motion requested.
 * @return {Object} Options.
 */
function options( root, config, calm ) {
	const isMulti = 'carousel' === config.type || 'ticker' === config.type;
	const nav = config.nav;
	const desktop = isMulti
		? {
				slidesPerView: config.carousel.auto_width
					? 'auto'
					: config.carousel.per_view,
				spaceBetween: config.carousel.gap,
		  }
		: { slidesPerView: 1, spaceBetween: 0 };

	const opts = {
		speed: calm ? 0 : config.speed,
		loop: config.loop,
		initialSlide: config.start,
		direction: config.direction,
		effect: config.effect,
		grabCursor: nav.draggable,
		allowTouchMove: nav.draggable,
		watchSlidesProgress: true,
		autoHeight:
			'auto' === config.height &&
			'auto' ===
				getComputedStyle( root )
					.getPropertyValue( '--sc-ratio' )
					.trim(),
		centeredSlides: isMulti && config.carousel.centered,
		...desktop,
		a11y: {
			enabled: true,
			prevSlideMessage: config.i18n.prev,
			nextSlideMessage: config.i18n.next,
			firstSlideMessage: config.i18n.first,
			lastSlideMessage: config.i18n.last,
			paginationBulletMessage: config.i18n.goto,
			slideLabelMessage: config.i18n.slideOf,
			containerMessage: null,
			containerRoleDescriptionMessage: null,
			itemRoleDescriptionMessage: null,
			slideRole: 'group',
		},
	};

	if ( isMulti ) {
		const bp = config.breakpoints;
		const perView = ( value ) =>
			config.carousel.auto_width ? 'auto' : value;
		// Swiper breakpoints are min-width; the base values are the mobile ones.
		opts.slidesPerView = perView( bp.mobile.perView );
		opts.spaceBetween = bp.mobile.gap;
		opts.breakpoints = {
			[ bp.mobile.max + 1 ]: {
				slidesPerView: perView( bp.tablet.perView ),
				spaceBetween: bp.tablet.gap,
			},
			[ bp.tablet.max + 1 ]: desktop,
		};
	}
	if ( config.carousel.rows > 1 && 'carousel' === config.type ) {
		opts.grid = { rows: config.carousel.rows, fill: 'row' };
		opts.loop = false;
	}
	if ( config.carousel.free_mode && 'carousel' === config.type ) {
		opts.freeMode = { enabled: true, momentum: true };
	}

	switch ( config.effect ) {
		case 'fade':
			opts.fadeEffect = { crossFade: true };
			break;
		case 'coverflow':
			opts.coverflowEffect = {
				rotate: 30,
				stretch: 0,
				depth: 140,
				modifier: 1,
				slideShadows: false,
			};
			opts.centeredSlides = true;
			break;
		case 'cube':
			opts.cubeEffect = { shadow: false, slideShadows: true };
			break;
		case 'flip':
			opts.flipEffect = { slideShadows: false };
			break;
		case 'cards':
			opts.cardsEffect = { slideShadows: false, rotate: true };
			break;
		case 'creative':
			opts.creativeEffect =
				CREATIVE[ config.creative ] || CREATIVE[ 'zoom-out' ];
			break;
	}

	if ( nav.arrows ) {
		opts.navigation = {
			prevEl: root.querySelector( '.slidecraft__arrow--prev' ),
			nextEl: root.querySelector( '.slidecraft__arrow--next' ),
			disabledClass: 'is-disabled',
		};
	}
	const pagination = root.querySelector( '.slidecraft__pagination' );
	if ( pagination && 'none' !== nav.pagination ) {
		opts.pagination = {
			el: pagination,
			clickable: true,
			type: [ 'fraction', 'progressbar' ].includes( nav.pagination )
				? nav.pagination
				: 'bullets',
			dynamicBullets: 'dynamic' === nav.pagination,
			bulletElement: 'button',
		};
	}
	if ( nav.keyboard ) {
		opts.keyboard = { enabled: true, onlyInViewport: true };
	}
	if ( nav.mousewheel ) {
		opts.mousewheel = { forceToAxis: true, releaseOnEdges: true };
	}

	if ( 'ticker' === config.type ) {
		if ( calm ) {
			opts.freeMode = { enabled: true };
		} else {
			// A continuous strip: no delay between transitions and a linear easing (see CSS).
			opts.loop = true;
			opts.freeMode = { enabled: true, momentum: false };
			opts.autoplay = {
				delay: 0,
				disableOnInteraction: false,
				pauseOnMouseEnter: true,
				reverseDirection: 'right' === config.ticker.direction,
			};
			opts.speed = Math.round(
				( 1000 * 260 ) / Math.max( 5, config.ticker.speed )
			);
		}
	} else if ( config.autoplay && ! calm ) {
		opts.autoplay = {
			delay: config.autoplay.delay,
			disableOnInteraction: false,
			pauseOnMouseEnter: config.autoplay.pauseOnHover,
		};
	}
	return opts;
}

/**
 * Reveal the layers of the active slide (CSS runs the entrance animation).
 *
 * @param {Object} swiper Swiper instance.
 */
function animateLayers( swiper ) {
	swiper.slides.forEach( ( slide, index ) => {
		const active =
			index === swiper.activeIndex ||
			( swiper.params.slidesPerView !== 1 &&
				slide.classList.contains( 'swiper-slide-visible' ) );
		slide.querySelectorAll( '[data-anim]' ).forEach( ( layer ) => {
			if ( active ) {
				layer.classList.add( 'sc-in' );
			} else {
				layer.classList.remove( 'sc-in' );
			}
		} );
	} );
}

/**
 * Pause/play button and progress bar.
 *
 * @param {HTMLElement} root   Slider element.
 * @param {Object}      swiper Swiper instance.
 * @param {Object}      i18n   Labels.
 */
function autoplayControls( root, swiper, i18n ) {
	const button = root.querySelector( '.slidecraft__pause' );
	const bar = root.querySelector( '.slidecraft__progress span' );
	let userPaused = false;

	const setPaused = ( paused ) => {
		userPaused = paused;
		root.classList.toggle( 'is-paused', paused );
		if ( button ) {
			button.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			button.setAttribute(
				'aria-label',
				paused ? i18n.play : i18n.pause
			);
		}
		if ( paused ) {
			swiper.autoplay.stop();
		} else {
			swiper.autoplay.start();
		}
	};

	if ( button ) {
		button.addEventListener( 'click', () => setPaused( ! userPaused ) );
	}
	if ( bar ) {
		swiper.on( 'autoplayTimeLeft', ( s, time, progress ) => {
			bar.style.transform = `scaleX(${ 1 - progress })`;
		} );
	}
	// Keyboard users: stop while focus is inside the slider (WCAG 2.2.2), resume when it leaves.
	root.addEventListener(
		'focusin',
		() => swiper.autoplay.running && swiper.autoplay.pause()
	);
	root.addEventListener( 'focusout', ( event ) => {
		if ( ! root.contains( event.relatedTarget ) && ! userPaused ) {
			swiper.autoplay.resume();
		}
	} );
	// Don't run while the tab is hidden.
	document.addEventListener( 'visibilitychange', () => {
		if ( document.hidden ) {
			swiper.autoplay.pause();
		} else if ( ! userPaused ) {
			swiper.autoplay.resume();
		}
	} );
}

/**
 * Start a slider.
 *
 * @param {HTMLElement} root Element with data-slidecraft.
 * @return {Promise<Object|null>} Swiper instance.
 */
export async function mountSlider( root ) {
	if ( root.slidecraft || root.dataset.slidecraftMounting ) {
		return root.slidecraft || null;
	}
	root.dataset.slidecraftMounting = '1';
	let config;
	try {
		config = JSON.parse( root.dataset.slidecraft );
	} catch ( e ) {
		return null;
	}
	const calm = reducedMotion();
	const modules = await loadModules( config.modules );
	const viewport = root.querySelector( '.slidecraft__viewport' );
	const opts = options( root, config, calm );
	opts.modules = modules;

	if (
		config.modules.includes( 'thumbs' ) &&
		'thumbnails' === config.nav.pagination
	) {
		const thumbs = createThumbs( root, modules );
		if ( thumbs ) {
			opts.thumbs = { swiper: thumbs };
		}
	}

	opts.on = {
		init( s ) {
			root.classList.add( 'is-ready' );
			if ( calm ) {
				root.classList.add( 'is-calm' );
			}
			animateLayers( s );
			syncVideos( s );
		},
		slideChangeTransitionStart( s ) {
			animateLayers( s );
			syncVideos( s );
		},
		resize( s ) {
			animateLayers( s );
		},
	};

	const swiper = new Swiper( viewport, opts );
	root.slidecraft = swiper;
	delete root.dataset.slidecraftMounting;

	if ( swiper.autoplay && opts.autoplay && 'ticker' !== config.type ) {
		autoplayControls( root, swiper, config.i18n );
	} else if ( calm && config.autoplay ) {
		// Autoplay is off for reduced motion; the pause button has nothing to control.
		const button = root.querySelector( '.slidecraft__pause' );
		if ( button ) {
			button.hidden = true;
		}
	}
	startCountdowns( root, config.i18n );
	if ( config.lightbox ) {
		enableLightbox( root, swiper, config.i18n );
	}
	root.dispatchEvent(
		new CustomEvent( 'slidecraft:ready', {
			detail: { swiper },
			bubbles: true,
		} )
	);
	return swiper;
}

/**
 * Stop and remove a slider instance (used by the builder preview before re-rendering).
 *
 * @param {HTMLElement} root Slider element.
 */
export function unmountSlider( root ) {
	if ( root.slidecraft ) {
		root.slidecraft.destroy( true, true );
		delete root.slidecraft;
	}
}
