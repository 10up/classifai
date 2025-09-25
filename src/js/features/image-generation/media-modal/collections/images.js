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
	 * Parse the response from the server.
	 * Extract the images array from the response object.
	 *
	 * @param {Object} response The response from the server.
	 * @return {Array} Array of image data.
	 */
	parse: function ( response ) {
		// If the response has an images key, return that array.
		// Otherwise, return the response as-is for backward compatibility.
		return response.images || response;
	},

	/**
	 * Send a request to our API endpoint.
	 *
	 * @param {string} prompt      Prompt used in generating images.
	 * @param {string} quality     Quality of the image.
	 * @param {string} size        Size of the image.
	 * @param {string} style       Style of the image.
	 * @param {string} aspectRatio Aspect ratio of the image.
	 */
	makeRequest: function ( prompt, quality, size, style, aspectRatio ) {
		const data = {
			input: {
				format: 'b64_json',
				prompt: prompt,
			},
		};

		if ( quality ) {
			data.input.quality = quality;
		}

		if ( size ) {
			data.input.size = size;
		}

		if ( style ) {
			data.input.style = style;
		}

		if ( aspectRatio ) {
			data.input.aspect_ratio = aspectRatio;
		}

		this.fetch( {
			type: 'post',
			contentType: 'application/json',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data: JSON.stringify( data ),
			reset: true,
			error: function ( collection, response ) {
				new ErrorMessage( { error: response.responseJSON.message } );
			},
		} );
	},
} );

export default Images;
