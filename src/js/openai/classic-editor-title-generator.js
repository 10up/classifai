import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import '../../scss/openai/classic-editor-title-generator.scss';

const ClassifAI = window.ClassifAI || {};
const classifaiChatGPTData = window.classifaiChatGPTData || {};
const scriptData = classifaiChatGPTData.enabledFeatures.reduce(
	( acc, cur ) => ( { [ cur.feature ]: cur } ),
	{}
);

( function ( $ ) {
	$( document ).ready( () => {
		if ( scriptData?.title ) {
			generateTitleInit();
		}
	} );

	/**
	 * Returns whether the post has unsaved changes or not.
	 *
	 * @return {boolean} Whether the post has unsaved change or not.
	 */
	function isPostChanged() {
		const editor = window.tinymce && window.tinymce.get( 'content' );
		let changed = false;

		if ( wp.autosave ) {
			changed = wp.autosave.server.postChanged();
		} else if ( editor ) {
			changed = ! editor.isHidden() && editor.isDirty();
		}
		return changed;
	}

	/**
	 * This function is solely responsible for rendering, generating
	 * and applying the generated title for the classic editor.
	 */
	function generateTitleInit() {
		// Boolean indicating whether title generation is in progress.
		let isProcessing = false;

		// Creates and appends the "Generate titles" button.
		$( '<span />', {
			text: scriptData?.title?.buttonText ?? '',
			class: 'classifai-openai__title-generate-btn--text',
		} )
			.wrap(
				'<div class="button" id="classifai-openai__title-generate-btn" />'
			)
			.parent()
			.append(
				$( '<span />', {
					class: 'classifai-openai__title-generate-btn--spinner',
				} )
			)
			.appendTo( '#titlewrap' );

		// The current post ID.
		const postId = $( '#post_ID' ).val();

		// Callback to hide the popup.
		const hidePopup = () => {
			$( '#classifai-openai__results' )
				.removeClass( 'classifai-openai--fade-in' )
				.delay( 300 )
				.fadeOut( 0 );
		};

		// Callback to apply the title from the result to the post title.
		const applyTitle = ( e ) => {
			const selectBtnEl = $( e.target );
			const textarea = selectBtnEl
				.closest( '.classifai-openai__result-item' )
				.find( 'textarea' );
			const isDirty = isPostChanged();
			$( '#title' ).val( textarea.val() ).trigger( 'input' );
			if ( ! isDirty && wp.autosave ) {
				wp.autosave.server.triggerSave();
			}
			hidePopup();
		};

		// Callback to generate the title.
		const generateTitle = () => {
			if ( isProcessing ) {
				return;
			}

			$( '#classifai-openai__results-content' ).html( '' );
			const generateTextEl = $(
				'.classifai-openai__title-generate-btn--text'
			);
			const spinnerEl = $(
				'.classifai-openai__title-generate-btn--spinner'
			);

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

					result.forEach( ( title ) => {
						$( '<textarea>', {
							text: title,
						} )
							.wrap(
								`<div class="classifai-openai__result-item" />`
							)
							.parent()
							.append(
								$( '<button />', {
									text: scriptData.title.selectBtnText,
									type: 'button',
									class: 'button classifai-openai__select-title',
								} )
							)
							.appendTo( '#classifai-openai__results-content' );
					} );

					// Append disable feature link.
					if (
						ClassifAI?.opt_out_enabled_features?.includes(
							'feature_title_generation'
						)
					) {
						$( '<a>', {
							text: __(
								'Disable this ClassifAI feature',
								'classifai'
							),
							href: ClassifAI?.profile_url,
							target: '_blank',
							rel: 'noopener noreferrer',
							class: 'classifai-disable-feature-link',
						} )
							.wrap(
								`<div class="classifai-openai__result-disable-link" />`
							)
							.parent()
							.appendTo( '#classifai-openai__modal' );
					}

					$( '#classifai-openai__results' )
						.show()
						.addClass( 'classifai-openai--fade-in' );
				} )
				.catch( ( error ) => {
					generateTextEl.css( 'opacity', '1' );
					spinnerEl.hide();
					isProcessing = false;

					$( '<span class="error">' )
						.text( error?.message )
						.wrap( `<div class="classifai-openai__result-item" />` )
						.appendTo( '#classifai-openai__results-content' );

					$( '#classifai-openai__results' )
						.show()
						.addClass( 'classifai-openai--fade-in' );
				} );
		};

		// Event handler registration to generate the title.
		$( document ).on(
			'click',
			'#classifai-openai__title-generate-btn',
			generateTitle
		);

		// Event handler registration to hide the popup.
		$( document ).on( 'click', '#classifai-openai__overlay', hidePopup );
		$( document ).on(
			'click',
			'#classifai-openai__close-modal-button',
			hidePopup
		);

		// Event handler registration to apply the selected title to the post title.
		$( document ).on(
			'click',
			'.classifai-openai__select-title',
			applyTitle
		);

		// Sets the modal title.
		const resultWrapper = $( '#classifai-openai__results' );
		resultWrapper
			.find( '#classifai-openai__results-title' )
			.text( scriptData.title.modalTitle );
	}
} )( jQuery );
