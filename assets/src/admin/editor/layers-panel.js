/**
 * Layers of the selected slide: list, add, reorder, duplicate, delete, and the selected layer's settings.
 */
import {
	PanelBody,
	TextControl,
	TextareaControl,
	SelectControl,
	ToggleControl,
	RangeControl,
	CheckboxControl,
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Dashicon,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { plus, chevronUp, chevronDown, copy, trash } from '@wordpress/icons';
import { LAYER_TYPES, MAX_LAYERS, plain, typeLabel, settings } from '../model';
import {
	MediaField,
	ColorField,
	AnchorPicker,
	Segmented,
	opts,
} from './controls';

const ANIMATION_LABELS = {
	none: __( 'None', 'wp-smart-flexslider' ),
	fade: __( 'Fade', 'wp-smart-flexslider' ),
	'fade-up': __( 'Fade up', 'wp-smart-flexslider' ),
	'fade-down': __( 'Fade down', 'wp-smart-flexslider' ),
	'fade-left': __( 'Fade from right', 'wp-smart-flexslider' ),
	'fade-right': __( 'Fade from left', 'wp-smart-flexslider' ),
	'zoom-in': __( 'Zoom in', 'wp-smart-flexslider' ),
	'zoom-out': __( 'Zoom out', 'wp-smart-flexslider' ),
	blur: __( 'Blur in', 'wp-smart-flexslider' ),
	typewriter: __( 'Typewriter', 'wp-smart-flexslider' ),
};

const SIZES = { s: 'S', m: 'M', l: 'L', xl: 'XL', xxl: 'XXL' };

function layerName( layer ) {
	if ( 'image' === layer.type ) {
		return plain( layer.text ) || __( 'Image', 'wp-smart-flexslider' );
	}
	if ( 'countdown' === layer.type ) {
		return layer.date
			? layer.date.replace( 'T', ' ' )
			: __( 'Countdown', 'wp-smart-flexslider' );
	}
	return plain( layer.text ) || typeLabel( LAYER_TYPES, layer.type );
}

function LayerSettings( { layer, set } ) {
	const isText = [ 'heading', 'text', 'button' ].includes( layer.type );
	const animations =
		( settings.defaults && settings.defaults.animations ) ||
		Object.keys( ANIMATION_LABELS );
	return (
		<>
			<PanelBody title={ typeLabel( LAYER_TYPES, layer.type ) }>
				{ isText && (
					<TextareaControl
						__nextHasNoMarginBottom
						label={
							'button' === layer.type
								? __( 'Label', 'wp-smart-flexslider' )
								: __( 'Text', 'wp-smart-flexslider' )
						}
						help={
							'button' === layer.type
								? undefined
								: __(
										'Simple formatting is allowed: <strong>, <em>, <a href>, <br>.',
										'wp-smart-flexslider'
								  )
						}
						rows={
							'heading' === layer.type || 'button' === layer.type
								? 2
								: 3
						}
						value={ layer.text }
						onChange={ ( v ) => set( [ 'text' ], v, 'text' ) }
					/>
				) }
				{ 'heading' === layer.type && (
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'HTML tag', 'wp-smart-flexslider' ) }
						help={ __(
							'Use one H1 per page at most; most slider headings should be H2.',
							'wp-smart-flexslider'
						) }
						value={ layer.level }
						options={ opts( {
							h1: 'H1',
							h2: 'H2',
							h3: 'H3',
							h4: 'H4',
							p: __( 'Paragraph', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) => set( [ 'level' ], v ) }
					/>
				) }
				{ [ 'heading', 'text', 'countdown' ].includes( layer.type ) && (
					<Segmented
						label={ __( 'Size', 'wp-smart-flexslider' ) }
						value={ layer.size }
						options={ opts( SIZES ) }
						onChange={ ( v ) => set( [ 'size' ], v ) }
					/>
				) }
				{ 'button' === layer.type && (
					<Segmented
						label={ __( 'Style', 'wp-smart-flexslider' ) }
						value={ layer.variant }
						options={ opts( {
							primary: __( 'Filled', 'wp-smart-flexslider' ),
							secondary: __( 'Light', 'wp-smart-flexslider' ),
							outline: __( 'Outline', 'wp-smart-flexslider' ),
							link: __( 'Link', 'wp-smart-flexslider' ),
						} ) }
						onChange={ ( v ) => set( [ 'variant' ], v ) }
					/>
				) }
				{ 'image' === layer.type && (
					<>
						<MediaField
							label={ __( 'Image', 'wp-smart-flexslider' ) }
							value={ layer.image_id }
							onChange={ ( v ) => set( [ 'image_id' ], v ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __(
								'Alternative text',
								'wp-smart-flexslider'
							) }
							value={ layer.text }
							onChange={ ( v ) => set( [ 'text' ], v, 'text' ) }
						/>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Width (px)', 'wp-smart-flexslider' ) }
							value={ layer.width }
							min={ 16 }
							max={ 1200 }
							onChange={ ( v ) =>
								set( [ 'width' ], v || 16, 'width' )
							}
						/>
					</>
				) }
				{ 'countdown' === layer.type && (
					<TextControl
						__nextHasNoMarginBottom
						type="datetime-local"
						label={ __( 'Counts down to', 'wp-smart-flexslider' ) }
						help={ __(
							'In the site’s time zone.',
							'wp-smart-flexslider'
						) }
						value={ layer.date }
						onChange={ ( v ) => set( [ 'date' ], v, 'date' ) }
					/>
				) }
				{ [ 'button', 'image' ].includes( layer.type ) && (
					<>
						<TextControl
							__nextHasNoMarginBottom
							type="url"
							label={ __( 'Link', 'wp-smart-flexslider' ) }
							placeholder="https://"
							value={ layer.url }
							onChange={ ( v ) => set( [ 'url' ], v, 'url' ) }
						/>
						{ layer.url && (
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __(
									'Open in a new tab',
									'wp-smart-flexslider'
								) }
								checked={ layer.new_tab }
								onChange={ ( v ) => set( [ 'new_tab' ], v ) }
							/>
						) }
					</>
				) }
				{ 'image' !== layer.type && (
					<ColorField
						label={ __( 'Colour', 'wp-smart-flexslider' ) }
						value={ layer.color }
						clearable
						onChange={ ( v ) => set( [ 'color' ], v, 'color' ) }
					/>
				) }
			</PanelBody>

			<PanelBody title={ __( 'Position', 'wp-smart-flexslider' ) }>
				<div className="slidecraft-position">
					<AnchorPicker
						value={ layer.position.anchor }
						onChange={ ( v ) => set( [ 'position', 'anchor' ], v ) }
					/>
					<p className="slidecraft-help">
						{ __(
							'Layers with the same position stack in a column. Drag a layer in the preview to fine-tune it.',
							'wp-smart-flexslider'
						) }
					</p>
				</div>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __(
						'Horizontal offset (%)',
						'wp-smart-flexslider'
					) }
					value={ layer.position.x }
					min={ -50 }
					max={ 50 }
					onChange={ ( v ) =>
						set( [ 'position', 'x' ], v || 0, 'x' )
					}
					allowReset
					resetFallbackValue={ 0 }
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Vertical offset (%)', 'wp-smart-flexslider' ) }
					value={ layer.position.y }
					min={ -50 }
					max={ 50 }
					onChange={ ( v ) =>
						set( [ 'position', 'y' ], v || 0, 'y' )
					}
					allowReset
					resetFallbackValue={ 0 }
				/>
				<Segmented
					label={ __( 'Text align', 'wp-smart-flexslider' ) }
					value={ layer.align }
					options={ opts( {
						left: __( 'Left', 'wp-smart-flexslider' ),
						center: __( 'Centre', 'wp-smart-flexslider' ),
						right: __( 'Right', 'wp-smart-flexslider' ),
					} ) }
					onChange={ ( v ) => set( [ 'align' ], v ) }
				/>
				{ 'image' !== layer.type && (
					<RangeControl
						__nextHasNoMarginBottom
						label={ __(
							'Maximum width (px)',
							'wp-smart-flexslider'
						) }
						value={ layer.max_width }
						min={ 80 }
						max={ 1600 }
						step={ 10 }
						onChange={ ( v ) =>
							set( [ 'max_width' ], v || 80, 'max_width' )
						}
					/>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'Animation', 'wp-smart-flexslider' ) }
				initialOpen={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Entrance', 'wp-smart-flexslider' ) }
					value={ layer.animation.in }
					options={ animations.map( ( a ) => ( {
						value: a,
						label: ANIMATION_LABELS[ a ] || a,
					} ) ) }
					onChange={ ( v ) => set( [ 'animation', 'in' ], v ) }
				/>
				{ 'none' !== layer.animation.in && (
					<>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Delay (ms)', 'wp-smart-flexslider' ) }
							value={ layer.animation.delay }
							min={ 0 }
							max={ 5000 }
							step={ 50 }
							onChange={ ( v ) =>
								set( [ 'animation', 'delay' ], v || 0, 'delay' )
							}
						/>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __(
								'Duration (ms)',
								'wp-smart-flexslider'
							) }
							value={ layer.animation.duration }
							min={ 100 }
							max={ 4000 }
							step={ 50 }
							onChange={ ( v ) =>
								set(
									[ 'animation', 'duration' ],
									v || 100,
									'duration'
								)
							}
						/>
					</>
				) }
			</PanelBody>

			<PanelBody
				title={ __( 'Visibility', 'wp-smart-flexslider' ) }
				initialOpen={ layer.hide_on.length > 0 }
			>
				{ opts( {
					desktop: __( 'Hide on desktop', 'wp-smart-flexslider' ),
					tablet: __( 'Hide on tablet', 'wp-smart-flexslider' ),
					mobile: __( 'Hide on mobile', 'wp-smart-flexslider' ),
				} ).map( ( device ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ device.value }
						label={ device.label }
						checked={ layer.hide_on.includes( device.value ) }
						onChange={ ( on ) =>
							set(
								[ 'hide_on' ],
								on
									? [ ...layer.hide_on, device.value ]
									: layer.hide_on.filter(
											( d ) => d !== device.value
									  )
							)
						}
					/>
				) ) }
			</PanelBody>
		</>
	);
}

export default function LayersPanel( {
	slide,
	layerId,
	onSelect,
	onAdd,
	onMove,
	onDuplicate,
	onDelete,
	set,
} ) {
	const layers = slide.layers;
	const index = layers.findIndex( ( l ) => l.id === layerId );
	const layer = layers[ index ];
	const canAdd = layers.length < MAX_LAYERS;

	return (
		<>
			<div className="slidecraft-layers">
				<div className="slidecraft-layers__header">
					<h2>{ __( 'Layers', 'wp-smart-flexslider' ) }</h2>
					<DropdownMenu
						icon={ plus }
						label={ __( 'Add layer', 'wp-smart-flexslider' ) }
						toggleProps={ {
							variant: 'secondary',
							size: 'small',
							disabled: ! canAdd,
						} }
					>
						{ ( { onClose } ) => (
							<MenuGroup
								label={ __(
									'Add layer',
									'wp-smart-flexslider'
								) }
							>
								{ LAYER_TYPES.map( ( type ) => (
									<MenuItem
										key={ type.value }
										icon={ <Dashicon icon={ type.icon } /> }
										onClick={ () => {
											onClose();
											onAdd( type.value );
										} }
									>
										{ type.label }
									</MenuItem>
								) ) }
							</MenuGroup>
						) }
					</DropdownMenu>
				</div>
				{ 'posts' === slide.type && (
					<p className="slidecraft-help">
						{ __(
							'On dynamic slides, layers only set the style: the first heading, text and button style the generated title, excerpt and button.',
							'wp-smart-flexslider'
						) }
					</p>
				) }
				{ ! layers.length && (
					<p className="slidecraft-help">
						{ __(
							'Add a heading, text, button, image or countdown on top of the slide.',
							'wp-smart-flexslider'
						) }
					</p>
				) }
				<ol className="slidecraft-layers__list">
					{ layers.map( ( item, i ) => (
						<li
							key={ item.id }
							className={ item.id === layerId ? 'is-active' : '' }
						>
							<button
								type="button"
								className="slidecraft-layers__select"
								onClick={ () =>
									onSelect(
										item.id === layerId ? '' : item.id
									)
								}
								aria-expanded={ item.id === layerId }
							>
								<Dashicon
									icon={
										(
											LAYER_TYPES.find(
												( t ) => t.value === item.type
											) || {}
										).icon
									}
								/>
								<span>{ layerName( item ) }</span>
							</button>
							<span className="slidecraft-layers__actions">
								<Button
									size="small"
									icon={ chevronUp }
									label={ __(
										'Move up',
										'wp-smart-flexslider'
									) }
									disabled={ 0 === i }
									onClick={ () => onMove( item.id, -1 ) }
								/>
								<Button
									size="small"
									icon={ chevronDown }
									label={ __(
										'Move down',
										'wp-smart-flexslider'
									) }
									disabled={ i === layers.length - 1 }
									onClick={ () => onMove( item.id, 1 ) }
								/>
								<Button
									size="small"
									icon={ copy }
									label={ __(
										'Duplicate',
										'wp-smart-flexslider'
									) }
									disabled={ ! canAdd }
									onClick={ () => onDuplicate( item.id ) }
								/>
								<Button
									size="small"
									icon={ trash }
									isDestructive
									label={ sprintf(
										/* translators: %s: layer name */
										__(
											'Delete %s',
											'wp-smart-flexslider'
										),
										layerName( item )
									) }
									onClick={ () => onDelete( item.id ) }
								/>
							</span>
						</li>
					) ) }
				</ol>
			</div>
			{ layer && (
				<LayerSettings
					key={ layer.id }
					layer={ layer }
					set={ ( path, value, key ) =>
						set(
							[ 'layers', index, ...path ],
							value,
							key && `${ layer.id }-${ key }`
						)
					}
				/>
			) }
		</>
	);
}
