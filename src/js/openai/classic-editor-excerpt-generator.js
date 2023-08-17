import apiFetch from '@wordpress/api-fetch';
import '../../scss/openai/classic-editor-title-generator.scss';

const classifaiExcerptData = window.classifaiGenerateExcerpt || {};

( function ( $ ) {
	$( document ).ready( () => {
		if ( document.getElementById( 'postexcerpt' ) ) {
			generateExcerptInit();
		}
	} );

	/**
	 * This function is solely responsibe for rendering, generating
	 * and applying the generated excerpt in the classic editor.
	 */
	function generateExcerptInit() {
		const excerptContainer = document.getElementById( 'excerpt' );

		// Boolean indicating whether generation is in progress.
		let isProcessing = false;

		// Creates and appends the "Generate excerpt" button.
		$( '<span />', {
			text: excerptContainer.value
				? classifaiExcerptData?.regenerateText ?? ''
				: classifaiExcerptData?.buttonText ?? '',
			class: 'classifai-openai__excerpt-generate-btn--text',
		} )
			.wrap(
				'<div class="button" id="classifai-openai__excerpt-generate-btn" />'
			)
			.parent()
			.append(
				$( '<span />', {
					class: 'classifai-openai__excerpt-generate-btn--spinner',
				} )
			)
			.insertAfter( excerptContainer );

		// The current post ID.
		const postId = $( '#post_ID' ).val();

		// Callback to generate the excerpt.
		const generateExcerpt = () => {
			if ( isProcessing ) {
				return;
			}

			const generateTextEl = $(
				'.classifai-openai__excerpt-generate-btn--text'
			);
			const spinnerEl = $(
				'.classifai-openai__excerpt-generate-btn--spinner'
			);

			generateTextEl.css( 'opacity', '0' );
			spinnerEl.show();
			isProcessing = true;

			const path = classifaiExcerptData?.path + postId;

			apiFetch( {
				path,
			} ).then( ( result ) => {
				generateTextEl.css( 'opacity', '1' );
				spinnerEl.hide();
				isProcessing = false;

				$( excerptContainer ).val( result ).trigger( 'input' );
				generateTextEl.text(
					classifaiExcerptData?.regenerateText ?? ''
				);
			} );
		};

		// Event handler registration to generate the excerpt.
		$( document ).on(
			'click',
			'#classifai-openai__excerpt-generate-btn',
			generateExcerpt
		);
	}
} )( jQuery );
