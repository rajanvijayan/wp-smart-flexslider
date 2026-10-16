/**
 * Live preview: an iframe on the site front end that renders the unsaved configuration with the
 * real renderer and runtime (see assets/src/preview).
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { settings } from '../model';

const WIDTHS = { desktop: '100%', tablet: '834px', mobile: '390px' };
// Sliders sized by screen height need a screen-sized frame (a frame that grows to fit would feed back).
const SCREEN_HEIGHTS = {
	desktop: 'calc(100vh - 220px)',
	tablet: '1000px',
	mobile: '760px',
};

export default function Preview( {
	config,
	title,
	id,
	device,
	playing,
	slideId,
	layerId,
	onSelect,
	onMove,
	onSlide,
} ) {
	const frame = useRef();
	const [ ready, setReady ] = useState( false );
	const [ height, setHeight ] = useState( 420 );
	const [ error, setError ] = useState( '' );
	const handlers = useRef( {} );
	handlers.current = { onSelect, onMove, onSlide };
	const origin = useRef( '' );
	try {
		origin.current = new URL( settings.previewUrl ).origin;
	} catch ( e ) {}

	const post = ( message ) => {
		if ( frame.current && frame.current.contentWindow ) {
			frame.current.contentWindow.postMessage(
				{ source: 'slidecraft-builder', ...message },
				origin.current
			);
		}
	};

	useEffect( () => {
		const onMessage = ( event ) => {
			if (
				event.origin !== origin.current ||
				! event.data ||
				'slidecraft-preview' !== event.data.source
			) {
				return;
			}
			const data = event.data;
			switch ( data.type ) {
				case 'slidecraft:ready':
					setReady( true );
					break;
				case 'slidecraft:rendered':
				case 'slidecraft:height':
					setHeight(
						Math.max( 120, Math.min( 4000, data.height || 0 ) )
					);
					setError( '' );
					break;
				case 'slidecraft:select':
					handlers.current.onSelect( data.slideId, data.layerId );
					break;
				case 'slidecraft:move':
					handlers.current.onMove(
						data.slideId,
						data.layerId,
						data.x,
						data.y
					);
					break;
				case 'slidecraft:slide':
					handlers.current.onSlide( data.slideId );
					break;
				case 'slidecraft:error':
					setError( data.message );
					break;
			}
		};
		window.addEventListener( 'message', onMessage );
		return () => window.removeEventListener( 'message', onMessage );
	}, [] );

	// Re-render shortly after edits stop (typing stays smooth).
	useEffect( () => {
		if ( ! ready ) {
			return;
		}
		const preview = JSON.parse( JSON.stringify( config ) );
		if ( ! playing ) {
			preview.settings.autoplay.enabled = false;
		}
		const timer = setTimeout(
			() =>
				post( {
					type: 'slidecraft:render',
					config: preview,
					title,
					id,
					slideId,
				} ),
			220
		);
		return () => clearTimeout( timer );
		// slideId is sent with each render but changing it alone only navigates (below).
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ ready, config, title, id, playing ] );

	useEffect( () => {
		if ( ready && slideId ) {
			post( { type: 'slidecraft:goto', slideId } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ ready, slideId ] );

	useEffect( () => {
		if ( ready ) {
			post( { type: 'slidecraft:select', layerId: layerId || '' } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ ready, layerId ] );

	return (
		<div className={ `slidecraft-preview is-${ device }` }>
			{ ! ready && (
				<div className="slidecraft-preview__loading">
					<Spinner />
				</div>
			) }
			{ error && (
				<div className="slidecraft-preview__error">{ error }</div>
			) }
			<iframe
				ref={ frame }
				title={ __( 'Slider preview', 'wp-smart-flexslider' ) }
				src={ settings.previewUrl }
				style={ {
					width: WIDTHS[ device ],
					height:
						'viewport' === config.settings.height.mode
							? SCREEN_HEIGHTS[ device ]
							: height,
				} }
			/>
		</div>
	);
}
