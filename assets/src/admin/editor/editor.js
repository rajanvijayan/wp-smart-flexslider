/**
 * The builder: slide list, live preview and inspector, with autosave, undo/redo and revisions.
 */
import {
	useEffect,
	useRef,
	useState,
	useCallback,
	useMemo,
} from '@wordpress/element';
import { Button, Spinner, Modal, Notice } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import {
	arrowLeft,
	undo as undoIcon,
	redo as redoIcon,
	desktop,
	tablet,
	mobile,
	backup,
	seen,
} from '@wordpress/icons';
import { api, errorMessage } from '../api';
import { useNotify } from '../context';
import { copyText } from '../library/library';
import {
	newSlide,
	newLayer,
	duplicateSlide,
	duplicateLayer,
	setIn,
	hasLayers,
	slideLabel,
	MAX_LAYERS,
} from '../model';
import useHistory from './use-history';
import Preview from './preview';
import SlideList from './slide-list';
import SlidePanel from './slide-panel';
import LayersPanel from './layers-panel';
import { SliderPanel, ResponsivePanel } from './slider-panel';

const AUTOSAVE_MS = 1500;

const isTyping = ( el ) =>
	el &&
	( /^(input|textarea|select)$/i.test( el.tagName ) || el.isContentEditable );

function Revisions( { id, onRestore, onClose } ) {
	const notify = useNotify();
	const [ list, setList ] = useState( null );
	useEffect( () => {
		api.revisions( id )
			.then( setList )
			.catch( ( e ) => {
				notify( errorMessage( e ), 'error' );
				onClose();
			} );
	}, [ id, notify, onClose ] );
	return (
		<Modal
			title={ __( 'Earlier versions', 'wp-smart-flexslider' ) }
			onRequestClose={ onClose }
		>
			<p className="slidecraft-help">
				{ __(
					'A version is kept each time you click Save (up to 10). Restoring one keeps the current version too.',
					'wp-smart-flexslider'
				) }
			</p>
			{ ! list && <Spinner /> }
			{ list && ! list.length && (
				<p>
					{ __( 'No earlier versions yet.', 'wp-smart-flexslider' ) }
				</p>
			) }
			{ list && list.length > 0 && (
				<ul className="slidecraft-revisions">
					{ list.map( ( rev ) => (
						<li key={ rev.index }>
							<span>
								<strong>
									{ new Date(
										rev.time * 1000
									).toLocaleString() }
								</strong>
								{ rev.author && <small>{ rev.author }</small> }
							</span>
							<Button
								variant="secondary"
								size="small"
								onClick={ () => onRestore( rev.index ) }
							>
								{ __( 'Restore', 'wp-smart-flexslider' ) }
							</Button>
						</li>
					) ) }
				</ul>
			) }
		</Modal>
	);
}

export default function Editor( { id } ) {
	const notify = useNotify();
	const history = useHistory( null );
	const config = history.value;
	const [ meta, setMeta ] = useState( null );
	const [ title, setTitle ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ slideId, setSlideId ] = useState( '' );
	const [ layerId, setLayerId ] = useState( '' );
	const [ tab, setTab ] = useState( 'slide' );
	const [ device, setDevice ] = useState( 'desktop' );
	const [ playing, setPlaying ] = useState( false );
	const [ status, setStatus ] = useState( 'saved' );
	const [ showRevisions, setShowRevisions ] = useState( false );
	const closeRevisions = useCallback( () => setShowRevisions( false ), [] );

	const saved = useRef( '' );
	const saving = useRef( null );
	const latest = useRef( {} );
	latest.current = { config, title };
	const snapshot = ( c, t ) => JSON.stringify( [ t, c ] );

	/* Load. */
	useEffect( () => {
		api.get( id )
			.then( ( data ) => {
				history.reset( data.config );
				setTitle( data.title );
				setMeta( data );
				saved.current = snapshot( data.config, data.title );
				setSlideId(
					data.config.slides[ 0 ] ? data.config.slides[ 0 ].id : ''
				);
			} )
			.catch( ( e ) => setError( errorMessage( e ) ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ id ] );

	/* Save: one request at a time; changes made meanwhile are saved right after. */
	const save = useCallback(
		async ( revision = false ) => {
			if ( saving.current ) {
				saving.current.again =
					saving.current.again || revision || 'plain';
				return saving.current.promise;
			}
			const { config: c, title: t } = latest.current;
			const snap = snapshot( c, t );
			if ( snap === saved.current && ! revision ) {
				setStatus( 'saved' );
				return;
			}
			const job = { again: false };
			saving.current = job;
			setStatus( 'saving' );
			job.promise = api
				.update( id, { title: t, config: c, revision } )
				.then( ( data ) => {
					saved.current = snap;
					setMeta( data );
					setStatus(
						snapshot(
							latest.current.config,
							latest.current.title
						) === snap
							? 'saved'
							: 'dirty'
					);
					if ( true === revision ) {
						notify( __( 'Saved.', 'wp-smart-flexslider' ) );
					}
				} )
				.catch( ( e ) => {
					setStatus( 'error' );
					notify(
						sprintf(
							/* translators: %s: error message */ __(
								'Could not save: %s',
								'wp-smart-flexslider'
							),
							errorMessage( e )
						),
						'error'
					);
				} )
				.finally( () => {
					saving.current = null;
					if ( job.again ) {
						save( true === job.again );
					}
				} );
			return job.promise;
		},
		[ id, notify ]
	);

	/* Autosave after a pause in editing. */
	useEffect( () => {
		if ( ! meta ) {
			return;
		}
		if ( snapshot( config, title ) === saved.current ) {
			return;
		}
		setStatus( 'dirty' );
		const timer = setTimeout( () => save( false ), AUTOSAVE_MS );
		return () => clearTimeout( timer );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ history.version, title, meta ] );

	/* Warn before leaving with unsaved changes. */
	useEffect( () => {
		const onLeave = ( event ) => {
			if (
				snapshot( latest.current.config, latest.current.title ) !==
				saved.current
			) {
				event.preventDefault();
				event.returnValue = '';
			}
		};
		window.addEventListener( 'beforeunload', onLeave );
		return () => window.removeEventListener( 'beforeunload', onLeave );
	}, [] );

	/* Keyboard: save, undo, redo. */
	useEffect( () => {
		const onKey = ( event ) => {
			const mod = event.metaKey || event.ctrlKey;
			if ( ! mod ) {
				return;
			}
			const key = event.key.toLowerCase();
			if ( 's' === key ) {
				event.preventDefault();
				save( true );
			} else if (
				! isTyping( event.target ) &&
				( 'z' === key || 'y' === key )
			) {
				event.preventDefault();
				if ( 'y' === key || event.shiftKey ) {
					history.redo();
				} else {
					history.undo();
				}
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ save, history ] );

	const slides = useMemo( () => ( config ? config.slides : [] ), [ config ] );
	const slideIndex = slides.findIndex( ( s ) => s.id === slideId );
	const slide = slides[ slideIndex ];

	/* Keep the selection valid after undo, delete or restore. */
	useEffect( () => {
		if ( ! config ) {
			return;
		}
		if ( ! slide && slides.length ) {
			setSlideId( slides[ 0 ].id );
		}
		if (
			layerId &&
			( ! slide || ! slide.layers.some( ( l ) => l.id === layerId ) )
		) {
			setLayerId( '' );
		}
	}, [ config, slide, slides, layerId ] );

	const update = useCallback(
		( path, value, key ) =>
			history.set( ( c ) => setIn( c, path, value ), key ),
		[ history ]
	);

	const setSlideField = ( path, value, key ) => {
		if ( 'type' === path[ 0 ] ) {
			history.set( ( c ) =>
				setIn( c, [ 'slides', slideIndex ], ( s ) => {
					let next = { ...s, type: value };
					if ( 'video' === value ) {
						next = setIn( next, [ 'background', 'type' ], 'video' );
					} else if ( 'video' === next.background.type ) {
						next = setIn( next, [ 'background', 'type' ], 'image' );
					}
					return next;
				} )
			);
			return;
		}
		update(
			[ 'slides', slideIndex, ...path ],
			value,
			key && `${ slide.id }-${ key }`
		);
	};

	/* Slide actions. */
	const addSlide = ( type ) => {
		const created = newSlide( type );
		history.set( ( c ) => {
			const list = [ ...c.slides ];
			const at = list.findIndex( ( s ) => s.id === slideId );
			list.splice( at < 0 ? list.length : at + 1, 0, created );
			return { ...c, slides: list };
		} );
		setSlideId( created.id );
		setLayerId( '' );
		setTab( 'slide' );
	};
	const duplicate = ( sid ) => {
		const source = slides.find( ( s ) => s.id === sid );
		const copy = duplicateSlide( source );
		history.set( ( c ) => {
			const list = [ ...c.slides ];
			list.splice( list.findIndex( ( s ) => s.id === sid ) + 1, 0, copy );
			return { ...c, slides: list };
		} );
		setSlideId( copy.id );
	};
	const toggleSlide = ( sid ) =>
		history.set( ( c ) => ( {
			...c,
			slides: c.slides.map( ( s ) =>
				s.id === sid ? { ...s, hidden: ! s.hidden } : s
			),
		} ) );
	const deleteSlide = ( sid ) => {
		const at = slides.findIndex( ( s ) => s.id === sid );
		const label = slideLabel( slides[ at ], at );
		history.set( ( c ) => ( {
			...c,
			slides: c.slides.filter( ( s ) => s.id !== sid ),
		} ) );
		if ( sid === slideId ) {
			const next = slides[ at + 1 ] || slides[ at - 1 ];
			setSlideId( next ? next.id : '' );
		}
		notify(
			sprintf(
				/* translators: %s: slide name */
				__(
					'“%s” deleted. Press Ctrl+Z (⌘Z) to undo.',
					'wp-smart-flexslider'
				),
				label
			)
		);
	};

	/* Layer actions on the selected slide. */
	const setLayers = ( fn ) =>
		update( [ 'slides', slideIndex, 'layers' ], fn );
	const addLayer = ( type ) => {
		if ( slide.layers.length >= MAX_LAYERS ) {
			return;
		}
		const base =
			slide.layers.find( ( l ) => l.type === type ) ||
			slide.layers[ slide.layers.length - 1 ];
		const layer = newLayer(
			type,
			base
				? {
						position: { anchor: base.position.anchor },
						align: base.align,
				  }
				: {}
		);
		setLayers( ( list ) => [ ...list, layer ] );
		setLayerId( layer.id );
		setTab( 'layers' );
	};
	const moveLayer = ( lid, dir ) =>
		setLayers( ( list ) => {
			const at = list.findIndex( ( l ) => l.id === lid );
			const to = at + dir;
			if ( to < 0 || to >= list.length ) {
				return list;
			}
			const copy = [ ...list ];
			[ copy[ at ], copy[ to ] ] = [ copy[ to ], copy[ at ] ];
			return copy;
		} );
	const duplicateLayerById = ( lid ) => {
		const source = slide.layers.find( ( l ) => l.id === lid );
		const copy = duplicateLayer( source );
		setLayers( ( list ) => {
			const at = list.findIndex( ( l ) => l.id === lid );
			const next = [ ...list ];
			next.splice( at + 1, 0, copy );
			return next;
		} );
		setLayerId( copy.id );
	};
	const deleteLayer = ( lid ) => {
		setLayers( ( list ) => list.filter( ( l ) => l.id !== lid ) );
		if ( lid === layerId ) {
			setLayerId( '' );
		}
	};

	/* Preview events. Generated (posts) slides report "{template id}-{post id}". */
	const findSlide = useCallback(
		( renderedId ) =>
			slides.find(
				( s ) =>
					s.id === renderedId ||
					( renderedId || '' ).startsWith( s.id + '-' )
			),
		[ slides ]
	);
	const onPreviewSelect = ( renderedId, lid ) => {
		const target = findSlide( renderedId );
		if ( ! target ) {
			return;
		}
		setSlideId( target.id );
		if ( lid && target.layers.some( ( l ) => l.id === lid ) ) {
			setLayerId( lid );
			setTab( 'layers' );
		} else {
			setLayerId( '' );
		}
	};
	const onPreviewMove = ( renderedId, lid, x, y ) => {
		const target = findSlide( renderedId );
		if ( ! target ) {
			return;
		}
		const li = target.layers.findIndex( ( l ) => l.id === lid );
		if ( li < 0 ) {
			return;
		}
		const si = slides.indexOf( target );
		history.set( ( c ) =>
			setIn( c, [ 'slides', si, 'layers', li, 'position' ], ( p ) => ( {
				...p,
				x: Math.round( x ),
				y: Math.round( y ),
			} ) )
		);
		setSlideId( target.id );
		setLayerId( lid );
		setTab( 'layers' );
	};
	const onPreviewSlide = ( renderedId ) => {
		if ( playing ) {
			return;
		}
		const target = findSlide( renderedId );
		if ( target && target.id !== slideId ) {
			setSlideId( target.id );
			setLayerId( '' );
		}
	};

	const restore = async ( index ) => {
		setShowRevisions( false );
		try {
			const data = await api.restore( id, index );
			saved.current = snapshot( data.config, latest.current.title );
			history.set( data.config );
			setMeta( data );
			notify( __( 'Version restored.', 'wp-smart-flexslider' ) );
		} catch ( e ) {
			notify( errorMessage( e ), 'error' );
		}
	};

	const statusText = useMemo(
		() =>
			( {
				saved: __( 'Saved', 'wp-smart-flexslider' ),
				dirty: __( 'Unsaved changes', 'wp-smart-flexslider' ),
				saving: __( 'Saving…', 'wp-smart-flexslider' ),
				error: __( 'Not saved', 'wp-smart-flexslider' ),
			} )[ status ],
		[ status ]
	);

	if ( error ) {
		return (
			<div className="slidecraft-library">
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
				<Button variant="link" href="#/">
					{ __( 'Back to all sliders', 'wp-smart-flexslider' ) }
				</Button>
			</div>
		);
	}
	if ( ! config || ! meta ) {
		return (
			<div className="slidecraft-editor is-loading">
				<Spinner />
			</div>
		);
	}

	const TABS = [
		{
			name: 'slide',
			title: __( 'Slide', 'wp-smart-flexslider' ),
			disabled: ! slide,
		},
		{
			name: 'layers',
			title: __( 'Layers', 'wp-smart-flexslider' ),
			disabled: ! slide || ! hasLayers( slide.type ),
		},
		{ name: 'slider', title: __( 'Slider', 'wp-smart-flexslider' ) },
		{
			name: 'responsive',
			title: __( 'Responsive', 'wp-smart-flexslider' ),
		},
	];
	const activeTab = TABS.find( ( t ) => t.name === tab && ! t.disabled )
		? tab
		: 'slider';

	return (
		<div className="slidecraft-editor">
			<header className="slidecraft-editor__bar">
				<Button
					icon={ arrowLeft }
					href="#/"
					label={ __( 'All sliders', 'wp-smart-flexslider' ) }
					className="slidecraft-editor__back"
				/>
				<input
					className="slidecraft-editor__title"
					value={ title }
					aria-label={ __( 'Slider title', 'wp-smart-flexslider' ) }
					placeholder={ __( 'Slider title', 'wp-smart-flexslider' ) }
					onChange={ ( e ) => setTitle( e.target.value ) }
				/>
				<span
					className={ `slidecraft-editor__status is-${ status }` }
					role="status"
				>
					{ statusText }
				</span>
				<div className="slidecraft-editor__tools">
					<Button
						icon={ undoIcon }
						label={ __( 'Undo', 'wp-smart-flexslider' ) }
						shortcut="Ctrl+Z"
						disabled={ ! history.canUndo }
						onClick={ history.undo }
					/>
					<Button
						icon={ redoIcon }
						label={ __( 'Redo', 'wp-smart-flexslider' ) }
						shortcut="Ctrl+Shift+Z"
						disabled={ ! history.canRedo }
						onClick={ history.redo }
					/>
					<span className="slidecraft-editor__divider" />
					{ [
						[
							'desktop',
							desktop,
							__( 'Desktop', 'wp-smart-flexslider' ),
						],
						[
							'tablet',
							tablet,
							__( 'Tablet', 'wp-smart-flexslider' ),
						],
						[
							'mobile',
							mobile,
							__( 'Mobile', 'wp-smart-flexslider' ),
						],
					].map( ( [ name, icon, label ] ) => (
						<Button
							key={ name }
							icon={ icon }
							label={ label }
							isPressed={ device === name }
							onClick={ () => setDevice( name ) }
						/>
					) ) }
					<span className="slidecraft-editor__divider" />
					<Button
						icon={ seen }
						isPressed={ playing }
						label={
							playing
								? __(
										'Stop autoplay in preview',
										'wp-smart-flexslider'
								  )
								: __(
										'Play autoplay in preview',
										'wp-smart-flexslider'
								  )
						}
						onClick={ () => setPlaying( ! playing ) }
					/>
					<Button
						icon={ backup }
						label={ __(
							'Earlier versions',
							'wp-smart-flexslider'
						) }
						onClick={ () => setShowRevisions( true ) }
					/>
					<button
						type="button"
						className="slidecraft-shortcode"
						onClick={ () => copyText( meta.shortcode, notify ) }
						title={ __( 'Copy shortcode', 'wp-smart-flexslider' ) }
					>
						<code>{ meta.shortcode }</code>
					</button>
					<Button
						variant="primary"
						onClick={ () => save( true ) }
						isBusy={ 'saving' === status }
					>
						{ __( 'Save', 'wp-smart-flexslider' ) }
					</Button>
				</div>
			</header>

			<div className="slidecraft-editor__body">
				<aside className="slidecraft-editor__slides">
					<SlideList
						slides={ slides }
						activeId={ slideId }
						onSelect={ ( sid ) => {
							setSlideId( sid );
							setLayerId( '' );
							if ( 'slider' === tab || 'responsive' === tab ) {
								setTab( 'slide' );
							}
						} }
						onReorder={ ( list ) =>
							history.set( ( c ) => ( { ...c, slides: list } ) )
						}
						onAdd={ addSlide }
						onDuplicate={ duplicate }
						onToggle={ toggleSlide }
						onDelete={ deleteSlide }
					/>
				</aside>

				<main className="slidecraft-editor__canvas">
					{ slide && slide.hidden && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'This slide is hidden, so it is not shown in the preview or on the site.',
								'wp-smart-flexslider'
							) }
						</Notice>
					) }
					<Preview
						config={ config }
						title={ title }
						id={ id }
						device={ device }
						playing={ playing }
						slideId={ slideId }
						layerId={ layerId }
						onSelect={ onPreviewSelect }
						onMove={ onPreviewMove }
						onSlide={ onPreviewSlide }
					/>
					<p className="slidecraft-editor__hint">
						{ __(
							'Click a layer to edit it, or drag it to move it. Add the slider to a page with the SlideCraft Slider block or the shortcode above.',
							'wp-smart-flexslider'
						) }
					</p>
				</main>

				<aside className="slidecraft-editor__inspector">
					<div className="slidecraft-tabs" role="tablist">
						{ TABS.map( ( t ) => (
							<button
								key={ t.name }
								type="button"
								role="tab"
								id={ `slidecraft-tab-${ t.name }` }
								aria-selected={ activeTab === t.name }
								aria-controls="slidecraft-tabpanel"
								disabled={ t.disabled }
								className={
									activeTab === t.name ? 'is-active' : ''
								}
								onClick={ () => setTab( t.name ) }
							>
								{ t.title }
							</button>
						) ) }
					</div>
					<div
						className="slidecraft-tabpanel"
						role="tabpanel"
						id="slidecraft-tabpanel"
						aria-labelledby={ `slidecraft-tab-${ activeTab }` }
					>
						{ 'slide' === activeTab && slide && (
							<SlidePanel
								key={ slide.id }
								slide={ slide }
								set={ setSlideField }
							/>
						) }
						{ 'layers' === activeTab && slide && (
							<LayersPanel
								key={ slide.id }
								slide={ slide }
								layerId={ layerId }
								onSelect={ setLayerId }
								onAdd={ addLayer }
								onMove={ moveLayer }
								onDuplicate={ duplicateLayerById }
								onDelete={ deleteLayer }
								set={ setSlideField }
							/>
						) }
						{ 'slider' === activeTab && (
							<SliderPanel
								settings={ config.settings }
								slides={ slides }
								set={ ( path, value, key ) =>
									update(
										[ 'settings', ...path ],
										value,
										key && `s-${ key }`
									)
								}
							/>
						) }
						{ 'responsive' === activeTab && (
							<ResponsivePanel
								breakpoints={ config.breakpoints }
								type={ config.settings.type }
								set={ ( path, value, key ) =>
									update(
										[ 'breakpoints', ...path ],
										value,
										key && `b-${ key }`
									)
								}
							/>
						) }
					</div>
				</aside>
			</div>

			{ showRevisions && (
				<Revisions
					id={ id }
					onRestore={ restore }
					onClose={ closeRevisions }
				/>
			) }
		</div>
	);
}
