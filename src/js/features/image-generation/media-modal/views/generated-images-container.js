/* eslint object-shorthand: 0 */

import Images from '../collections/images';
import GeneratedImage from './generated-image';
import EventBus from '../events/event-bus';

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

		const { resultData = false } = options;

		if ( options?.isAsync ) {
			if ( false !== resultData && resultData.generated_images && Array.isArray( resultData.generated_images ) ) {
				// Defer until DOM is ready.
				setTimeout( () => {
					this.collection.reset( resultData.generated_images );
				}, 0 );
			} else {
				jQuery( document ).on( 'heartbeat-tick', ( event, data ) => {
					if ( false === data.continue_polling ) {
						EventBus.trigger( 'classifai:stop-polling' );
					}
	
					if ( data.generated_images && Array.isArray( data.generated_images ) ) {
						this.collection.reset( data.generated_images );
					}
				} );
			}

		} else {
			this.prompt = options.prompt;
			this.collection.makeRequest( options );
		}

		this.listenTo( this.collection, 'reset', this.renderAll );
		this.listenTo( this.collection, 'error', this.error );

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

			this.$el.prev().find( 'button' ).prop( 'disabled', false );
		}
	},

	error: function () {
		this.$( '.spinner' ).removeClass( 'active' );
		this.$el.prev().find( 'button' ).prop( 'disabled', false );
	},
} );

export default GeneratedImagesContainer;
