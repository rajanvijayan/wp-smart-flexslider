/**
 * Slide list: select, reorder (drag or keyboard), add, duplicate, hide and delete.
 */
import {
	DndContext,
	closestCenter,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
	arrayMove,
} from '@dnd-kit/sortable';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
import { CSS } from '@dnd-kit/utilities';
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	Dashicon,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import {
	Icon,
	plus,
	moreVertical,
	dragHandle,
	seen,
	unseen,
} from '@wordpress/icons';
import { useMedia } from '../api';
import { SLIDE_TYPES, MAX_SLIDES, slideLabel, typeLabel } from '../model';

function Thumb( { slide } ) {
	const bg = slide.background;
	let imageId = bg.image_id;
	if ( 'testimonial' === slide.type ) {
		imageId = slide.testimonial.avatar_id;
	} else if ( 'video' === slide.type ) {
		imageId = bg.video.poster_id;
	}
	const media = useMedia( imageId );
	const style = {};
	if ( 'gradient' === bg.type && 'video' !== slide.type ) {
		style.background = `linear-gradient(${ bg.gradient.angle }deg, ${ bg.gradient.from }, ${ bg.gradient.to })`;
	} else if ( 'color' === bg.type ) {
		style.background = bg.color;
	}
	const icon = ( SLIDE_TYPES.find( ( t ) => t.value === slide.type ) || {} )
		.icon;
	return (
		<span className="slidecraft-slides__thumb" style={ style }>
			{ media && media.thumb ? (
				<img src={ media.thumb } alt="" />
			) : (
				<Dashicon icon={ icon } />
			) }
		</span>
	);
}

function Item( {
	slide,
	index,
	active,
	onSelect,
	onDuplicate,
	onToggle,
	onDelete,
	canAdd,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
		setActivatorNodeRef,
	} = useSortable( { id: slide.id } );
	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
	};
	const label = slideLabel( slide, index );
	return (
		<li
			ref={ setNodeRef }
			style={ style }
			className={ `slidecraft-slides__item${
				active ? ' is-active' : ''
			}${ slide.hidden ? ' is-hidden' : '' }${
				isDragging ? ' is-dragging' : ''
			}` }
		>
			<button
				type="button"
				className="slidecraft-slides__handle"
				ref={ setActivatorNodeRef }
				{ ...attributes }
				{ ...listeners }
				aria-label={ sprintf(
					/* translators: %s: slide name */
					__( 'Move %s', 'wp-smart-flexslider' ),
					label
				) }
			>
				<Icon icon={ dragHandle } size={ 18 } />
			</button>
			<button
				type="button"
				className="slidecraft-slides__select"
				onClick={ onSelect }
				aria-current={ active ? 'true' : undefined }
			>
				<Thumb slide={ slide } />
				<span className="slidecraft-slides__text">
					<span className="slidecraft-slides__name">{ label }</span>
					<span className="slidecraft-slides__type">
						{ typeLabel( SLIDE_TYPES, slide.type ) }
						{ slide.hidden
							? ' · ' + __( 'Hidden', 'wp-smart-flexslider' )
							: '' }
					</span>
				</span>
			</button>
			<DropdownMenu
				icon={ moreVertical }
				label={ __( 'Slide actions', 'wp-smart-flexslider' ) }
				className="slidecraft-slides__menu"
			>
				{ ( { onClose } ) => (
					<>
						<MenuGroup>
							<MenuItem
								disabled={ ! canAdd }
								onClick={ () => {
									onClose();
									onDuplicate();
								} }
							>
								{ __( 'Duplicate', 'wp-smart-flexslider' ) }
							</MenuItem>
							<MenuItem
								icon={ slide.hidden ? seen : unseen }
								onClick={ () => {
									onClose();
									onToggle();
								} }
							>
								{ slide.hidden
									? __( 'Show slide', 'wp-smart-flexslider' )
									: __(
											'Hide slide',
											'wp-smart-flexslider'
									  ) }
							</MenuItem>
						</MenuGroup>
						<MenuGroup>
							<MenuItem
								isDestructive
								onClick={ () => {
									onClose();
									onDelete();
								} }
							>
								{ __( 'Delete', 'wp-smart-flexslider' ) }
							</MenuItem>
						</MenuGroup>
					</>
				) }
			</DropdownMenu>
		</li>
	);
}

export default function SlideList( {
	slides,
	activeId,
	onSelect,
	onReorder,
	onAdd,
	onDuplicate,
	onToggle,
	onDelete,
} ) {
	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 4 } } ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);
	const canAdd = slides.length < MAX_SLIDES;

	const onDragEnd = ( { active, over } ) => {
		if ( over && active.id !== over.id ) {
			const from = slides.findIndex( ( s ) => s.id === active.id );
			const to = slides.findIndex( ( s ) => s.id === over.id );
			onReorder( arrayMove( slides, from, to ) );
		}
	};

	return (
		<div className="slidecraft-slides">
			<div className="slidecraft-slides__header">
				<h2>{ __( 'Slides', 'wp-smart-flexslider' ) }</h2>
				<DropdownMenu
					icon={ plus }
					label={ __( 'Add slide', 'wp-smart-flexslider' ) }
					toggleProps={ {
						variant: 'primary',
						size: 'small',
						disabled: ! canAdd,
					} }
				>
					{ ( { onClose } ) => (
						<MenuGroup
							label={ __( 'Add slide', 'wp-smart-flexslider' ) }
						>
							{ SLIDE_TYPES.map( ( type ) => (
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
			{ ! slides.length && (
				<div className="slidecraft-slides__empty">
					<p>{ __( 'No slides yet.', 'wp-smart-flexslider' ) }</p>
					<Button
						variant="secondary"
						onClick={ () => onAdd( 'image' ) }
					>
						{ __( 'Add an image slide', 'wp-smart-flexslider' ) }
					</Button>
				</div>
			) }
			<DndContext
				sensors={ sensors }
				collisionDetection={ closestCenter }
				onDragEnd={ onDragEnd }
				modifiers={ [ restrictToVerticalAxis ] }
			>
				<SortableContext
					items={ slides.map( ( s ) => s.id ) }
					strategy={ verticalListSortingStrategy }
				>
					<ol className="slidecraft-slides__list">
						{ slides.map( ( slide, index ) => (
							<Item
								key={ slide.id }
								slide={ slide }
								index={ index }
								active={ slide.id === activeId }
								canAdd={ canAdd }
								onSelect={ () => onSelect( slide.id ) }
								onDuplicate={ () => onDuplicate( slide.id ) }
								onToggle={ () => onToggle( slide.id ) }
								onDelete={ () => onDelete( slide.id ) }
							/>
						) ) }
					</ol>
				</SortableContext>
			</DndContext>
		</div>
	);
}
