/**
 * Start a slider: blank or from a template.
 */
import { useState } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { arrowLeft } from '@wordpress/icons';
import { api, errorMessage } from '../api';
import { navigate, useNotify } from '../context';
import { blankConfig, settings } from '../model';

const CATEGORIES = [
	{ value: 'hero', label: __( 'Hero', 'wp-smart-flexslider' ) },
	{ value: 'carousel', label: __( 'Carousels', 'wp-smart-flexslider' ) },
	{ value: 'content', label: __( 'Content', 'wp-smart-flexslider' ) },
	{ value: 'gallery', label: __( 'Galleries', 'wp-smart-flexslider' ) },
];

/**
 * A stand-in thumbnail drawn from the template's first slide.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 * @return {Element} Element.
 */
function Sketch( { template } ) {
	const slide = template.config.slides[ 0 ] || {};
	const bg = slide.background || {};
	const style = {};
	if ( 'gradient' === bg.type ) {
		style.background = `linear-gradient(${ bg.gradient.angle }deg, ${ bg.gradient.from }, ${ bg.gradient.to })`;
	} else if ( 'color' === bg.type ) {
		style.background = bg.color;
	}
	return (
		<span
			className={ `slidecraft-sketch is-${ template.config.settings.type } is-${ template.key }` }
			style={ style }
		/>
	);
}

export default function NewSlider() {
	const notify = useNotify();
	const [ busy, setBusy ] = useState( '' );
	const templates = settings.templates || [];

	const start = async ( key, title, config ) => {
		setBusy( key );
		try {
			const slider = await api.create( title, config );
			navigate( `#/edit/${ slider.id }` );
		} catch ( e ) {
			notify( errorMessage( e ), 'error' );
			setBusy( '' );
		}
	};

	return (
		<div className="slidecraft-library slidecraft-new">
			<header className="slidecraft-library__header">
				<div>
					<Button
						variant="link"
						icon={ arrowLeft }
						href="#/"
						className="slidecraft-back"
					>
						{ __( 'All sliders', 'wp-smart-flexslider' ) }
					</Button>
					<h1>{ __( 'New slider', 'wp-smart-flexslider' ) }</h1>
					<p className="slidecraft-lead">
						{ __(
							'Pick a starting point. Everything can be changed in the builder.',
							'wp-smart-flexslider'
						) }
					</p>
				</div>
			</header>

			<ul className="slidecraft-grid is-templates">
				<li className="slidecraft-card is-template">
					<button
						type="button"
						className="slidecraft-template"
						disabled={ !! busy }
						onClick={ () =>
							start(
								'blank',
								__( 'Untitled slider', 'wp-smart-flexslider' ),
								blankConfig()
							)
						}
					>
						<span className="slidecraft-sketch is-blank">+</span>
						<strong>
							{ __( 'Blank slider', 'wp-smart-flexslider' ) }
						</strong>
						<span>
							{ __(
								'Start from scratch.',
								'wp-smart-flexslider'
							) }
						</span>
						{ 'blank' === busy && <Spinner /> }
					</button>
				</li>
			</ul>

			{ CATEGORIES.map( ( category ) => {
				const items = templates.filter(
					( t ) => t.category === category.value
				);
				if ( ! items.length ) {
					return null;
				}
				return (
					<section
						key={ category.value }
						className="slidecraft-templates"
					>
						<h2>{ category.label }</h2>
						<ul className="slidecraft-grid is-templates">
							{ items.map( ( template ) => (
								<li
									key={ template.key }
									className="slidecraft-card is-template"
								>
									<button
										type="button"
										className="slidecraft-template"
										disabled={ !! busy }
										onClick={ () =>
											start(
												template.key,
												template.name,
												template.config
											)
										}
									>
										{ template.thumb ? (
											<img
												src={ template.thumb }
												alt=""
												loading="lazy"
											/>
										) : (
											<Sketch template={ template } />
										) }
										<strong>{ template.name }</strong>
										<span>{ template.description }</span>
										{ template.key === busy && <Spinner /> }
									</button>
								</li>
							) ) }
						</ul>
					</section>
				);
			} ) }
		</div>
	);
}
