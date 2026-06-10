/* eslint object-shorthand: 0 */

/**
 * Internal dependencies
 */
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
	 * @param {string} prompt      Prompt used in generating images.
	 * @param {string} quality     Quality of the image.
	 * @param {string} size        Size of the image.
	 * @param {string} style       Style of the image.
	 * @param {string} aspectRatio Aspect ratio of the image.
	 */
	makeRequest: function ( prompt, quality, size, style, aspectRatio ) {
		const data = {
			format: 'b64_json',
			prompt: prompt,
		};

		if ( quality ) {
			data.quality = quality;
		}

		if ( size ) {
			data.size = size;
		}

		if ( style ) {
			data.style = style;
		}

		if ( aspectRatio ) {
			data.aspect_ratio = aspectRatio;
		}

		this.fetch( {
			type: 'get',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data: data,
			reset: true,
			error: function ( collection, response ) {
				new ErrorMessage( { error: response.responseJSON.message } );
			},
		} );
	},
} );

export default Images;
