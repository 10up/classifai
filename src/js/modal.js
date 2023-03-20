/* eslint object-shorthand: 0 */

const oldMediaFrame = wp.media.view.MediaFrame.Select;

/**
 * Model to hold our image data.
 */
const Image = Backbone.Model.extend( {
	defaults: {
		url: '',
	},
} );

/**
 * Collection to hold all of our Image models.
 * This has the functionality to make an API request.
 */
const Images = Backbone.Collection.extend( {
	model: Image,

	url: wpApiSettings.root + classifaiDalleData.endpoint,

	makeRequest: function ( prompt ) {
		this.fetch( {
			type: 'get',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			},
			data: {
				prompt: prompt,
			},
			reset: true,
			error: function ( collection, response ) {
				new ErrorMessage( { error: response.responseJSON.message } );
			},
		} );
	},
} );

/**
 * Extends the core MediaFrame.Select view in order
 * to add our new tab and content.
 */
wp.media.view.MediaFrame.Select = oldMediaFrame.extend( {
	/**
	 * Bind region mode event callbacks.
	 *
	 * @see media.controller.Region.render
	 */
	bindHandlers: function () {
		oldMediaFrame.prototype.bindHandlers.apply( this, arguments );

		this.on( 'content:render:generate', this.generateContent, this );
	},

	/**
	 * Render callback for the router region in the `browse` mode.
	 *
	 * @param {wp.media.view.Router} routerView
	 */
	browseRouter: function ( routerView ) {
		oldMediaFrame.prototype.browseRouter.apply( this, arguments );

		routerView.set( {
			generate: {
				text: classifaiDalleData.tabText,
				priority: 60,
			},
		} );
	},

	/**
	 * Render callback for the content region in the `generate` mode.
	 */
	generateContent: function () {
		this.content.set( new Prompt().render() );
	},
} );

/**
 * View to render the tab content. This contains
 * the prompt input (and related functionality) as
 * well as basic HTML for the other containers (errors, images).
 */
const Prompt = wp.media.View.extend( {
	template: wp.template( 'dalle-prompt' ),

	events: {
		'click button': 'search',
		'keyup .prompt': 'search',
	},

	render: function () {
		this.$el.html( this.template() );

		return this;
	},

	search: function ( event ) {
		let prompt = '';

		if ( event.which === 13 ) {
			prompt = event.target.value.trim();
		} else if ( event.target.nodeName === 'BUTTON' ) {
			prompt = event.target.parentElement
				.querySelector( '.prompt' )
				.value.trim();
		}

		if ( prompt ) {
			new GeneratedImagesContainer( { prompt } );
		}
	},
} );

/**
 * View to render a single generated image.
 */
const GeneratedImage = wp.media.View.extend( {
	tagName: 'li',
	template: wp.template( 'dalle-image' ),

	render: function () {
		this.$el.html( this.template( this.model.toJSON() ) );
		return this;
	},
} );

/**
 * View to render out the generated images container.
 *
 * This uses the Images collection to make our API
 * request, showing a loading state and then rendering
 * the images.
 */
const GeneratedImagesContainer = wp.media.View.extend( {
	el: '.generated-images',

	initialize: function ( options ) {
		this.collection = new Images();
		this.prompt = options.prompt;

		this.listenTo( this.collection, 'reset', this.renderAll );

		this.collection.makeRequest( this.prompt );
		this.render();
	},

	render: function () {
		this.$el.prev().find( 'button' ).prop( 'disabled', true );
		this.$( 'ul' ).empty();
		this.$( '.spinner' ).addClass( 'active' );
		this.$( '.prompt-text' ).addClass( 'hidden' );
		return this;
	},

	renderImage: function ( image ) {
		const view = new GeneratedImage( { model: image } );
		this.$( 'ul' ).append( view.render().el );
	},

	renderAll: function () {
		this.$( '.prompt-text' ).removeClass( 'hidden' );
		this.$( '.prompt-text span' ).text( this.prompt );
		this.$( '.spinner' ).removeClass( 'active' );
		this.collection.each( this.renderImage, this );
		this.$el.prev().find( '.prompt' ).val( '' );
		this.$el.prev().find( 'button' ).prop( 'disabled', false );
	},
} );

/**
 * View to render an error message.
 */
const ErrorMessage = wp.media.View.extend( {
	el: '.error',

	initialize: function ( options ) {
		this.$el.text( '' );
		this.render( options.error );
	},

	render: function ( error ) {
		this.$el.text( error );
		return this;
	},
} );
