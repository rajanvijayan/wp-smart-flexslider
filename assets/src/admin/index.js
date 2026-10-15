/**
 * SlideCraft admin app (slider library and builder).
 */
import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

function App() {
	return (
		<div className="wrap">
			<h1>{ __( 'SlideCraft', 'wp-smart-flexslider' ) }</h1>
		</div>
	);
}

const mount = document.getElementById( 'slidecraft-admin' );
if ( mount ) {
	createRoot( mount ).render( <App /> );
}
