/**
 * A small accessible lightbox built on <dialog>.
 */
let dialog;

function ensureDialog( i18n ) {
	if ( dialog ) {
		return dialog;
	}
	dialog = document.createElement( 'dialog' );
	dialog.className = 'slidecraft-lightbox';
	dialog.innerHTML = `
		<button type="button" class="slidecraft-lightbox__close" aria-label="${ i18n.close }">×</button>
		<button type="button" class="slidecraft-lightbox__prev" aria-label="${ i18n.prev }">‹</button>
		<figure class="slidecraft-lightbox__figure"><img alt="" /><figcaption></figcaption></figure>
		<button type="button" class="slidecraft-lightbox__next" aria-label="${ i18n.next }">›</button>`;
	document.body.appendChild( dialog );
	dialog.addEventListener( 'click', ( event ) => {
		if ( event.target === dialog ) {
			dialog.close();
		}
	} );
	dialog
		.querySelector( '.slidecraft-lightbox__close' )
		.addEventListener( 'click', () => dialog.close() );
	return dialog;
}

/**
 * Open images of a slider in the lightbox when clicked.
 *
 * @param {HTMLElement} root   Slider element.
 * @param {Object}      swiper Swiper instance.
 * @param {Object}      i18n   Labels.
 */
export function enableLightbox( root, swiper, i18n ) {
	const images = () =>
		Array.from(
			root.querySelectorAll(
				'.swiper-slide:not(.swiper-slide-duplicate) .slidecraft-slide__image'
			)
		);

	root.addEventListener( 'click', ( event ) => {
		const img = event.target.closest( '.slidecraft-slide__bg' );
		if ( ! img || event.target.closest( 'a, button' ) ) {
			return;
		}
		const list = images();
		const slideImage = img.querySelector( '.slidecraft-slide__image' );
		let index = list.indexOf( slideImage );
		if ( index < 0 ) {
			return;
		}
		const box = ensureDialog( i18n );
		const show = () => {
			const source = list[ index ];
			const target = box.querySelector( 'img' );
			target.src = source.currentSrc || source.src;
			target.alt = source.alt;
			box.querySelector( 'figcaption' ).textContent = source.alt;
		};
		box.querySelector( '.slidecraft-lightbox__prev' ).onclick = () => {
			index = ( index - 1 + list.length ) % list.length;
			show();
		};
		box.querySelector( '.slidecraft-lightbox__next' ).onclick = () => {
			index = ( index + 1 ) % list.length;
			show();
		};
		box.onkeydown = ( e ) => {
			if ( 'ArrowLeft' === e.key ) {
				box.querySelector( '.slidecraft-lightbox__prev' ).click();
			} else if ( 'ArrowRight' === e.key ) {
				box.querySelector( '.slidecraft-lightbox__next' ).click();
			}
		};
		show();
		if ( swiper.autoplay ) {
			swiper.autoplay.stop();
		}
		box.showModal();
	} );
	root.classList.add( 'has-lightbox' );
}
