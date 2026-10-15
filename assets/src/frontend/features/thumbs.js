/**
 * Thumbnail strip for galleries, built from the slide images.
 */
import Swiper from 'swiper';

/**
 * Create the thumbnail slider below the main one.
 *
 * @param {HTMLElement} root    Slider element.
 * @param {Function[]}  modules Loaded Swiper modules.
 * @return {Object|null} Thumbs Swiper instance.
 */
export function createThumbs( root, modules ) {
	const slides = root.querySelectorAll(
		'.slidecraft__track > .swiper-slide'
	);
	if ( slides.length < 2 ) {
		return null;
	}
	const strip = document.createElement( 'div' );
	strip.className = 'swiper slidecraft__thumbs';
	strip.setAttribute( 'aria-hidden', 'true' );
	const wrapper = document.createElement( 'div' );
	wrapper.className = 'swiper-wrapper';
	slides.forEach( ( slide ) => {
		const thumb = document.createElement( 'div' );
		thumb.className = 'swiper-slide';
		const img = slide.querySelector( '.slidecraft-slide__image' );
		if ( img ) {
			const copy = document.createElement( 'img' );
			copy.src = img.currentSrc || img.src;
			copy.alt = '';
			copy.loading = 'lazy';
			thumb.appendChild( copy );
		} else {
			thumb.style.background = getComputedStyle(
				slide.querySelector( '.slidecraft-slide__bg' ) || slide
			).background;
		}
		wrapper.appendChild( thumb );
	} );
	strip.appendChild( wrapper );
	root.appendChild( strip );
	return new Swiper( strip, {
		modules: modules.filter( Boolean ),
		slidesPerView: 4.5,
		spaceBetween: 8,
		watchSlidesProgress: true,
		breakpoints: { 768: { slidesPerView: 6.5 } },
	} );
}
