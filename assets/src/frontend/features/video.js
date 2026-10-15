/**
 * Video slides: embeds and files start only when their slide is active and stop when it leaves.
 */

/**
 * Play media in the active slide and stop media elsewhere.
 *
 * @param {Object} swiper Swiper instance.
 */
export function syncVideos( swiper ) {
	swiper.slides.forEach( ( slide, index ) => {
		const active = index === swiper.activeIndex;
		slide
			.querySelectorAll( 'video.slidecraft-slide__video' )
			.forEach( ( video ) => {
				if ( active ) {
					if ( ! video.src && video.dataset.src ) {
						video.src = video.dataset.src;
					}
					const played = video.play();
					if ( played && played.catch ) {
						played.catch( () => {} );
					}
				} else if ( ! video.paused ) {
					video.pause();
				}
			} );
		slide
			.querySelectorAll( '.slidecraft-slide__embed' )
			.forEach( ( holder ) => {
				const frame = holder.querySelector( 'iframe' );
				if ( active && ! frame ) {
					const iframe = document.createElement( 'iframe' );
					iframe.src = holder.dataset.embed;
					iframe.title = holder.dataset.title || 'Video';
					iframe.allow =
						'autoplay; fullscreen; picture-in-picture; encrypted-media';
					iframe.setAttribute( 'loading', 'lazy' );
					iframe.setAttribute( 'tabindex', '-1' );
					holder.appendChild( iframe );
					holder.classList.add( 'is-playing' );
				} else if ( ! active && frame ) {
					// Removing the iframe is the only reliable way to stop a third-party player.
					frame.remove();
					holder.classList.remove( 'is-playing' );
				}
			} );
	} );
}
