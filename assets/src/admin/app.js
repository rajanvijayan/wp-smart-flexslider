/**
 * Hash routes: #/ (library), #/new (templates), #/edit/:id (builder).
 */
import { useEffect, useState, useCallback } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import Library from './library/library';
import NewSlider from './library/new-slider';
import Editor from './editor/editor';
import { NoticeContext } from './context';

function parse( hash ) {
	const path = hash.replace( /^#\/?/, '' );
	const edit = path.match( /^edit\/(\d+)/ );
	if ( edit ) {
		return { name: 'edit', id: Number( edit[ 1 ] ) };
	}
	if ( 'new' === path ) {
		return { name: 'new' };
	}
	return { name: 'library' };
}

export default function App() {
	const [ route, setRoute ] = useState( () => parse( window.location.hash ) );
	const [ notices, setNotices ] = useState( [] );

	useEffect( () => {
		const onHash = () => setRoute( parse( window.location.hash ) );
		window.addEventListener( 'hashchange', onHash );
		return () => window.removeEventListener( 'hashchange', onHash );
	}, [] );

	useEffect( () => {
		document.body.classList.toggle(
			'slidecraft-is-editing',
			'edit' === route.name
		);
	}, [ route.name ] );

	const notify = useCallback( ( message, status = 'success' ) => {
		const id = Date.now() + Math.random();
		setNotices( ( list ) => [ ...list, { id, message, status } ] );
		setTimeout(
			() => setNotices( ( list ) => list.filter( ( n ) => n.id !== id ) ),
			'error' === status ? 8000 : 3500
		);
	}, [] );

	let screen;
	if ( 'edit' === route.name ) {
		screen = <Editor key={ route.id } id={ route.id } />;
	} else if ( 'new' === route.name ) {
		screen = <NewSlider />;
	} else {
		screen = <Library />;
	}

	return (
		<NoticeContext.Provider value={ notify }>
			<div className="slidecraft-app">{ screen }</div>
			<div className="slidecraft-notices" aria-live="polite">
				{ notices.map( ( n ) => (
					<Notice
						key={ n.id }
						status={ n.status }
						onRemove={ () =>
							setNotices( ( list ) =>
								list.filter( ( x ) => x.id !== n.id )
							)
						}
					>
						{ n.message }
					</Notice>
				) ) }
			</div>
		</NoticeContext.Provider>
	);
}
