/**
 * Undo/redo for the slider configuration. Rapid edits to the same field (typing, dragging a
 * slider control) merge into one step.
 */
import { useReducer, useCallback } from '@wordpress/element';

const LIMIT = 100;
const MERGE_MS = 800;

export function reducer( state, action ) {
	switch ( action.type ) {
		case 'set': {
			const next =
				typeof action.value === 'function'
					? action.value( state.present )
					: action.value;
			if ( next === state.present ) {
				return state;
			}
			const now = Date.now();
			const merge =
				action.key &&
				action.key === state.key &&
				now - state.time < MERGE_MS;
			return {
				past: merge
					? state.past
					: [ ...state.past, state.present ].slice( -LIMIT ),
				present: next,
				future: [],
				key: action.key || null,
				time: now,
				version: state.version + 1,
			};
		}
		case 'reset':
			return {
				past: [],
				present: action.value,
				future: [],
				key: null,
				time: 0,
				version: 0,
			};
		case 'undo':
			if ( ! state.past.length ) {
				return state;
			}
			return {
				past: state.past.slice( 0, -1 ),
				present: state.past[ state.past.length - 1 ],
				future: [ state.present, ...state.future ],
				key: null,
				time: 0,
				version: state.version + 1,
			};
		case 'redo':
			if ( ! state.future.length ) {
				return state;
			}
			return {
				past: [ ...state.past, state.present ],
				present: state.future[ 0 ],
				future: state.future.slice( 1 ),
				key: null,
				time: 0,
				version: state.version + 1,
			};
	}
	return state;
}

/**
 * History state.
 *
 * @param {*} initial Initial value.
 * @return {Object} { value, version, set( value|fn, key ), reset, undo, redo, canUndo, canRedo }.
 */
export default function useHistory( initial ) {
	const [ state, dispatch ] = useReducer( reducer, {
		past: [],
		present: initial,
		future: [],
		key: null,
		time: 0,
		version: 0,
	} );
	const set = useCallback(
		( value, key ) => dispatch( { type: 'set', value, key } ),
		[]
	);
	const reset = useCallback(
		( value ) => dispatch( { type: 'reset', value } ),
		[]
	);
	const undo = useCallback( () => dispatch( { type: 'undo' } ), [] );
	const redo = useCallback( () => dispatch( { type: 'redo' } ), [] );
	return {
		value: state.present,
		version: state.version,
		set,
		reset,
		undo,
		redo,
		canUndo: state.past.length > 0,
		canRedo: state.future.length > 0,
	};
}
