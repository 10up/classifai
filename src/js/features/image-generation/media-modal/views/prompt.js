/* eslint object-shorthand: 0 */

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
		let quality = '';
		let size = '';
		let style = '';
		const parent = event.target.parentElement;

		if ( event.which === 13 ) {
			prompt = event.target.value.trim();
		} else if ( event.target.nodeName === 'BUTTON' ) {
			// Ensure parent exists before querying elements.
			if ( parent ) {
				const promptEl = parent.querySelector( '.prompt' );
				const qualityEl = parent.querySelector( '.quality' );
				const sizeEl = parent.querySelector( '.size' );
				const styleEl = parent.querySelector( '.style' );

				// Check if elements exist before accessing .value
				prompt = promptEl ? promptEl.value.trim() : '';
				quality = qualityEl ? qualityEl.value.trim() : '';
				size = sizeEl ? sizeEl.value.trim() : '';
				style = styleEl ? styleEl.value.trim() : '';
			}
		}

		if ( prompt ) {
			new GeneratedImagesContainer( { prompt, quality, size, style } );
		}
	},
} );

export default Prompt;
