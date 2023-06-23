import apiFetch from '@wordpress/api-fetch';
import '../../scss/openai/classic-editor-title-generator.scss';

const classifaiChatGPTData = window.classifaiChatGPTData || {};
let scriptData = classifaiChatGPTData.enabledFeatures.reduce( ( acc, cur ) => ( { [ cur.feature ] : cur } ), {} );

( function( $ ) {
	$( document ).ready( () => {
		if ( scriptData?.title ) {
			generateTitleInit();
		}
	} );

	/**
	 * This function is solely responsibe for rendering, generating
	 * and applying the generated title for the classic editor.
	 */
	function generateTitleInit() {
		// Boolean indicating whether title generation is in progress.
		let isProcessing = false;

		// Creates and appens the "Generate titles" button.
		$( '<span />', {
			text: scriptData?.title?.buttonText ?? '',
			'class': 'classifai-openai__title-generate-btn--text',
		} )
		.wrap( '<div class="button" id="classifai-openai__title-generate-btn" />' )
		.parent()
		.append( $( '<span />', {
			'class': 'classifai-openai__title-generate-btn--spinner',
		} ) )
		.appendTo( '#titlewrap' )

		// The current post ID.
		const postId = $( '#post_ID' ).val();

		// Callback to hide the popup.
		const hidePopup = () => {
			$( '#classifai-openai__results' ).removeClass( 'classifai-openai--fade-in' ).delay(300).fadeOut(0);
		}

		// Callback to apply the title from the result to the post title.
		const applyTitle = ( e ) => {
			const selectBtnEl = $( e.target );
			const textarea = selectBtnEl.closest( '.classifai-openai__result-item' ).find( 'textarea' );

			$( '#title' ).val( textarea.val() ).trigger( 'input' );
			hidePopup();
		}

		// Callback to generate the title.
		const generateTitle = () => {
			if ( isProcessing ) {
				return;
			}

			$( '#classifai-openai__results-content' ).html( '' );
			const generateTextEl = $( '.classifai-openai__title-generate-btn--text' );
			const spinnerEl = $( '.classifai-openai__title-generate-btn--spinner' );

			generateTextEl.css( 'opacity', '0' );
			spinnerEl.show();
			isProcessing = true;

			const path = scriptData.title?.path + postId;

			apiFetch( {
				path,
			} )
			.then( ( result ) => {
				generateTextEl.css( 'opacity', '1' );
				spinnerEl.hide();
				isProcessing = false;

				result.forEach( ( title, index ) => {
					$( '<textarea>', {
						text: title
					} )
					.wrap( `<div class="classifai-openai__result-item" data-result-item-index="${index}" />` )
					.parent()
					.append( $( '<button />', {
						text: scriptData.title.selectBtnText,
						type: 'button',
						'class': 'button classifai-openai__select-title',
						'data-result-title-index': index
					} ) )
					.appendTo( '#classifai-openai__results-content' );
				} );

				$( '#classifai-openai__results' ).show().addClass( 'classifai-openai--fade-in' );
			} );
		};

		// Event handler registration to generate the title.
		$( document ).on( 'click', '#classifai-openai__title-generate-btn', generateTitle );

		// Event handler registration to hide the popup.
		$( document ).on( 'click', '#classifai-openai__overlay', hidePopup );
		$( document ).on( 'click', '#classifai-openai__close-modal-button', hidePopup );

		// Event handler registration to apply the selected title to the post title.
		$( document ).on( 'click', '.classifai-openai__select-title', applyTitle );

		// Sets the modal title.
		const resultWrapper = $( '#classifai-openai__results' );
		resultWrapper
			.find( '#classifai-openai__results-title' )
			.text( scriptData.title.modalTitle );
	}
} ( jQuery ) );
