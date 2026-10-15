/**
 * Slider-wide settings and the tablet/mobile overrides.
 */
import {
	PanelBody,
	TextControl,
	SelectControl,
	ToggleControl,
	RangeControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ColorField, Segmented, opts } from './controls';

const TYPES = {
	slider: __( 'Slider', 'wp-smart-flexslider' ),
	carousel: __( 'Carousel', 'wp-smart-flexslider' ),
	gallery: __( 'Gallery', 'wp-smart-flexslider' ),
	ticker: __( 'Ticker', 'wp-smart-flexslider' ),
};
const TYPE_HELP = {
	slider: __( 'One slide at a time.', 'wp-smart-flexslider' ),
	carousel: __(
		'Several slides in view, like cards.',
		'wp-smart-flexslider'
	),
	gallery: __(
		'Large images with captions below; pair with thumbnails and the lightbox.',
		'wp-smart-flexslider'
	),
	ticker: __(
		'A continuous, slowly moving strip, e.g. logos or headlines.',
		'wp-smart-flexslider'
	),
};
const EFFECTS = {
	slide: __( 'Slide', 'wp-smart-flexslider' ),
	fade: __( 'Fade', 'wp-smart-flexslider' ),
	coverflow: __( 'Coverflow (3D)', 'wp-smart-flexslider' ),
	cube: __( 'Cube', 'wp-smart-flexslider' ),
	flip: __( 'Flip', 'wp-smart-flexslider' ),
	cards: __( 'Cards', 'wp-smart-flexslider' ),
	creative: __( 'Creative', 'wp-smart-flexslider' ),
};
const RATIOS = {
	'21:9': '21:9',
	'16:9': '16:9',
	'3:2': '3:2',
	'4:3': '4:3',
	'1:1': '1:1',
	'4:5': '4:5',
	'3:4': '3:4',
	'9:16': '9:16',
};

export function SliderPanel( { settings: s, slides, set } ) {
	const multi = 'carousel' === s.type || 'ticker' === s.type;
	const effects = multi
		? { slide: EFFECTS.slide, coverflow: EFFECTS.coverflow }
		: EFFECTS;
	const nav = s.navigation;
	const hasLabels = slides.length;

	return (
		<>
			<PanelBody title={ __( 'Layout', 'wp-smart-flexslider' ) }>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Type', 'wp-smart-flexslider' ) }
					help={ TYPE_HELP[ s.type ] }
					value={ s.type }
					options={ opts( TYPES ) }
					onChange={ ( v ) => set( [ 'type' ], v ) }
				/>
				{ 'ticker' !== s.type && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Transition', 'wp-smart-flexslider' ) }
						value={ effects[ s.effect ] ? s.effect : 'slide' }
						options={ opts( effects ) }
						onChange={ ( v ) => set( [ 'effect' ], v ) }
					/>
				) }
				{ 'creative' === s.effect && ! multi && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Creative style', 'wp-smart-flexslider' ) }
						value={ s.creative }
						options={ opts( {
							'zoom-out': __( 'Zoom out', 'wp-smart-flexslider' ),
							push: __( 'Push', 'wp-smart-flexslider' ),
							rotate: __( 'Rotate', 'wp-smart-flexslider' ),
							stack: __( 'Stack', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) => set( [ 'creative' ], v ) }
					/>
				) }
				{ 'slide' === s.effect && 'ticker' !== s.type && (
					<Segmented
						label={ __( 'Direction', 'wp-smart-flexslider' ) }
						value={ s.direction }
						options={ opts( {
							horizontal: __(
								'Horizontal',
								'wp-smart-flexslider'
							),
							vertical: __( 'Vertical', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) => set( [ 'direction' ], v ) }
					/>
				) }
				<Segmented
					label={ __( 'Width', 'wp-smart-flexslider' ) }
					value={ s.width }
					options={ opts( {
						content: __( 'Content', 'wp-smart-flexslider' ),
						wide: __( 'Wide', 'wp-smart-flexslider' ),
						full: __( 'Full', 'wp-smart-flexslider' ),
					} ) }
					onChange={ ( v ) => set( [ 'width' ], v ) }
				/>
			</PanelBody>

			<PanelBody title={ __( 'Height', 'wp-smart-flexslider' ) }>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Height', 'wp-smart-flexslider' ) }
					value={ s.height.mode }
					options={ opts( {
						ratio: __( 'Aspect ratio', 'wp-smart-flexslider' ),
						fixed: __( 'Fixed (px)', 'wp-smart-flexslider' ),
						viewport: __(
							'Screen height (%)',
							'wp-smart-flexslider'
						),
						auto: __(
							'Match the first image',
							'wp-smart-flexslider'
						),
					} ) }
					onChange={ ( v ) => set( [ 'height', 'mode' ], v ) }
				/>
				{ 'ratio' === s.height.mode && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Aspect ratio', 'wp-smart-flexslider' ) }
						value={ s.height.ratio }
						options={ opts( RATIOS ) }
						onChange={ ( v ) => set( [ 'height', 'ratio' ], v ) }
					/>
				) }
				{ 'fixed' === s.height.mode && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Height (px)', 'wp-smart-flexslider' ) }
						value={ s.height.px }
						min={ 120 }
						max={ 2000 }
						step={ 10 }
						onChange={ ( v ) =>
							set( [ 'height', 'px' ], v || 120, 'px' )
						}
					/>
				) }
				{ 'viewport' === s.height.mode && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __(
							'Screen height (%)',
							'wp-smart-flexslider'
						) }
						value={ s.height.vh }
						min={ 20 }
						max={ 100 }
						onChange={ ( v ) =>
							set( [ 'height', 'vh' ], v || 20, 'vh' )
						}
					/>
				) }
			</PanelBody>

			{ 'ticker' !== s.type && (
				<PanelBody title={ __( 'Autoplay', 'wp-smart-flexslider' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Play automatically',
							'wp-smart-flexslider'
						) }
						help={ __(
							'A pause button is always shown so visitors can stop it.',
							'wp-smart-flexslider'
						) }
						checked={ s.autoplay.enabled }
						onChange={ ( v ) =>
							set( [ 'autoplay', 'enabled' ], v )
						}
					/>
					{ s.autoplay.enabled && (
						<>
							<RangeControl
								__nextHasNoMarginBottom
								label={ __(
									'Time per slide (seconds)',
									'wp-smart-flexslider'
								) }
								value={ s.autoplay.delay / 1000 }
								min={ 1 }
								max={ 30 }
								step={ 0.5 }
								onChange={ ( v ) =>
									set(
										[ 'autoplay', 'delay' ],
										Math.round( ( v || 1 ) * 1000 ),
										'delay'
									)
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Pause on hover',
									'wp-smart-flexslider'
								) }
								checked={ s.autoplay.pause_on_hover }
								onChange={ ( v ) =>
									set( [ 'autoplay', 'pause_on_hover' ], v )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Progress bar',
									'wp-smart-flexslider'
								) }
								checked={ s.autoplay.progress }
								onChange={ ( v ) =>
									set( [ 'autoplay', 'progress' ], v )
								}
							/>
						</>
					) }
				</PanelBody>
			) }

			{ 'ticker' === s.type && (
				<PanelBody title={ __( 'Ticker', 'wp-smart-flexslider' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Speed', 'wp-smart-flexslider' ) }
						value={ s.ticker.speed }
						min={ 5 }
						max={ 300 }
						onChange={ ( v ) =>
							set( [ 'ticker', 'speed' ], v || 5, 'ticker-speed' )
						}
					/>
					<Segmented
						label={ __( 'Moves', 'wp-smart-flexslider' ) }
						value={ s.ticker.direction }
						options={ opts( {
							left: __( 'Left', 'wp-smart-flexslider' ),
							right: __( 'Right', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) =>
							set( [ 'ticker', 'direction' ], v )
						}
					/>
				</PanelBody>
			) }

			{ multi && (
				<PanelBody
					title={
						'ticker' === s.type
							? __( 'Items', 'wp-smart-flexslider' )
							: __( 'Carousel', 'wp-smart-flexslider' )
					}
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Each slide sizes to its content',
							'wp-smart-flexslider'
						) }
						checked={ s.carousel.auto_width }
						onChange={ ( v ) =>
							set( [ 'carousel', 'auto_width' ], v )
						}
					/>
					{ ! s.carousel.auto_width && (
						<RangeControl
							__nextHasNoMarginBottom
							label={ __(
								'Slides in view (desktop)',
								'wp-smart-flexslider'
							) }
							value={ s.carousel.per_view }
							min={ 1 }
							max={ 8 }
							step={ 0.1 }
							onChange={ ( v ) =>
								set(
									[ 'carousel', 'per_view' ],
									v || 1,
									'per_view'
								)
							}
						/>
					) }
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Gap (px)', 'wp-smart-flexslider' ) }
						value={ s.carousel.gap }
						min={ 0 }
						max={ 120 }
						onChange={ ( v ) =>
							set( [ 'carousel', 'gap' ], v || 0, 'gap' )
						}
					/>
					{ 'carousel' === s.type && (
						<>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Centre the active slide',
									'wp-smart-flexslider'
								) }
								checked={ s.carousel.centered }
								onChange={ ( v ) =>
									set( [ 'carousel', 'centered' ], v )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Free scrolling',
									'wp-smart-flexslider'
								) }
								checked={ s.carousel.free_mode }
								onChange={ ( v ) =>
									set( [ 'carousel', 'free_mode' ], v )
								}
							/>
							<RangeControl
								__nextHasNoMarginBottom
								label={ __( 'Rows', 'wp-smart-flexslider' ) }
								help={
									s.carousel.rows > 1
										? __(
												'Loop is turned off with more than one row.',
												'wp-smart-flexslider'
										  )
										: undefined
								}
								value={ s.carousel.rows }
								min={ 1 }
								max={ 4 }
								onChange={ ( v ) =>
									set(
										[ 'carousel', 'rows' ],
										v || 1,
										'rows'
									)
								}
							/>
						</>
					) }
				</PanelBody>
			) }

			{ 'ticker' !== s.type && (
				<PanelBody
					title={ __( 'Navigation', 'wp-smart-flexslider' ) }
					initialOpen={ false }
				>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Arrows', 'wp-smart-flexslider' ) }
						checked={ nav.arrows }
						onChange={ ( v ) =>
							set( [ 'navigation', 'arrows' ], v )
						}
					/>
					{ nav.arrows && (
						<>
							<SelectControl
								__nextHasNoMarginBottom
								label={ __(
									'Arrow style',
									'wp-smart-flexslider'
								) }
								value={ nav.arrow_style }
								options={ opts( {
									circle: __(
										'Circle',
										'wp-smart-flexslider'
									),
									square: __(
										'Square',
										'wp-smart-flexslider'
									),
									minimal: __(
										'Minimal',
										'wp-smart-flexslider'
									),
									chevron: __(
										'Tall',
										'wp-smart-flexslider'
									),
									pill: __( 'Pill', 'wp-smart-flexslider' ),
									outline: __(
										'Outline',
										'wp-smart-flexslider'
									),
								} ) }
								onChange={ ( v ) =>
									set( [ 'navigation', 'arrow_style' ], v )
								}
							/>
							<Segmented
								label={ __(
									'Arrow position',
									'wp-smart-flexslider'
								) }
								value={ nav.arrow_position }
								options={ opts( {
									inside: __(
										'Inside',
										'wp-smart-flexslider'
									),
									outside: __(
										'Outside',
										'wp-smart-flexslider'
									),
								} ) }
								onChange={ ( v ) =>
									set( [ 'navigation', 'arrow_position' ], v )
								}
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Show arrows on hover only',
									'wp-smart-flexslider'
								) }
								checked={ nav.arrows_on_hover }
								onChange={ ( v ) =>
									set(
										[ 'navigation', 'arrows_on_hover' ],
										v
									)
								}
							/>
						</>
					) }
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Pagination', 'wp-smart-flexslider' ) }
						value={ nav.pagination }
						options={ opts( {
							bullets: __( 'Dots', 'wp-smart-flexslider' ),
							dynamic: __(
								'Dots (compact)',
								'wp-smart-flexslider'
							),
							fraction: __(
								'Numbers (2 / 5)',
								'wp-smart-flexslider'
							),
							progressbar: __(
								'Progress bar',
								'wp-smart-flexslider'
							),
							thumbnails: __(
								'Thumbnails',
								'wp-smart-flexslider'
							),
							none: __( 'None', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) =>
							set( [ 'navigation', 'pagination' ], v )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Swipe and drag', 'wp-smart-flexslider' ) }
						checked={ nav.draggable }
						onChange={ ( v ) =>
							set( [ 'navigation', 'draggable' ], v )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Keyboard arrows', 'wp-smart-flexslider' ) }
						checked={ nav.keyboard }
						onChange={ ( v ) =>
							set( [ 'navigation', 'keyboard' ], v )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Mouse wheel', 'wp-smart-flexslider' ) }
						checked={ nav.mousewheel }
						onChange={ ( v ) =>
							set( [ 'navigation', 'mousewheel' ], v )
						}
					/>
				</PanelBody>
			) }

			<PanelBody
				title={ __( 'Style', 'wp-smart-flexslider' ) }
				initialOpen={ false }
			>
				<ColorField
					label={ __( 'Accent', 'wp-smart-flexslider' ) }
					value={ s.style.accent }
					onChange={ ( v ) =>
						set( [ 'style', 'accent' ], v, 'accent' )
					}
				/>
				<ColorField
					label={ __( 'Text', 'wp-smart-flexslider' ) }
					value={ s.style.text_color }
					onChange={ ( v ) =>
						set( [ 'style', 'text_color' ], v, 'text_color' )
					}
				/>
				<ColorField
					label={ __( 'Arrow icon', 'wp-smart-flexslider' ) }
					value={ s.style.arrow_color }
					onChange={ ( v ) =>
						set( [ 'style', 'arrow_color' ], v, 'arrow_color' )
					}
				/>
				<ColorField
					label={ __( 'Arrow background', 'wp-smart-flexslider' ) }
					value={ s.style.arrow_bg }
					onChange={ ( v ) =>
						set( [ 'style', 'arrow_bg' ], v, 'arrow_bg' )
					}
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Corner radius (px)', 'wp-smart-flexslider' ) }
					value={ s.style.radius }
					min={ 0 }
					max={ 64 }
					onChange={ ( v ) =>
						set( [ 'style', 'radius' ], v || 0, 'radius' )
					}
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Font', 'wp-smart-flexslider' ) }
					value={ s.style.font }
					options={ opts( {
						theme: __( 'Theme font', 'wp-smart-flexslider' ),
						inter: 'Inter',
						montserrat: 'Montserrat',
						playfair: 'Playfair Display',
						lora: 'Lora',
					} ) }
					onChange={ ( v ) => set( [ 'style', 'font' ], v ) }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Captions', 'wp-smart-flexslider' ) }
					value={ s.style.caption_style }
					options={ opts( {
						overlay: __(
							'Layers on the slide',
							'wp-smart-flexslider'
						),
						below: __(
							'Slide name below the image',
							'wp-smart-flexslider'
						),
						none: __( 'Hide layers', 'wp-smart-flexslider' ),
					} ) }
					onChange={ ( v ) => set( [ 'style', 'caption_style' ], v ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Drop shadow', 'wp-smart-flexslider' ) }
					checked={ s.style.shadow }
					onChange={ ( v ) => set( [ 'style', 'shadow' ], v ) }
				/>
				{ slides.some( ( slide ) => 'logo' === slide.type ) && (
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Grey logos (colour on hover)',
							'wp-smart-flexslider'
						) }
						checked={ s.style.gray_logos }
						onChange={ ( v ) =>
							set( [ 'style', 'gray_logos' ], v )
						}
					/>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'More', 'wp-smart-flexslider' ) }
				initialOpen={ false }
			>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __(
						'Transition speed (ms)',
						'wp-smart-flexslider'
					) }
					value={ s.speed }
					min={ 100 }
					max={ 5000 }
					step={ 50 }
					onChange={ ( v ) => set( [ 'speed' ], v || 100, 'speed' ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Loop', 'wp-smart-flexslider' ) }
					checked={ s.loop }
					onChange={ ( v ) => set( [ 'loop' ], v ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Random order', 'wp-smart-flexslider' ) }
					checked={ s.random }
					onChange={ ( v ) => set( [ 'random' ], v ) }
				/>
				{ hasLabels > 1 && ! s.random && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Start at slide', 'wp-smart-flexslider' ) }
						value={ s.start + 1 }
						min={ 1 }
						max={ slides.length }
						onChange={ ( v ) =>
							set( [ 'start' ], ( v || 1 ) - 1, 'start' )
						}
					/>
				) }
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Open images in a lightbox',
						'wp-smart-flexslider'
					) }
					checked={ s.lightbox }
					onChange={ ( v ) => set( [ 'lightbox' ], v ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Accessible name', 'wp-smart-flexslider' ) }
					help={ __(
						'Read by screen readers. Leave empty to use the slider title.',
						'wp-smart-flexslider'
					) }
					value={ s.label }
					onChange={ ( v ) => set( [ 'label' ], v, 'label' ) }
				/>
			</PanelBody>
		</>
	);
}

export function ResponsivePanel( { breakpoints, type, set } ) {
	const multi = 'carousel' === type || 'ticker' === type;
	const devices = [
		{ key: 'tablet', title: __( 'Tablet', 'wp-smart-flexslider' ) },
		{ key: 'mobile', title: __( 'Mobile', 'wp-smart-flexslider' ) },
	];
	return devices.map( ( { key, title } ) => {
		const bp = breakpoints[ key ];
		const k = ( name ) => `${ key }-${ name }`;
		return (
			<PanelBody key={ key } title={ title }>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __(
						'Applies up to (px wide)',
						'wp-smart-flexslider'
					) }
					value={ bp.max }
					min={ 320 }
					max={ 1600 }
					step={ 1 }
					onChange={ ( v ) =>
						set( [ key, 'max' ], v || 320, k( 'max' ) )
					}
				/>
				{ multi && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Slides in view', 'wp-smart-flexslider' ) }
						value={ bp.per_view }
						min={ 1 }
						max={ 8 }
						step={ 0.1 }
						onChange={ ( v ) =>
							set( [ key, 'per_view' ], v || 1, k( 'per_view' ) )
						}
					/>
				) }
				{ multi && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Gap (px)', 'wp-smart-flexslider' ) }
						value={ bp.gap }
						min={ 0 }
						max={ 120 }
						onChange={ ( v ) =>
							set( [ key, 'gap' ], v || 0, k( 'gap' ) )
						}
					/>
				) }
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Fixed height (px)', 'wp-smart-flexslider' ) }
					help={ __(
						'0 keeps the desktop height setting.',
						'wp-smart-flexslider'
					) }
					value={ bp.height_px }
					min={ 0 }
					max={ 2000 }
					step={ 10 }
					onChange={ ( v ) =>
						set( [ key, 'height_px' ], v || 0, k( 'height' ) )
					}
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Text size (%)', 'wp-smart-flexslider' ) }
					value={ Math.round( bp.font_scale * 100 ) }
					min={ 40 }
					max={ 150 }
					step={ 5 }
					onChange={ ( v ) =>
						set(
							[ key, 'font_scale' ],
							( v || 40 ) / 100,
							k( 'scale' )
						)
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Hide arrows', 'wp-smart-flexslider' ) }
					checked={ bp.hide_arrows }
					onChange={ ( v ) => set( [ key, 'hide_arrows' ], v ) }
				/>
			</PanelBody>
		);
	} );
}
