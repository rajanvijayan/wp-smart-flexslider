/**
 * REST client for the slidecraft/v1 API and a small media cache.
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

const NS = '/slidecraft/v1';

export const api = {
	async list( { search = '', page = 1, perPage = 24 } = {} ) {
		const params = new URLSearchParams( {
			search,
			page,
			per_page: perPage,
		} );
		const response = await apiFetch( {
			path: `${ NS }/sliders?${ params }`,
			parse: false,
		} );
		return {
			items: await response.json(),
			total: Number( response.headers.get( 'X-WP-Total' ) ) || 0,
		};
	},
	get: ( id ) => apiFetch( { path: `${ NS }/sliders/${ id }` } ),
	create: ( title, config ) =>
		apiFetch( {
			path: `${ NS }/sliders`,
			method: 'POST',
			data: { title, config },
		} ),
	update: ( id, data ) =>
		apiFetch( { path: `${ NS }/sliders/${ id }`, method: 'POST', data } ),
	remove: ( id ) =>
		apiFetch( { path: `${ NS }/sliders/${ id }`, method: 'DELETE' } ),
	duplicate: ( id ) =>
		apiFetch( {
			path: `${ NS }/sliders/${ id }/duplicate`,
			method: 'POST',
		} ),
	exportSlider: ( id ) =>
		apiFetch( { path: `${ NS }/sliders/${ id }/export` } ),
	importSlider: ( data ) =>
		apiFetch( { path: `${ NS }/sliders/import`, method: 'POST', data } ),
	revisions: ( id ) =>
		apiFetch( { path: `${ NS }/sliders/${ id }/revisions` } ),
	restore: ( id, index ) =>
		apiFetch( {
			path: `${ NS }/sliders/${ id }/revisions/${ index }/restore`,
			method: 'POST',
		} ),
	sources: () => apiFetch( { path: `${ NS }/sources` } ),
	terms: ( taxonomy, search = '' ) =>
		apiFetch( {
			path: `${ NS }/sources/terms?${ new URLSearchParams( {
				taxonomy,
				search,
			} ) }`,
		} ),
};

/* Attachments: one request per batch of unknown IDs, shared by every component. */

const media = new Map();
const listeners = new Set();
let queue = new Set();
let timer = null;

function flush() {
	const ids = [ ...queue ];
	queue = new Set();
	timer = null;
	if ( ! ids.length ) {
		return;
	}
	apiFetch( {
		path: `/wp/v2/media?include=${ ids.join(
			','
		) }&per_page=100&_fields=id,source_url,alt_text,mime_type,media_details`,
	} )
		.then( ( items ) => {
			items.forEach( ( item ) => {
				const sizes =
					( item.media_details && item.media_details.sizes ) || {};
				const thumb = sizes.medium || sizes.thumbnail || {};
				media.set( item.id, {
					id: item.id,
					url: item.source_url,
					thumb: thumb.source_url || item.source_url,
					alt: item.alt_text,
					mime: item.mime_type,
					width: item.media_details && item.media_details.width,
					height: item.media_details && item.media_details.height,
				} );
			} );
			ids.filter( ( id ) => ! media.has( id ) ).forEach( ( id ) =>
				media.set( id, null )
			);
			listeners.forEach( ( fn ) => fn() );
		} )
		.catch( () => {} );
}

/**
 * Remember an attachment chosen in the media library (no request needed).
 *
 * @param {Object} attachment wp.media attachment JSON.
 */
export function rememberMedia( attachment ) {
	const sizes = attachment.sizes || {};
	const thumb = sizes.medium || sizes.thumbnail || {};
	media.set( attachment.id, {
		id: attachment.id,
		url: attachment.url,
		thumb: thumb.url || attachment.icon || attachment.url,
		alt: attachment.alt,
		mime: attachment.mime,
		width: attachment.width,
		height: attachment.height,
	} );
	listeners.forEach( ( fn ) => fn() );
}

/**
 * Attachment data for an ID (null while loading or if missing).
 *
 * @param {number} id Attachment ID.
 * @return {Object|null} Attachment.
 */
export function useMedia( id ) {
	const [ , force ] = useState( 0 );
	useEffect( () => {
		const fn = () => force( ( n ) => n + 1 );
		listeners.add( fn );
		return () => listeners.delete( fn );
	}, [] );
	if ( id && ! media.has( id ) && ! queue.has( id ) ) {
		queue.add( id );
		if ( ! timer ) {
			timer = setTimeout( flush, 30 );
		}
	}
	return id ? media.get( id ) || null : null;
}

/**
 * Error message from an apiFetch rejection.
 *
 * @param {Object} error Error.
 * @return {string} Message.
 */
export const errorMessage = ( error ) =>
	( error && error.message ) || String( error );
