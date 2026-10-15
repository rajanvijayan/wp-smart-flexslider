/**
 * Slider configuration helpers: IDs, defaults from the server schema and immutable updates.
 */
import { __, sprintf } from '@wordpress/i18n';

export const settings = window.slideCraftSettings || {};
const defaults = settings.defaults || { config: {}, slides: {}, layers: {} };

export const MAX_SLIDES = defaults.maxSlides || 200;
export const MAX_LAYERS = defaults.maxLayers || 12;

const clone = ( value ) => JSON.parse( JSON.stringify( value ) );

/**
 * Eight lowercase letters and digits, like the server's IDs.
 *
 * @return {string} ID.
 */
export function newId() {
	const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
	let id = '';
	const random = new Uint8Array( 8 );
	window.crypto.getRandomValues( random );
	random.forEach( ( n ) => ( id += chars[ n % chars.length ] ) );
	return id;
}

export function blankConfig() {
	return clone( defaults.config );
}

export function newSlide( type = 'image' ) {
	const slide = clone( defaults.slides[ type ] || defaults.slides.image );
	slide.id = newId();
	if ( 'content' === type ) {
		slide.background.type = 'gradient';
		slide.layers = [
			newLayer( 'heading', {
				text: __( 'Your headline', 'wp-smart-flexslider' ),
			} ),
			newLayer( 'text', {
				text: __(
					'A short sentence about this slide.',
					'wp-smart-flexslider'
				),
				size: 'm',
				animation: { delay: 150 },
			} ),
		];
	}
	if ( 'video' === type ) {
		slide.background.type = 'video';
	}
	if ( 'testimonial' === type ) {
		slide.background.type = 'none';
	}
	return slide;
}

const LAYER_TEXT = {
	heading: () => __( 'New heading', 'wp-smart-flexslider' ),
	text: () => __( 'New text', 'wp-smart-flexslider' ),
	button: () => __( 'Learn more', 'wp-smart-flexslider' ),
	image: () => '',
	countdown: () => '',
};

export function newLayer( type = 'heading', data = {} ) {
	const layer = clone( defaults.layers[ type ] || defaults.layers.heading );
	layer.id = newId();
	layer.text = LAYER_TEXT[ type ] ? LAYER_TEXT[ type ]() : '';
	if ( 'text' === type ) {
		layer.level = 'p';
		layer.size = 'm';
	}
	if ( 'button' === type ) {
		layer.size = 'm';
	}
	if ( 'countdown' === type ) {
		const date = new Date( Date.now() + 7 * 864e5 );
		layer.date = date.toISOString().slice( 0, 16 );
	}
	return merge( layer, data );
}

/**
 * Deep merge of plain objects (arrays are replaced).
 *
 * @param {Object} target Base.
 * @param {Object} source Changes.
 * @return {Object} New object.
 */
export function merge( target, source ) {
	const out = { ...target };
	Object.keys( source || {} ).forEach( ( key ) => {
		const value = source[ key ];
		out[ key ] =
			value &&
			typeof value === 'object' &&
			! Array.isArray( value ) &&
			target[ key ] &&
			typeof target[ key ] === 'object'
				? merge( target[ key ], value )
				: value;
	} );
	return out;
}

/**
 * Copy of `object` with the value at `path` replaced.
 *
 * @param {Object|Array} object Source.
 * @param {Array}        path   Keys.
 * @param {*|Function}   value  New value, or a function of the old one.
 * @return {Object|Array} Updated copy.
 */
export function setIn( object, path, value ) {
	if ( ! path.length ) {
		return typeof value === 'function' ? value( object ) : value;
	}
	const [ key, ...rest ] = path;
	const copy = Array.isArray( object ) ? [ ...object ] : { ...object };
	copy[ key ] = setIn( object[ key ], rest, value );
	return copy;
}

export function duplicateSlide( slide ) {
	const copy = clone( slide );
	copy.id = newId();
	copy.layers = copy.layers.map( ( layer ) => ( { ...layer, id: newId() } ) );
	return copy;
}

export function duplicateLayer( layer ) {
	return { ...clone( layer ), id: newId() };
}

/**
 * Plain text of a layer or slide for lists.
 *
 * @param {string} html Inline HTML.
 * @return {string} Text.
 */
export function plain( html ) {
	const div = document.createElement( 'div' );
	div.innerHTML = html || '';
	return ( div.textContent || '' ).trim();
}

export const SLIDE_TYPES = [
	{
		value: 'image',
		label: __( 'Image', 'wp-smart-flexslider' ),
		icon: 'format-image',
	},
	{
		value: 'content',
		label: __( 'Content', 'wp-smart-flexslider' ),
		icon: 'text',
	},
	{
		value: 'video',
		label: __( 'Video', 'wp-smart-flexslider' ),
		icon: 'video-alt3',
	},
	{
		value: 'posts',
		label: __( 'Posts (dynamic)', 'wp-smart-flexslider' ),
		icon: 'admin-post',
	},
	{
		value: 'testimonial',
		label: __( 'Testimonial', 'wp-smart-flexslider' ),
		icon: 'format-quote',
	},
	{
		value: 'logo',
		label: __( 'Logo', 'wp-smart-flexslider' ),
		icon: 'awards',
	},
];

export const LAYER_TYPES = [
	{
		value: 'heading',
		label: __( 'Heading', 'wp-smart-flexslider' ),
		icon: 'heading',
	},
	{
		value: 'text',
		label: __( 'Text', 'wp-smart-flexslider' ),
		icon: 'editor-paragraph',
	},
	{
		value: 'button',
		label: __( 'Button', 'wp-smart-flexslider' ),
		icon: 'button',
	},
	{
		value: 'image',
		label: __( 'Image', 'wp-smart-flexslider' ),
		icon: 'format-image',
	},
	{
		value: 'countdown',
		label: __( 'Countdown', 'wp-smart-flexslider' ),
		icon: 'clock',
	},
];

export const typeLabel = ( list, value ) =>
	( list.find( ( t ) => t.value === value ) || {} ).label || value;

/**
 * Short label for a slide in the list.
 *
 * @param {Object} slide Slide.
 * @param {number} index Position.
 * @return {string} Label.
 */
export function slideLabel( slide, index ) {
	if ( slide.title ) {
		return slide.title;
	}
	if ( 'testimonial' === slide.type && slide.testimonial.name ) {
		return slide.testimonial.name;
	}
	const heading = slide.layers.find(
		( l ) => 'heading' === l.type || 'text' === l.type
	);
	const text = heading ? plain( heading.text ) : '';
	return (
		text ||
		sprintf(
			/* translators: %d: slide number */
			__( 'Slide %d', 'wp-smart-flexslider' ),
			index + 1
		)
	);
}

/**
 * Whether a slide type can carry layers.
 *
 * @param {string} type Slide type.
 * @return {boolean} Has layers.
 */
export const hasLayers = ( type ) =>
	! [ 'testimonial', 'logo' ].includes( type );
