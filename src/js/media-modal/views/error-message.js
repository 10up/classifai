/* eslint object-shorthand: 0 */

/**
 * View to render an error message.
 */
const ErrorMessage = wp.media.View.extend( {
	el: '.error',

	/**
	 * Initialize the view.
	 *
	 * @param {Object} options Options passed to the view.
	 */
	initialize: function( options ) {
		this.$el.text( '' );
		this.render( options.error );
	},

	/**
	 * Render the view.
	 *
	 * @param {string} error Error text.
	 */
	render: function( error ) {
		this.$el.text( error );
		return this;
	},
} );

export default ErrorMessage;
