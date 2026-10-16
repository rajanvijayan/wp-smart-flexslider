import { setIn, merge, newId } from '../model';
import { reducer } from '../editor/use-history';

describe( 'setIn', () => {
	it( 'replaces a nested value without touching the original', () => {
		const config = {
			slides: [ { id: 'a', layers: [ { id: 'x', text: 'Hi' } ] } ],
		};
		const next = setIn(
			config,
			[ 'slides', 0, 'layers', 0, 'text' ],
			'Hello'
		);
		expect( next.slides[ 0 ].layers[ 0 ].text ).toBe( 'Hello' );
		expect( config.slides[ 0 ].layers[ 0 ].text ).toBe( 'Hi' );
		expect( Array.isArray( next.slides ) ).toBe( true );
		expect( next.slides[ 0 ].id ).toBe( 'a' );
	} );

	it( 'accepts an updater function', () => {
		expect( setIn( { n: 1 }, [ 'n' ], ( v ) => v + 1 ) ).toEqual( {
			n: 2,
		} );
	} );
} );

describe( 'merge', () => {
	it( 'merges objects deeply and replaces arrays', () => {
		expect(
			merge( { a: { b: 1, c: 2 }, l: [ 1 ] }, { a: { c: 3 }, l: [ 2 ] } )
		).toEqual( { a: { b: 1, c: 3 }, l: [ 2 ] } );
	} );
} );

describe( 'newId', () => {
	it( 'makes 8-character lowercase IDs', () => {
		expect( newId() ).toMatch( /^[a-z0-9]{8}$/ );
		expect( newId() ).not.toBe( newId() );
	} );
} );

describe( 'history', () => {
	const start = {
		past: [],
		present: 0,
		future: [],
		key: null,
		time: 0,
		version: 0,
	};

	it( 'undoes and redoes', () => {
		let state = reducer( start, { type: 'set', value: 1 } );
		state = reducer( state, { type: 'set', value: 2 } );
		state = reducer( state, { type: 'undo' } );
		expect( state.present ).toBe( 1 );
		state = reducer( state, { type: 'redo' } );
		expect( state.present ).toBe( 2 );
	} );

	it( 'merges quick edits to the same field into one step', () => {
		let state = reducer( start, { type: 'set', value: 1, key: 'title' } );
		state = reducer( state, { type: 'set', value: 2, key: 'title' } );
		state = reducer( state, { type: 'set', value: 3, key: 'title' } );
		expect( state.past ).toEqual( [ 0 ] );
		state = reducer( state, { type: 'set', value: 4, key: 'other' } );
		expect( state.past ).toEqual( [ 0, 3 ] );
	} );

	it( 'drops the redo stack after a new edit', () => {
		let state = reducer( start, { type: 'set', value: 1 } );
		state = reducer( state, { type: 'undo' } );
		state = reducer( state, { type: 'set', value: 5 } );
		expect( state.future ).toEqual( [] );
	} );
} );
