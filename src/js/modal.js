/* eslint object-shorthand: 0 */

const oldMediaFrame = wp.media.view.MediaFrame.Select;

const Image = Backbone.Model.extend( {
	defaults: {
		url: '',
	},
} );

const Images = Backbone.Collection.extend( {
	model: Image,

	url: 'https://oss.test/wp-json/classifai/v1/openai/generate-image', // TODO don't hardcode this

	makeRequest: function ( prompt ) {
		this.fetch( {
			type: 'get',
			beforeSend: function ( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce ); //TODO ensure this library is loaded
			},
			data: {
				prompt: prompt,
				n: 1, // TODO don't hardcode these
				size: '256x256',
			},
			reset: true,
			error: function ( collection, response ) {
				console.log( response );
				new ErrorMessage( { error: response.responseJSON.message } );
			},
		} );
	},
} );

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
				text: 'Generate image',
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

const Prompt = wp.media.View.extend( {
	template: _.template( '<div class="generated-images"></div><div class="prompt-view"><input type="search" class="prompt" placeholder="Enter prompt"><button type="button" class="button media-button button-secondary button-large media-button-select">Generate images</button><span class="error"></span></div>' ), // wp.template( 'prompt' )

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

const GeneratedImage = wp.media.View.extend( {
	tagName: 'li',
	template: _.template( '<div class="generated-image"><img src="<%= url %>" /><button type="button" class="button media-button button-secondary button-large media-button-select">Import</button></div>' ), // wp.template( 'generated-images' )

	render: function () {
		this.$el.html( this.template( this.model.toJSON() ) );
		return this;
	},
} );

const GeneratedImagesContainer = wp.media.View.extend( {
	el: '.generated-images',
	template: _.template( '<%= text %>' ), // wp.template( 'generated-images' )

	initialize: function ( options ) {
		this.collection = new Images();

		this.listenTo( this.collection, 'reset', this.renderAll );

		this.collection.makeRequest( options.prompt );
		this.render();
	},

	render: function () {
		this.$el.html( this.template( { text: 'Loading...' } ) );
		return this;
	},

	renderImage: function ( image ) {
		const view = new GeneratedImage( { model: image } );
		this.$( 'ul' ).append( view.render().el );
	},

	renderAll: function () {
		this.$el.html( this.template( { text: '<ul></ul>' } ) );
		this.collection.each( this.renderImage, this );
	},
} );

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
