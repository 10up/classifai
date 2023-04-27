import { __ } from '@wordpress/i18n';
import { get } from 'lodash';

/**
 * Handle Click for given button.
 *
 * @param {Object}           root          Option for handle click.
 * @param {Element}          root.button   The button being clicked
 * @param {string}           root.endpoint Which endpoint to query
 * @param {Function|boolean} root.callback Optional callback to run after the request completes.
 *
 */
export const handleClick = ( {
	button,
	endpoint,
	callback = false,
	buttonText = __( 'Rescan', 'classifai' ),
} ) => {
	const postID = button.getAttribute( 'data-id' );
	const [ spinner ] = button.parentNode.getElementsByClassName( 'spinner' );
	const [ errorContainer ] =
		button.parentNode.getElementsByClassName( 'error' );
	const path = `${ endpoint }${ postID }`;

	button.setAttribute( 'disabled', 'disabled' );
	spinner.style.display = 'inline-block';
	spinner.classList.add( 'is-active' );
	errorContainer.style.display = 'none';

	wp.apiRequest( { path } ).then(
		( response ) => {
			button.removeAttribute( 'disabled' );
			spinner.style.display = 'none';
			spinner.classList.remove( 'is-active' );
			button.textContent = buttonText;
			// eslint-disable-next-line no-unused-expressions
			callback && callback( response );
		},
		( error ) => {
			const errorObj = get( error, 'responseJSON', {
				code: 'unknown_error',
				message: __( 'An unknown error occurred.', 'classifai' ),
			} );
			spinner.style.display = 'none';
			spinner.classList.remove( 'is-active' );
			button.removeAttribute( 'disabled' );
			button.textContent = buttonText;
			errorContainer.style.display = 'inline-block';
			errorContainer.textContent = `Error: ${ errorObj.message }`;
		}
	);
};
