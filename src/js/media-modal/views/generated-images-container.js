/* eslint object-shorthand: 0 */

import Images from '../collections/images';
import GeneratedImage from './generated-image';

/**
 * View to render out the generated images container.
 *
 * This uses the Images collection to make our API
 * request, showing a loading state and then rendering
 * the images.
 */
const GeneratedImagesContainer = wp.media.View.extend( {
	el: '.generated-images',

	/**
	 * Initialize the view.
	 *
	 * @param {Object} options Options passed to the view.
	 */
	initialize: function ( options ) {
		this.collection = new Images();
		this.prompt = options.prompt;

		this.listenTo( this.collection, 'reset', this.renderAll );
		this.listenTo( this.collection, 'error', this.error );

		this.collection.makeRequest( this.prompt );
		this.render();
	},

	/**
	 * Render the view.
	 */
	render: function () {
		this.$el.prev().find( 'button' ).prop( 'disabled', true );
		this.$el.prev().find( '.error' ).text( '' );
		this.$( 'ul' ).empty();
		this.$( '.spinner' ).addClass( 'active' );
		this.$( '.prompt-text' ).addClass( 'hidden' );
		return this;
	},

	/**
	 * Render an individual image.
	 *
	 * @param {wp.media.View.GeneratedImage} image Individual image model.
	 */
	renderImage: function ( image ) {
		const view = new GeneratedImage( {
			model: image,
			prompt: this.prompt,
		} );
		this.$( 'ul' ).append( view.render().el );
	},

	/**
	 * Render all images.
	 */
	renderAll: function () {
		if ( this.collection.length < 1 ) {
			this.error();
			this.$el
				.prev()
				.find( '.error' )
				.text( classifaiDalleData.errorText );
		} else {
			this.$( '.prompt-text' ).removeClass( 'hidden' );
			this.$( '.prompt-text span' ).text( this.prompt );
			this.$( '.spinner' ).removeClass( 'active' );

			this.collection.each( this.renderImage, this );

			this.$el.prev().find( '.prompt' ).val( '' );
			this.$el.prev().find( 'button' ).prop( 'disabled', false );
		}
	},

	error: function () {
		this.$( '.spinner' ).removeClass( 'active' );
		this.$el.prev().find( 'button' ).prop( 'disabled', false );
	},
} );

export default GeneratedImagesContainer;
