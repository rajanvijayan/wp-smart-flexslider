/**
 * Slider library: search, cards, copy shortcode, duplicate, export, import and delete.
 */
import { useEffect, useState, useRef, useCallback } from '@wordpress/element';
import {
	Button,
	SearchControl,
	Spinner,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Modal,
} from '@wordpress/components';
import { __, sprintf, _n } from '@wordpress/i18n';
import { moreVertical, plus, upload } from '@wordpress/icons';
import { api, errorMessage } from '../api';
import { navigate, useNotify } from '../context';
import { typeLabel } from '../model';

const PER_PAGE = 24;

const TYPES = [
	{ value: 'slider', label: __( 'Slider', 'wp-smart-flexslider' ) },
	{ value: 'carousel', label: __( 'Carousel', 'wp-smart-flexslider' ) },
	{ value: 'gallery', label: __( 'Gallery', 'wp-smart-flexslider' ) },
	{ value: 'ticker', label: __( 'Ticker', 'wp-smart-flexslider' ) },
];

/* eslint-disable no-alert -- prompt() is the copy fallback where the clipboard API is unavailable. */
export function copyText( text, notify ) {
	const done = () =>
		notify( __( 'Shortcode copied.', 'wp-smart-flexslider' ) );
	if ( window.navigator.clipboard && window.isSecureContext ) {
		window.navigator.clipboard
			.writeText( text )
			.then( done, () => window.prompt( '', text ) );
	} else {
		window.prompt(
			__( 'Copy the shortcode:', 'wp-smart-flexslider' ),
			text
		);
	}
}
/* eslint-enable no-alert */

function download( name, data ) {
	const blob = new window.Blob( [ JSON.stringify( data, null, 2 ) ], {
		type: 'application/json',
	} );
	const url = window.URL.createObjectURL( blob );
	const a = document.createElement( 'a' );
	a.href = url;
	a.download = name;
	document.body.appendChild( a );
	a.click();
	a.remove();
	setTimeout( () => window.URL.revokeObjectURL( url ), 1000 );
}

function relativeTime( iso ) {
	const date = new Date( iso );
	return isNaN( date )
		? ''
		: date.toLocaleDateString( undefined, {
				year: 'numeric',
				month: 'short',
				day: 'numeric',
		  } );
}

function Card( { slider, onChanged, onDelete } ) {
	const notify = useNotify();
	const edit = `#/edit/${ slider.id }`;

	const duplicate = async () => {
		try {
			await api.duplicate( slider.id );
			notify( __( 'Slider duplicated.', 'wp-smart-flexslider' ) );
			onChanged();
		} catch ( e ) {
			notify( errorMessage( e ), 'error' );
		}
	};
	const exportSlider = async () => {
		try {
			const data = await api.exportSlider( slider.id );
			const slug = ( slider.title || 'slider' )
				.toLowerCase()
				.replace( /[^a-z0-9]+/g, '-' )
				.replace( /^-|-$/g, '' );
			download( `slidecraft-${ slug || slider.id }.json`, data );
		} catch ( e ) {
			notify( errorMessage( e ), 'error' );
		}
	};

	return (
		<li className="slidecraft-card">
			<a
				className={ `slidecraft-card__thumb is-${ slider.type }` }
				href={ edit }
				aria-hidden="true"
				tabIndex={ -1 }
			>
				{ slider.thumb ? (
					<img src={ slider.thumb } alt="" loading="lazy" />
				) : (
					<span
						className={ `slidecraft-card__placeholder is-${ slider.type }` }
						style={
							slider.swatch
								? { background: slider.swatch }
								: undefined
						}
					/>
				) }
				<span className="slidecraft-card__badge">
					{ typeLabel( TYPES, slider.type ) }
				</span>
			</a>
			<div className="slidecraft-card__body">
				<h2 className="slidecraft-card__title">
					<a href={ edit }>
						{ slider.title ||
							__( '(no title)', 'wp-smart-flexslider' ) }
					</a>
				</h2>
				<p className="slidecraft-card__meta">
					{ sprintf(
						/* translators: %d: number of slides */
						_n(
							'%d slide',
							'%d slides',
							slider.slides,
							'wp-smart-flexslider'
						),
						slider.slides
					) }
					{ ' · ' }
					{ relativeTime( slider.modified ) }
				</p>
				<div className="slidecraft-card__actions">
					<button
						type="button"
						className="slidecraft-shortcode"
						onClick={ () => copyText( slider.shortcode, notify ) }
						title={ __( 'Copy shortcode', 'wp-smart-flexslider' ) }
					>
						<code>{ slider.shortcode }</code>
					</button>
					<DropdownMenu
						icon={ moreVertical }
						label={ __( 'More actions', 'wp-smart-flexslider' ) }
					>
						{ ( { onClose } ) => (
							<>
								<MenuGroup>
									<MenuItem
										onClick={ () => navigate( edit ) }
									>
										{ __( 'Edit', 'wp-smart-flexslider' ) }
									</MenuItem>
									<MenuItem
										onClick={ () => {
											onClose();
											duplicate();
										} }
									>
										{ __(
											'Duplicate',
											'wp-smart-flexslider'
										) }
									</MenuItem>
									<MenuItem
										onClick={ () => {
											onClose();
											exportSlider();
										} }
									>
										{ __(
											'Export',
											'wp-smart-flexslider'
										) }
									</MenuItem>
								</MenuGroup>
								<MenuGroup>
									<MenuItem
										isDestructive
										onClick={ () => {
											onClose();
											onDelete( slider );
										} }
									>
										{ __(
											'Delete',
											'wp-smart-flexslider'
										) }
									</MenuItem>
								</MenuGroup>
							</>
						) }
					</DropdownMenu>
				</div>
			</div>
		</li>
	);
}

export default function Library() {
	const notify = useNotify();
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ data, setData ] = useState( null );
	const [ confirm, setConfirm ] = useState( null );
	const fileInput = useRef();
	const request = useRef( 0 );

	const load = useCallback( () => {
		const id = ++request.current;
		api.list( { search, page, perPage: PER_PAGE } )
			.then( ( result ) => id === request.current && setData( result ) )
			.catch( ( e ) => notify( errorMessage( e ), 'error' ) );
	}, [ search, page, notify ] );

	useEffect( () => {
		const timer = setTimeout( load, search ? 250 : 0 );
		return () => clearTimeout( timer );
	}, [ load, search ] );

	const remove = async () => {
		const slider = confirm;
		setConfirm( null );
		try {
			await api.remove( slider.id );
			notify( __( 'Slider deleted.', 'wp-smart-flexslider' ) );
			load();
		} catch ( e ) {
			notify( errorMessage( e ), 'error' );
		}
	};

	const importFile = async ( event ) => {
		const file = event.target.files[ 0 ];
		event.target.value = '';
		if ( ! file ) {
			return;
		}
		try {
			const json = JSON.parse( await file.text() );
			const slider = await api.importSlider( json );
			notify( __( 'Slider imported.', 'wp-smart-flexslider' ) );
			navigate( `#/edit/${ slider.id }` );
		} catch ( e ) {
			notify(
				e instanceof SyntaxError
					? __(
							'This file is not valid JSON.',
							'wp-smart-flexslider'
					  )
					: errorMessage( e ),
				'error'
			);
		}
	};

	const pages = data ? Math.max( 1, Math.ceil( data.total / PER_PAGE ) ) : 1;

	return (
		<div className="slidecraft-library">
			<header className="slidecraft-library__header">
				<h1>{ __( 'Sliders', 'wp-smart-flexslider' ) }</h1>
				<div className="slidecraft-library__tools">
					<SearchControl
						__nextHasNoMarginBottom
						value={ search }
						onChange={ ( value ) => {
							setSearch( value );
							setPage( 1 );
						} }
						placeholder={ __(
							'Search sliders',
							'wp-smart-flexslider'
						) }
					/>
					<Button
						variant="secondary"
						icon={ upload }
						onClick={ () => fileInput.current.click() }
					>
						{ __( 'Import', 'wp-smart-flexslider' ) }
					</Button>
					<input
						ref={ fileInput }
						type="file"
						accept="application/json,.json"
						hidden
						onChange={ importFile }
					/>
					<Button variant="primary" icon={ plus } href="#/new">
						{ __( 'New slider', 'wp-smart-flexslider' ) }
					</Button>
				</div>
			</header>

			{ ! data && <Spinner /> }

			{ data && ! data.items.length && (
				<div className="slidecraft-empty">
					{ search ? (
						<p>
							{ __(
								'No sliders match your search.',
								'wp-smart-flexslider'
							) }
						</p>
					) : (
						<>
							<h2>
								{ __(
									'Create your first slider',
									'wp-smart-flexslider'
								) }
							</h2>
							<p>
								{ __(
									'Start from a template or a blank slider. You can add it to any page with the SlideCraft Slider block or a shortcode.',
									'wp-smart-flexslider'
								) }
							</p>
							<Button variant="primary" href="#/new">
								{ __(
									'Choose a template',
									'wp-smart-flexslider'
								) }
							</Button>
						</>
					) }
				</div>
			) }

			{ data && data.items.length > 0 && (
				<ul className="slidecraft-grid">
					{ data.items.map( ( slider ) => (
						<Card
							key={ slider.id }
							slider={ slider }
							onChanged={ load }
							onDelete={ setConfirm }
						/>
					) ) }
				</ul>
			) }

			{ pages > 1 && (
				<nav
					className="slidecraft-pagination"
					aria-label={ __( 'Pages', 'wp-smart-flexslider' ) }
				>
					<Button
						variant="tertiary"
						disabled={ page <= 1 }
						onClick={ () => setPage( page - 1 ) }
					>
						{ __( 'Previous', 'wp-smart-flexslider' ) }
					</Button>
					<span>
						{ sprintf(
							/* translators: 1: current page, 2: number of pages */
							__( 'Page %1$d of %2$d', 'wp-smart-flexslider' ),
							page,
							pages
						) }
					</span>
					<Button
						variant="tertiary"
						disabled={ page >= pages }
						onClick={ () => setPage( page + 1 ) }
					>
						{ __( 'Next', 'wp-smart-flexslider' ) }
					</Button>
				</nav>
			) }

			{ confirm && (
				<Modal
					title={ __( 'Delete slider?', 'wp-smart-flexslider' ) }
					onRequestClose={ () => setConfirm( null ) }
					size="small"
				>
					<p>
						{ sprintf(
							/* translators: %s: slider title */
							__(
								'“%s” will be deleted. Pages that show it will show nothing in its place.',
								'wp-smart-flexslider'
							),
							confirm.title ||
								__( '(no title)', 'wp-smart-flexslider' )
						) }
					</p>
					<div className="slidecraft-modal-actions">
						<Button
							variant="tertiary"
							onClick={ () => setConfirm( null ) }
						>
							{ __( 'Cancel', 'wp-smart-flexslider' ) }
						</Button>
						<Button
							variant="primary"
							isDestructive
							onClick={ remove }
						>
							{ __( 'Delete', 'wp-smart-flexslider' ) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
}
