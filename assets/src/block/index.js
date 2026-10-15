/**
 * SlideCraft Slider block: pick a slider from the library and preview it with the real renderer.
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Placeholder,
	ComboboxControl,
	SelectControl,
	RangeControl,
	Button,
	Spinner,
	ExternalLink,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from '../../../blocks/slider/block.json';

const adminUrl =
	( window.slideCraftBlock && window.slideCraftBlock.adminUrl ) || '';

/**
 * Sliders in the library, loaded once per editor session.
 */
let cache = null;
function useSliders() {
	const [ sliders, setSliders ] = useState( cache );
	useEffect( () => {
		if ( cache ) {
			return;
		}
		apiFetch( { path: '/slidecraft/v1/sliders?per_page=100' } )
			.then( ( list ) => {
				cache = list;
				setSliders( list );
			} )
			.catch( () => setSliders( [] ) );
	}, [] );
	return sliders;
}

function SliderPicker( { value, onChange, sliders } ) {
	if ( ! sliders ) {
		return <Spinner />;
	}
	return (
		<ComboboxControl
			__nextHasNoMarginBottom
			label={ __( 'Slider', 'wp-smart-flexslider' ) }
			value={ value || null }
			options={ sliders.map( ( s ) => ( {
				value: s.id,
				label: s.title || __( '(no title)', 'wp-smart-flexslider' ),
			} ) ) }
			onChange={ ( id ) => onChange( Number( id ) || 0 ) }
		/>
	);
}

function Edit( { attributes, setAttributes } ) {
	const { sliderId, autoplay, height } = attributes;
	const sliders = useSliders();
	const blockProps = useBlockProps();
	const editLink = sliderId ? `${ adminUrl }#/edit/${ sliderId }` : '';

	const inspector = (
		<InspectorControls>
			<PanelBody title={ __( 'Slider', 'wp-smart-flexslider' ) }>
				<SliderPicker
					value={ sliderId }
					sliders={ sliders }
					onChange={ ( id ) => setAttributes( { sliderId: id } ) }
				/>
				{ editLink && (
					<ExternalLink href={ editLink }>
						{ __( 'Edit this slider', 'wp-smart-flexslider' ) }
					</ExternalLink>
				) }
			</PanelBody>
			<PanelBody
				title={ __( 'Overrides', 'wp-smart-flexslider' ) }
				initialOpen={ false }
			>
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Autoplay', 'wp-smart-flexslider' ) }
					value={ autoplay }
					options={ [
						{
							value: 'default',
							label: __(
								'Use slider setting',
								'wp-smart-flexslider'
							),
						},
						{
							value: 'on',
							label: __( 'On', 'wp-smart-flexslider' ),
						},
						{
							value: 'off',
							label: __( 'Off', 'wp-smart-flexslider' ),
						},
					] }
					onChange={ ( value ) =>
						setAttributes( { autoplay: value } )
					}
				/>
				<RangeControl
					__nextHasNoMarginBottom
					label={ __(
						'Height (px, 0 = slider setting)',
						'wp-smart-flexslider'
					) }
					value={ height }
					min={ 0 }
					max={ 1200 }
					step={ 10 }
					onChange={ ( value ) =>
						setAttributes( { height: value || 0 } )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);

	if ( ! sliderId ) {
		return (
			<div { ...blockProps }>
				{ inspector }
				<Placeholder
					icon="images-alt2"
					label={ __( 'SlideCraft Slider', 'wp-smart-flexslider' ) }
					instructions={
						sliders && ! sliders.length
							? __(
									'You have no sliders yet. Create one in the SlideCraft builder.',
									'wp-smart-flexslider'
							  )
							: __(
									'Choose a slider from your library.',
									'wp-smart-flexslider'
							  )
					}
				>
					{ sliders && sliders.length > 0 && (
						<div style={ { minWidth: 280 } }>
							<SliderPicker
								value={ sliderId }
								sliders={ sliders }
								onChange={ ( id ) =>
									setAttributes( { sliderId: id } )
								}
							/>
						</div>
					) }
					<Button
						variant="secondary"
						href={ `${ adminUrl }#/new` }
						target="_blank"
					>
						{ __( 'Create a slider', 'wp-smart-flexslider' ) }
					</Button>
				</Placeholder>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			{ inspector }
			<ServerSideRender
				block={ metadata.name }
				attributes={ { sliderId, autoplay, height } }
				LoadingResponsePlaceholder={ () => (
					<Placeholder
						icon="images-alt2"
						label={ __(
							'SlideCraft Slider',
							'wp-smart-flexslider'
						) }
					>
						<Spinner />
					</Placeholder>
				) }
			/>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
