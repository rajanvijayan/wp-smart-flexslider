/**
 * Live countdown layers: [data-countdown] holds a Unix timestamp.
 */
const timers = new WeakMap();

function unit( value, label ) {
	return `<span class="sc-cd-unit"><b>${ String( value ).padStart(
		2,
		'0'
	) }</b><small>${ label }</small></span>`;
}

/**
 * Start every countdown inside a slider element.
 *
 * @param {HTMLElement} root Slider element.
 * @param {Object}      i18n Labels.
 */
export function startCountdowns( root, i18n ) {
	root.querySelectorAll( '[data-countdown]' ).forEach( ( el ) => {
		if ( timers.has( el ) ) {
			return;
		}
		const end = Number( el.dataset.countdown ) * 1000;
		const tick = () => {
			const left = Math.max( 0, end - Date.now() );
			const s = Math.floor( left / 1000 );
			el.innerHTML =
				unit( Math.floor( s / 86400 ), i18n.days ) +
				unit( Math.floor( ( s % 86400 ) / 3600 ), i18n.hours ) +
				unit( Math.floor( ( s % 3600 ) / 60 ), i18n.minutes ) +
				unit( s % 60, i18n.seconds );
			el.setAttribute( 'role', 'timer' );
			if ( ! left ) {
				clearInterval( timers.get( el ) );
			}
		};
		tick();
		timers.set( el, setInterval( tick, 1000 ) );
	} );
}
