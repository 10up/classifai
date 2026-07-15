/* eslint object-shorthand: 0 */

/**
 * Internal dependencies
 */
import GeneratedImagesContainer from './generated-images-container';

/**
 * View to render the tab content.
 *
 * This contains the prompt input (and related functionality) as
 * well as basic HTML for the other containers (errors, images).
 */
const Prompt = wp.media.View.extend( {
	template: wp.template( 'dalle-prompt' ),

	events: {
		'click .button-generate': 'promptRequest',
		'keyup .prompt': 'promptRequest',
	},

	/**
	 * Render the view.
	 */
	render: function () {
		this.$el.html( this.template() );

		return this;
	},

	/**
	 * Event tied to the prompt input and button.
	 *
	 * When a prompt is submitted, trigger off a
	 * request.
	 *
	 * @param {Object} event
	 */
	promptRequest: function ( event ) {
		let prompt = '';
		const parent = event.target.parentElement;

		if ( event.which === 13 ) {
			prompt = event.target.value.trim();
		} else if ( event.target.nodeName === 'BUTTON' ) {
			prompt = parent.querySelector( '.prompt' ).value.trim();
		}

		const quality = parent?.querySelector( '.quality' )?.value.trim() || '';
		const size = parent?.querySelector( '.size' )?.value.trim() || '';
		const style = parent?.querySelector( '.style' )?.value.trim() || '';
		const aspectRatio =
			parent?.querySelector( '.aspect-ratio' )?.value.trim() || '';

		if ( prompt ) {
			new GeneratedImagesContainer( {
				prompt,
				quality,
				size,
				style,
				aspectRatio,
			} );
		}
	},
} );

export default Prompt;
