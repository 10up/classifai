/* eslint object-shorthand: 0 */

import GeneratedImagesContainer from './generated-images-container';
import EventBus from '../events/event-bus';

/**
 * View to render the tab content.
 *
 * This contains the prompt input (and related functionality) as
 * well as basic HTML for the other containers (errors, images).
 */
const Prompt = wp.media.View.extend( {
	options: {
		imageGenerationMode: 'sync',
		imageGenerationProvider: null,
	},

	template: wp.template( 'classifai-image-generation' ),

	initialize: function() {
		this.listenTo( this, 'ready', function() {
			this.imageGenerationMode = this.$el.find( '[name="image-generation-mode"]' ).val();

			if ( 'sync' === this.imageGenerationMode ) {
				return;
			}

			this.imageGenerationProvider = this.$el.find( '[name="image-generation-provider"]' ).val();
			this.postId = jQuery( '[name="post_ID"]' ).val();

			if ( this.imageGenerationProvider && ! Prompt.hasPolled ) {
				this.pollResults(
					this.postId,
					this.imageGenerationProvider,
				);

				Prompt.hasPolled = true;
			}
		} );
	},

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
		const fieldValueMap = {
			post_id: jQuery( '[name="post_ID"]' ).val(),
		};

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

	/**
	 * Polls for image generation async results.
	 *
	 * @param {Number} postId The Post ID.
	 * @param {String} provider ID of the Provider.
	 */
	pollResults: async function( postId, provider ) {
		/**
		 * Fires as soon as the `Generate Image` tab is in view.
		 * This is so that we don't wait for the first heartbeat-tick
		 * to trigger.
		 *
		 * If this calls retrieves the results, then the heartbeat polling
		 * is skipped.
		 */
		const initPollResults = await jQuery.ajax( {
			url: ajaxurl,
			method: 'POST',
			data: {
				action: 'classifai_check_image_generation_results',
				classifai_post_id: postId,
				classifai_provider: provider,
			}
		} );

		let resultData = false;

		if ( initPollResults.success ) {
			resultData = initPollResults.data;
		} else {
			this.heartbeatSendHandler = function ( event, data ) {
				data.classifai_action = 'classifai_check_image_generation_results';
				data.classifai_post_id = postId;
				data.classifai_provider = provider;
			}
	
			jQuery( document ).on( 'heartbeat-send', this.heartbeatSendHandler );
		}

		EventBus.on( 'classifai:stop-polling', () => {
			jQuery( document ).off( 'heartbeat-send', this.heartbeatSendHandler );
		} );

		new GeneratedImagesContainer( {
			isAsync: true,
			resultData,
		} );
	}
} );

export default Prompt;
