/**
 * Shared app helpers: notices and navigation.
 */
import { createContext, useContext } from '@wordpress/element';

export const NoticeContext = createContext( () => {} );

/**
 * Show a short notice: notify( message, 'success' | 'error' ).
 *
 * @return {Function} notify.
 */
export const useNotify = () => useContext( NoticeContext );

export const navigate = ( hash ) => {
	window.location.hash = hash;
};
