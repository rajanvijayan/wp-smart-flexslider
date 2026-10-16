/**
 * Form controls shared by the inspector panels.
 */
import {
	Button,
	BaseControl,
	Dropdown,
	ColorPicker,
	ColorIndicator,
	FocalPointPicker,
} from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import { rememberMedia, useMedia } from '../api';

/**
 * Pick an attachment from the media library.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.label    Label.
 * @param {number}   props.value    Attachment ID.
 * @param {Function} props.onChange Called with the new ID (0 to remove).
 * @param {string}   props.type     image or video.
 * @param {string}   props.help     Help text.
 * @return {Element} Control.
 */
export function MediaField( { label, value, onChange, type = 'image', help } ) {
	const item = useMedia( value );
	const id = useInstanceId( MediaField, 'slidecraft-media' );

	const open = () => {
		const frame = window.wp.media( {
			title: label,
			library: { type },
			multiple: false,
			button: { text: __( 'Use this file', 'wp-smart-flexslider' ) },
		} );
		frame.on( 'open', () => {
			if ( value ) {
				const attachment = window.wp.media.attachment( value );
				attachment.fetch();
				frame.state().get( 'selection' ).add( attachment );
			}
		} );
		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			rememberMedia( attachment );
			onChange( attachment.id );
		} );
		frame.open();
	};

	return (
		<BaseControl
			id={ id }
			label={ label }
			help={ help }
			__nextHasNoMarginBottom
		>
			<div className="slidecraft-media">
				{ value ? (
					<button
						type="button"
						className="slidecraft-media__preview"
						onClick={ open }
						id={ id }
					>
						{ item &&
							item.mime &&
							item.mime.startsWith( 'image' ) && (
								<img src={ item.thumb } alt="" />
							) }
						{ item &&
							item.mime &&
							! item.mime.startsWith( 'image' ) && (
								<span className="dashicons dashicons-media-video" />
							) }
						{ ! item && (
							<span className="slidecraft-media__loading" />
						) }
					</button>
				) : (
					<Button variant="secondary" onClick={ open } id={ id }>
						{ 'video' === type
							? __( 'Choose video', 'wp-smart-flexslider' )
							: __( 'Choose image', 'wp-smart-flexslider' ) }
					</Button>
				) }
				{ !! value && (
					<div className="slidecraft-media__actions">
						<Button
							variant="secondary"
							size="small"
							onClick={ open }
						>
							{ __( 'Replace', 'wp-smart-flexslider' ) }
						</Button>
						<Button
							variant="tertiary"
							size="small"
							isDestructive
							onClick={ () => onChange( 0 ) }
						>
							{ __( 'Remove', 'wp-smart-flexslider' ) }
						</Button>
					</div>
				) }
			</div>
		</BaseControl>
	);
}

/**
 * Focal point picker for a background image.
 *
 * @param {Object}   props          Props.
 * @param {number}   props.imageId  Attachment ID.
 * @param {Object}   props.value    { x, y }.
 * @param {Function} props.onChange Called with { x, y }.
 * @return {Element|null} Control.
 */
export function FocalField( { imageId, value, onChange } ) {
	const item = useMedia( imageId );
	if ( ! item || ! item.url ) {
		return null;
	}
	return (
		<FocalPointPicker
			__nextHasNoMarginBottom
			label={ __( 'Focal point', 'wp-smart-flexslider' ) }
			help={ __(
				'The part of the image that stays visible when it is cropped.',
				'wp-smart-flexslider'
			) }
			url={ item.url }
			value={ value }
			onChange={ ( v ) =>
				onChange( {
					x: Math.round( v.x * 100 ) / 100,
					y: Math.round( v.y * 100 ) / 100,
				} )
			}
		/>
	);
}

/**
 * Colour with a picker in a popover. Empty value allowed when `clearable`.
 *
 * @param {Object}   props           Props.
 * @param {string}   props.label     Label.
 * @param {string}   props.value     Hex colour.
 * @param {Function} props.onChange  Called with the new colour.
 * @param {boolean}  props.clearable Allow "default".
 * @return {Element} Control.
 */
export function ColorField( { label, value, onChange, clearable = false } ) {
	return (
		<div className="slidecraft-color">
			<span className="slidecraft-color__label">{ label }</span>
			<Dropdown
				popoverProps={ { placement: 'left-start' } }
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						className="slidecraft-color__toggle"
						onClick={ onToggle }
						aria-expanded={ isOpen }
						aria-label={ label }
					>
						<ColorIndicator colorValue={ value || 'transparent' } />
						<span>
							{ value || __( 'Default', 'wp-smart-flexslider' ) }
						</span>
					</Button>
				) }
				renderContent={ () => (
					<div className="slidecraft-color__popover">
						<ColorPicker
							color={ value || '#ffffff' }
							onChange={ onChange }
							enableAlpha={ false }
						/>
						{ clearable && value && (
							<Button
								variant="tertiary"
								size="small"
								onClick={ () => onChange( '' ) }
							>
								{ __( 'Use default', 'wp-smart-flexslider' ) }
							</Button>
						) }
					</div>
				) }
			/>
		</div>
	);
}

const ANCHORS = [
	'top-left',
	'top-center',
	'top-right',
	'center-left',
	'center',
	'center-right',
	'bottom-left',
	'bottom-center',
	'bottom-right',
];
const ANCHOR_LABELS = {
	'top-left': __( 'Top left', 'wp-smart-flexslider' ),
	'top-center': __( 'Top centre', 'wp-smart-flexslider' ),
	'top-right': __( 'Top right', 'wp-smart-flexslider' ),
	'center-left': __( 'Centre left', 'wp-smart-flexslider' ),
	center: __( 'Centre', 'wp-smart-flexslider' ),
	'center-right': __( 'Centre right', 'wp-smart-flexslider' ),
	'bottom-left': __( 'Bottom left', 'wp-smart-flexslider' ),
	'bottom-center': __( 'Bottom centre', 'wp-smart-flexslider' ),
	'bottom-right': __( 'Bottom right', 'wp-smart-flexslider' ),
};

/**
 * Nine-point position grid.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.value    Anchor.
 * @param {Function} props.onChange Called with the new anchor.
 * @return {Element} Control.
 */
export function AnchorPicker( { value, onChange } ) {
	return (
		<div
			className="slidecraft-anchor"
			role="radiogroup"
			aria-label={ __( 'Position', 'wp-smart-flexslider' ) }
		>
			{ ANCHORS.map( ( anchor ) => (
				<button
					key={ anchor }
					type="button"
					role="radio"
					aria-checked={ value === anchor }
					aria-label={ ANCHOR_LABELS[ anchor ] }
					title={ ANCHOR_LABELS[ anchor ] }
					className={ value === anchor ? 'is-active' : '' }
					onClick={ () => onChange( anchor ) }
				/>
			) ) }
		</div>
	);
}

/**
 * A row of mutually exclusive buttons.
 *
 * @param {Object}   props          Props.
 * @param {string}   props.label    Label.
 * @param {string}   props.value    Value.
 * @param {Array}    props.options  { value, label }.
 * @param {Function} props.onChange Called with the new value.
 * @return {Element} Control.
 */
export function Segmented( { label, value, options, onChange } ) {
	return (
		<div className="slidecraft-segmented">
			{ label && (
				<span className="slidecraft-segmented__label">{ label }</span>
			) }
			<div
				className="slidecraft-segmented__buttons"
				role="radiogroup"
				aria-label={ label }
			>
				{ options.map( ( option ) => (
					<Button
						key={ option.value }
						role="radio"
						aria-checked={ value === option.value }
						isPressed={ value === option.value }
						onClick={ () => onChange( option.value ) }
						size="small"
						icon={ option.icon }
						label={ option.icon ? option.label : undefined }
						showTooltip={ !! option.icon }
					>
						{ option.icon ? null : option.label }
					</Button>
				) ) }
			</div>
		</div>
	);
}

export const opts = ( map ) =>
	Object.keys( map ).map( ( value ) => ( { value, label: map[ value ] } ) );
