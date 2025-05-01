/* eslint object-shorthand: 0 */

import GeneratedImagesContainer from './generated-images-container';

/**
 * View to render the tab content.
 *
 * This contains the prompt input (and related functionality) as
 * well as basic HTML for the other containers (errors, images).
 */
const Prompt = wp.media.View.extend( {
	template: wp.template( 'classifai-image-generation' ),

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
		const fieldValueMap = {};

		if ( event.which === 13 ) {
			prompt = event.target.value.trim();
		} else if ( event.target.nodeName === 'BUTTON' ) {
			const imageGenFields = parent.querySelectorAll( '[data-image-gen-setting]' );

			[ ...imageGenFields ].forEach( ( item ) => {
				fieldValueMap[ item.name ] = item.value.trim();
			} );
		}

		if ( fieldValueMap?.prompt ) {
			new GeneratedImagesContainer( fieldValueMap );
		}
	},
} );

export default Prompt;
