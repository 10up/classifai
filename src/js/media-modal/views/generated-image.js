/* eslint object-shorthand: 0 */

const { uploadMedia } = wp.mediaUtils;
const { cleanForSlug } = wp.url;

/**
 * View to render a single generated image.
 *
 * This renders out an individual image as well
 * as handles importing that image into the Media Library.
 */
const GeneratedImage = wp.media.View.extend( {
	tagName: 'li',
	template: wp.template( 'dalle-image' ),

	events: {
		'click .button-import': 'import',
		'click .button-media-library': 'loadMediaLibrary',
	},

	/**
	 * Initialize the view.
	 *
	 * @param {Object} options Options passed to the view.
	 */
	initialize: function( options ) {
		this.data = this.model.toJSON();
		this.prompt = options.prompt;
		this.fileName = cleanForSlug( this.prompt );
	},

	/**
	 * Render the view.
	 */
	render: function() {
		this.$el.html( this.template( this.data ) );
		return this;
	},

	/**
	 * Event tied to the import button.
	 *
	 * Attempts to download the chosen image to
	 * your site.
	 */
	import: async function() {
		const self = this;
		this.enableLoadingState();

		const blob = await this.convertImageToBlob( this.data.url );

		if ( ! blob ) {
			this.$( '.error' ).text( classifaiDalleData.errorText );
			return;
		}

		const status = await uploadMedia( {
			filesList: [ new File( [ blob ], this.fileName + '.png' ) ],
			onFileChange: function( [ fileObj ] ) {
				if ( fileObj && fileObj.id ) {
					self.file = fileObj;

					self.$( '.button-import' )
						.removeClass( 'button-import' )
						.addClass( 'button-media-library' )
						.text( classifaiDalleData.buttonText );
					self.disableLoadingState();
				}
			},
			onError: function( error ) {
				self.disableLoadingState();
				self.$( '.error' ).text( error );
			},
		} );

		return status;
	},

	/**
	 * Event tied to the media library button.
	 *
	 * Adds the imported image to the Attachments
	 * controller and switches to the Media Library
	 * tab.
	 */
	loadMediaLibrary: async function() {
		this.enableLoadingState();

		// Turn our uploaded file into an Attachment model.
		// Allows us to fetch the proper attachment data.
		const Attachment = wp.media.model.Attachment;
		this.attachment = await Attachment.get( this.file.id ).fetch();

		// Create a new Attachment model to trigger the queue.
		// Note most of this logic was copied from wp-plupload.js.
		const attributes = {
			file: this.attachment,
			uploading: true,
			date: new Date(),
			filename: this.fileName + '.png',
			menuOrder: 0,
			loaded: 0,
			percent: 0,
			uploadedTo: wp.media.model.settings.post.id,
		};

		const attachment = wp.media.model.Attachment.create( attributes );
		wp.Uploader.queue.add( attachment );

		// Re-fetch the model and clear the queue.
		_.each( [ 'file', 'loaded', 'size', 'percent' ], function( key ) {
			attachment.unset( key );
		} );

		attachment.set( _.extend( this.attachment, { uploading: false } ) );
		wp.media.model.Attachment.get( this.file.id, attachment );
		wp.Uploader.queue.reset();

		this.disableLoadingState();

		// Trigger a click on the Media Library tab.
		jQuery( '#menu-item-browse' ).click();
	},

	/**
	 * Enable the loading state.
	 */
	enableLoadingState: function() {
		const $buttons = this.$el.parent( 'ul' ).find( 'button' );
		const $spinner = this.$( '.spinner' );

		// Set loading state.
		$buttons.prop( 'disabled', true );
		$spinner.addClass( 'active' );
	},

	/**
	 * Disable the loading state.
	 */
	disableLoadingState: function() {
		const $buttons = this.$el.parent( 'ul' ).find( 'button' );
		const $spinner = this.$( '.spinner' );

		$buttons.prop( 'disabled', false );
		$spinner.removeClass( 'active' );
	},

	/**
	 * Convert an image to a blob object.
	 *
	 * @param {string} base64Image base64 encoded image.
	 */
	convertImageToBlob: async function( base64Image ) {
		const image = new Image(); // eslint-disable-line no-undef
		image.src = `data:image/png;base64,${ base64Image }`;
		image.crossOrigin = 'anonymous';
		await this.loadImage( image );

		const canvas = document.createElement( 'canvas' );
		canvas.width = image.width;
		canvas.height = image.height;

		const ctx = canvas.getContext( '2d' );
		if ( ! ctx ) {
			return;
		}
		ctx.drawImage( image, 0, 0 );

		const finalBlob = await new Promise( ( resolve ) => {
			canvas.toBlob( ( blob ) => {
				blob && resolve( blob ); // eslint-disable-line no-unused-expressions
			}, 'image/jpeg' );
		} );

		return finalBlob;
	},

	loadImage: function( img ) {
		return new Promise( ( resolve ) => ( img.onload = resolve ) );
	},
} );

export default GeneratedImage;
