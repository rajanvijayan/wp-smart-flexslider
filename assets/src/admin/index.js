/**
 * SlideCraft admin app: slider library, template picker and builder.
 */
import { createRoot } from '@wordpress/element';
import App from './app';
import './admin.scss';

const mount = document.getElementById( 'slidecraft-admin' );
if ( mount ) {
	createRoot( mount ).render( <App /> );
}
