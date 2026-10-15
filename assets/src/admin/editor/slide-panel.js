/**
 * Slide settings: type, background, video, testimonial, logo, dynamic source and link.
 */
import { useEffect, useState } from '@wordpress/element';
import {
	PanelBody,
	TextControl,
	TextareaControl,
	SelectControl,
	ToggleControl,
	RangeControl,
	CheckboxControl,
	FormTokenField,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { SLIDE_TYPES } from '../model';
import {
	MediaField,
	FocalField,
	ColorField,
	Segmented,
	opts,
} from './controls';

let sourcesCache = null;
function useSources() {
	const [ sources, setSources ] = useState( sourcesCache );
	useEffect( () => {
		if ( ! sourcesCache ) {
			api.sources()
				.then( ( data ) => {
					sourcesCache = data;
					setSources( data );
				} )
				.catch( () =>
					setSources( { post_types: [], woocommerce: false } )
				);
		}
	}, [] );
	return sources;
}

/**
 * Pick terms of a taxonomy by name, stored as IDs.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.taxonomy Taxonomy.
 * @param {number[]} props.value    Term IDs.
 * @param {Function} props.onChange Called with IDs.
 * @return {Element} Control.
 */
function TermsField( { taxonomy, value, onChange } ) {
	const [ terms, setTerms ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	useEffect( () => {
		let live = true;
		const timer = setTimeout( () => {
			api.terms( taxonomy, search )
				.then(
					( list ) =>
						live &&
						setTerms( ( old ) => {
							const seen = new Map(
								old.map( ( t ) => [ t.id, t ] )
							);
							list.forEach( ( t ) => seen.set( t.id, t ) );
							return [ ...seen.values() ];
						} )
				)
				.catch( () => {} );
		}, 200 );
		return () => {
			live = false;
			clearTimeout( timer );
		};
	}, [ taxonomy, search ] );

	const nameOf = ( id ) =>
		( terms.find( ( t ) => t.id === id ) || {} ).name || `#${ id }`;
	return (
		<FormTokenField
			__nextHasNoMarginBottom
			__experimentalExpandOnFocus
			label={ __( 'Terms', 'wp-smart-flexslider' ) }
			value={ value.map( nameOf ) }
			suggestions={ terms.map( ( t ) => t.name ) }
			onInputChange={ setSearch }
			onChange={ ( names ) =>
				onChange(
					names
						.map( ( name ) => {
							const term = terms.find(
								( t ) =>
									t.name === name || `#${ t.id }` === name
							);
							return term
								? term.id
								: Number( String( name ).replace( '#', '' ) ) ||
										0;
						} )
						.filter( Boolean )
				)
			}
		/>
	);
}

function SourceFields( { source, set } ) {
	const sources = useSources();
	if ( ! sources ) {
		return null;
	}
	const type = sources.post_types.find(
		( t ) => t.slug === source.post_type
	);
	const taxonomies = type ? type.taxonomies : [];
	const isProduct = 'product' === source.post_type;
	const SHOW = {
		title: __( 'Title', 'wp-smart-flexslider' ),
		excerpt: __( 'Excerpt', 'wp-smart-flexslider' ),
		date: __( 'Date', 'wp-smart-flexslider' ),
		author: __( 'Author', 'wp-smart-flexslider' ),
		price: __( 'Price', 'wp-smart-flexslider' ),
		rating: __( 'Rating', 'wp-smart-flexslider' ),
		button: __( 'Button', 'wp-smart-flexslider' ),
	};
	const ORDER = {
		date: __( 'Newest', 'wp-smart-flexslider' ),
		modified: __( 'Recently updated', 'wp-smart-flexslider' ),
		title: __( 'Title', 'wp-smart-flexslider' ),
		menu_order: __( 'Menu order', 'wp-smart-flexslider' ),
		comment_count: __( 'Most comments', 'wp-smart-flexslider' ),
		rand: __( 'Random', 'wp-smart-flexslider' ),
	};
	if ( isProduct ) {
		ORDER.price = __( 'Price', 'wp-smart-flexslider' );
		ORDER.popularity = __( 'Best selling', 'wp-smart-flexslider' );
	}

	return (
		<PanelBody title={ __( 'Content source', 'wp-smart-flexslider' ) }>
			<p className="slidecraft-help">
				{ __(
					'This slide becomes one slide per item. Its background overlay and the style of its first heading, text and button are used for every item.',
					'wp-smart-flexslider'
				) }
			</p>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Show', 'wp-smart-flexslider' ) }
				value={ source.post_type }
				options={ sources.post_types.map( ( t ) => ( {
					value: t.slug,
					label: t.label,
				} ) ) }
				onChange={ ( v ) =>
					set( [ 'source' ], {
						...source,
						post_type: v,
						taxonomy: '',
						terms: [],
					} )
				}
			/>
			{ isProduct && ! sources.woocommerce && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'WooCommerce is not active.',
						'wp-smart-flexslider'
					) }
				</Notice>
			) }
			{ taxonomies.length > 0 && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Filter by', 'wp-smart-flexslider' ) }
					value={ source.taxonomy }
					options={ [
						{
							value: '',
							label: __(
								'Nothing (all items)',
								'wp-smart-flexslider'
							),
						},
						...taxonomies.map( ( t ) => ( {
							value: t.slug,
							label: t.label,
						} ) ),
					] }
					onChange={ ( v ) =>
						set( [ 'source' ], {
							...source,
							taxonomy: v,
							terms: [],
						} )
					}
				/>
			) }
			{ source.taxonomy && (
				<TermsField
					key={ source.taxonomy }
					taxonomy={ source.taxonomy }
					value={ source.terms }
					onChange={ ( v ) => set( [ 'source', 'terms' ], v ) }
				/>
			) }
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Order', 'wp-smart-flexslider' ) }
				value={ source.orderby }
				options={ opts( ORDER ) }
				onChange={ ( v ) => set( [ 'source', 'orderby' ], v ) }
			/>
			{ ! [
				'rand',
				'date',
				'modified',
				'comment_count',
				'popularity',
			].includes( source.orderby ) && (
				<Segmented
					label={ __( 'Direction', 'wp-smart-flexslider' ) }
					value={ source.order }
					options={ [
						{
							value: 'ASC',
							label: __( 'Ascending', 'wp-smart-flexslider' ),
						},
						{
							value: 'DESC',
							label: __( 'Descending', 'wp-smart-flexslider' ),
						},
					] }
					onChange={ ( v ) => set( [ 'source', 'order' ], v ) }
				/>
			) }
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Number of items', 'wp-smart-flexslider' ) }
				value={ source.count }
				min={ 1 }
				max={ 30 }
				onChange={ ( v ) =>
					set( [ 'source', 'count' ], v || 1, 'count' )
				}
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Skip the first', 'wp-smart-flexslider' ) }
				value={ source.offset }
				min={ 0 }
				max={ 100 }
				onChange={ ( v ) =>
					set( [ 'source', 'offset' ], v || 0, 'offset' )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={
					isProduct
						? __( 'Featured products only', 'wp-smart-flexslider' )
						: __( 'Sticky posts only', 'wp-smart-flexslider' )
				}
				checked={ source.featured_only }
				onChange={ ( v ) => set( [ 'source', 'featured_only' ], v ) }
			/>
			{ 'post' === source.post_type && ! source.featured_only && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Sticky posts first', 'wp-smart-flexslider' ) }
					checked={ source.sticky_first }
					onChange={ ( v ) => set( [ 'source', 'sticky_first' ], v ) }
				/>
			) }
			<fieldset className="slidecraft-checks">
				<legend>
					{ __( 'Show on each slide', 'wp-smart-flexslider' ) }
				</legend>
				{ Object.keys( SHOW )
					.filter(
						( key ) =>
							isProduct || ! [ 'price', 'rating' ].includes( key )
					)
					.map( ( key ) => (
						<CheckboxControl
							__nextHasNoMarginBottom
							key={ key }
							label={ SHOW[ key ] }
							checked={ source.show.includes( key ) }
							onChange={ ( on ) =>
								set(
									[ 'source', 'show' ],
									on
										? [ ...source.show, key ]
										: source.show.filter(
												( k ) => k !== key
										  )
								)
							}
						/>
					) ) }
			</fieldset>
			{ source.show.includes( 'button' ) && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Button label', 'wp-smart-flexslider' ) }
					placeholder={
						isProduct
							? __( 'View product', 'wp-smart-flexslider' )
							: __( 'Read more', 'wp-smart-flexslider' )
					}
					value={ source.button_label }
					onChange={ ( v ) =>
						set( [ 'source', 'button_label' ], v, 'button_label' )
					}
				/>
			) }
		</PanelBody>
	);
}

function BackgroundFields( { slide, set } ) {
	const bg = slide.background;
	const isVideo = 'video' === slide.type;
	const BG = {
		image: __( 'Image', 'wp-smart-flexslider' ),
		color: __( 'Colour', 'wp-smart-flexslider' ),
		gradient: __( 'Gradient', 'wp-smart-flexslider' ),
		none: __( 'None', 'wp-smart-flexslider' ),
	};
	const isPosts = 'posts' === slide.type;
	return (
		<PanelBody
			title={
				isVideo
					? __( 'Video', 'wp-smart-flexslider' )
					: __( 'Background', 'wp-smart-flexslider' )
			}
		>
			{ isVideo ? (
				<>
					<Segmented
						label={ __( 'Source', 'wp-smart-flexslider' ) }
						value={ bg.video.source }
						options={ opts( {
							youtube: 'YouTube',
							vimeo: 'Vimeo',
							file: __( 'File', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) =>
							set( [ 'background', 'video', 'source' ], v )
						}
					/>
					{ 'file' === bg.video.source ? (
						<MediaField
							label={ __( 'Video file', 'wp-smart-flexslider' ) }
							type="video"
							value={ bg.video.file_id }
							onChange={ ( v ) =>
								set( [ 'background', 'video', 'file_id' ], v )
							}
						/>
					) : (
						<TextControl
							__nextHasNoMarginBottom
							type="url"
							label={ __( 'Video URL', 'wp-smart-flexslider' ) }
							placeholder={
								'youtube' === bg.video.source
									? 'https://www.youtube.com/watch?v=…'
									: 'https://vimeo.com/…'
							}
							value={ bg.video.url }
							onChange={ ( v ) =>
								set(
									[ 'background', 'video', 'url' ],
									v,
									'video-url'
								)
							}
						/>
					) }
					<MediaField
						label={ __( 'Poster image', 'wp-smart-flexslider' ) }
						help={ __(
							'Shown until the video starts, and on slow connections.',
							'wp-smart-flexslider'
						) }
						value={ bg.video.poster_id }
						onChange={ ( v ) =>
							set( [ 'background', 'video', 'poster_id' ], v )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Muted', 'wp-smart-flexslider' ) }
						help={ __(
							'Browsers only autoplay muted video.',
							'wp-smart-flexslider'
						) }
						checked={ bg.video.muted }
						onChange={ ( v ) =>
							set( [ 'background', 'video', 'muted' ], v )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Loop', 'wp-smart-flexslider' ) }
						checked={ bg.video.loop }
						onChange={ ( v ) =>
							set( [ 'background', 'video', 'loop' ], v )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Player controls', 'wp-smart-flexslider' ) }
						checked={ bg.video.controls }
						onChange={ ( v ) =>
							set( [ 'background', 'video', 'controls' ], v )
						}
					/>
				</>
			) : (
				<>
					<Segmented
						value={ bg.type }
						options={ opts(
							isPosts
								? {
										image: __(
											'Featured image',
											'wp-smart-flexslider'
										),
										color: BG.color,
										gradient: BG.gradient,
										none: BG.none,
								  }
								: BG
						) }
						onChange={ ( v ) => set( [ 'background', 'type' ], v ) }
					/>
					{ 'image' === bg.type && ! isPosts && (
						<>
							<MediaField
								label={ __( 'Image', 'wp-smart-flexslider' ) }
								value={ bg.image_id }
								onChange={ ( v ) =>
									set( [ 'background', 'image_id' ], v )
								}
							/>
							<FocalField
								imageId={ bg.image_id }
								value={ bg.focal }
								onChange={ ( v ) =>
									set( [ 'background', 'focal' ], v, 'focal' )
								}
							/>
						</>
					) }
					{ 'image' === bg.type && (
						<>
							<Segmented
								label={ __( 'Fit', 'wp-smart-flexslider' ) }
								value={ bg.fit }
								options={ opts( {
									cover: __( 'Fill', 'wp-smart-flexslider' ),
									contain: __(
										'Fit whole image',
										'wp-smart-flexslider'
									),
								} ) }
								onChange={ ( v ) =>
									set( [ 'background', 'fit' ], v )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Slow zoom (Ken Burns)',
									'wp-smart-flexslider'
								) }
								checked={ bg.ken_burns }
								onChange={ ( v ) =>
									set( [ 'background', 'ken_burns' ], v )
								}
							/>
						</>
					) }
					{ ( 'color' === bg.type || 'image' === bg.type ) && (
						<ColorField
							label={
								'image' === bg.type
									? __(
											'Colour while loading',
											'wp-smart-flexslider'
									  )
									: __( 'Colour', 'wp-smart-flexslider' )
							}
							value={ bg.color }
							onChange={ ( v ) =>
								set( [ 'background', 'color' ], v, 'bg-color' )
							}
						/>
					) }
					{ 'gradient' === bg.type && (
						<>
							<ColorField
								label={ __( 'From', 'wp-smart-flexslider' ) }
								value={ bg.gradient.from }
								onChange={ ( v ) =>
									set(
										[ 'background', 'gradient', 'from' ],
										v,
										'g-from'
									)
								}
							/>
							<ColorField
								label={ __( 'To', 'wp-smart-flexslider' ) }
								value={ bg.gradient.to }
								onChange={ ( v ) =>
									set(
										[ 'background', 'gradient', 'to' ],
										v,
										'g-to'
									)
								}
							/>
							<RangeControl
								__nextHasNoMarginBottom
								label={ __( 'Angle', 'wp-smart-flexslider' ) }
								value={ bg.gradient.angle }
								min={ 0 }
								max={ 360 }
								onChange={ ( v ) =>
									set(
										[ 'background', 'gradient', 'angle' ],
										v || 0,
										'g-angle'
									)
								}
							/>
						</>
					) }
				</>
			) }
			{ 'none' !== bg.type && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Overlay opacity', 'wp-smart-flexslider' ) }
						help={ __(
							'Darkens the background so text stays readable.',
							'wp-smart-flexslider'
						) }
						value={ Math.round( bg.overlay.opacity * 100 ) }
						min={ 0 }
						max={ 95 }
						onChange={ ( v ) =>
							set(
								[ 'background', 'overlay', 'opacity' ],
								( v || 0 ) / 100,
								'overlay'
							)
						}
					/>
					{ bg.overlay.opacity > 0 && (
						<ColorField
							label={ __(
								'Overlay colour',
								'wp-smart-flexslider'
							) }
							value={ bg.overlay.color }
							onChange={ ( v ) =>
								set(
									[ 'background', 'overlay', 'color' ],
									v,
									'overlay-color'
								)
							}
						/>
					) }
				</>
			) }
		</PanelBody>
	);
}

export default function SlidePanel( { slide, set } ) {
	const t = slide.testimonial;
	return (
		<>
			<PanelBody title={ __( 'Slide', 'wp-smart-flexslider' ) }>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Type', 'wp-smart-flexslider' ) }
					value={ slide.type }
					options={ SLIDE_TYPES.map( ( type ) => ( {
						value: type.value,
						label: type.label,
					} ) ) }
					onChange={ ( v ) => set( [ 'type' ], v ) }
				/>
				{ 'posts' !== slide.type && (
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Name', 'wp-smart-flexslider' ) }
						help={ __(
							'Used in the slide list and as the caption in galleries and the lightbox.',
							'wp-smart-flexslider'
						) }
						value={ slide.title }
						onChange={ ( v ) => set( [ 'title' ], v, 'title' ) }
					/>
				) }
				{ [ 'image', 'logo', 'content' ].includes( slide.type ) &&
					'image' === slide.background.type && (
						<TextControl
							__nextHasNoMarginBottom
							label={ __(
								'Alternative text',
								'wp-smart-flexslider'
							) }
							help={ __(
								'Describe the image for people who can’t see it. Leave empty to use the media library text.',
								'wp-smart-flexslider'
							) }
							value={ slide.alt }
							onChange={ ( v ) => set( [ 'alt' ], v, 'alt' ) }
						/>
					) }
			</PanelBody>

			{ 'testimonial' === slide.type && (
				<PanelBody title={ __( 'Testimonial', 'wp-smart-flexslider' ) }>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Quote', 'wp-smart-flexslider' ) }
						value={ t.quote }
						rows={ 4 }
						onChange={ ( v ) =>
							set( [ 'testimonial', 'quote' ], v, 'quote' )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Name', 'wp-smart-flexslider' ) }
						value={ t.name }
						onChange={ ( v ) =>
							set( [ 'testimonial', 'name' ], v, 't-name' )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Role or company', 'wp-smart-flexslider' ) }
						value={ t.role }
						onChange={ ( v ) =>
							set( [ 'testimonial', 'role' ], v, 't-role' )
						}
					/>
					<MediaField
						label={ __( 'Photo', 'wp-smart-flexslider' ) }
						value={ t.avatar_id }
						onChange={ ( v ) =>
							set( [ 'testimonial', 'avatar_id' ], v )
						}
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Star rating', 'wp-smart-flexslider' ) }
						value={ t.rating }
						min={ 0 }
						max={ 5 }
						onChange={ ( v ) =>
							set( [ 'testimonial', 'rating' ], v || 0, 'rating' )
						}
					/>
				</PanelBody>
			) }

			{ 'logo' === slide.type && (
				<PanelBody title={ __( 'Logo', 'wp-smart-flexslider' ) }>
					<MediaField
						label={ __( 'Logo image', 'wp-smart-flexslider' ) }
						value={ slide.background.image_id }
						onChange={ ( v ) =>
							set( [ 'background', 'image_id' ], v )
						}
					/>
				</PanelBody>
			) }

			{ 'posts' === slide.type && (
				<SourceFields source={ slide.source } set={ set } />
			) }

			{ ! [ 'testimonial', 'logo' ].includes( slide.type ) && (
				<BackgroundFields slide={ slide } set={ set } />
			) }

			{ 'posts' !== slide.type && (
				<PanelBody
					title={ __( 'Link', 'wp-smart-flexslider' ) }
					initialOpen={ !! slide.link.url }
				>
					<TextControl
						__nextHasNoMarginBottom
						type="url"
						label={ __(
							'Link the whole slide to',
							'wp-smart-flexslider'
						) }
						placeholder="https://"
						value={ slide.link.url }
						onChange={ ( v ) =>
							set( [ 'link', 'url' ], v, 'link' )
						}
					/>
					{ slide.link.url && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Open in a new tab',
								'wp-smart-flexslider'
							) }
							checked={ slide.link.new_tab }
							onChange={ ( v ) =>
								set( [ 'link', 'new_tab' ], v )
							}
						/>
					) }
				</PanelBody>
			) }
		</>
	);
}
