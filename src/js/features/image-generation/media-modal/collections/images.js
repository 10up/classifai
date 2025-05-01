/* eslint object-shorthand: 0 */

import Image from '../models/image';
import ErrorMessage from '../views/error-message';

/**
 * Collection to hold all of our Image models.
 *
 * This has the functionality to make an API request.
 */
const Images = Backbone.Collection.extend( {
	model: Image,

	url: wpApiSettings.root + classifaiDalleData.endpoint,

	/**
	 * Send a request to our API endpoint.
	 *
	 * @param {object} params Parameters to be passed with the request.
	 */
	makeRequest: function ( params ) {
		this.fetch( {
			type: 'get',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data: {
				...params,
				format: 'b64_json',
			},
			reset: true,
			error: function ( collection, response ) {
				new ErrorMessage( { error: response.responseJSON.message } );
			},
		} );
	},
} );

export default Images;
