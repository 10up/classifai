import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import './index.scss';

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
			class: 'classifai-title-generation__title-generate-btn--text',
		} )
			.wrap(
				'<div class="button" id="classifai-title-generation__title-generate-btn" />'
			)
			.parent()
			.append(
				$( '<span />', {
					class: 'classifai-title-generation__title-generate-btn--spinner',
				} )
			)
			.appendTo( '#titlewrap' );

		// The current post ID.
		const postId = $( '#post_ID' ).val();

		// Callback to hide the popup.
		const hidePopup = () => {
			$( '#classifai-title-generation__results' )
				.removeClass( 'classifai-title-generation--fade-in' )
				.delay( 300 )
				.fadeOut( 0 );
		};

		// Callback to apply the title from the result to the post title.
		const applyTitle = ( e ) => {
			const selectBtnEl = $( e.target );
			const textarea = selectBtnEl
				.closest( '.classifai-title-generation__result-item' )
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

			$( '#classifai-title-generation__results-content' ).html( '' );
			const generateTextEl = $(
				'.classifai-title-generation__title-generate-btn--text'
			);
			const spinnerEl = $(
				'.classifai-title-generation__title-generate-btn--spinner'
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
								`<div class="classifai-title-generation__result-item" />`
							)
							.parent()
							.append(
								$( '<button />', {
									text: scriptData.title.selectBtnText,
									type: 'button',
									class: 'button classifai-title-generation__select-title',
								} )
							)
							.appendTo( '#classifai-title-generation__results-content' );
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
								`<div class="classifai-title-generation__result-disable-link" />`
							)
							.parent()
							.appendTo( '#classifai-title-generation__modal' );
					}

					$( '#classifai-title-generation__results' )
						.show()
						.addClass( 'classifai-title-generation--fade-in' );
				} )
				.catch( ( error ) => {
					generateTextEl.css( 'opacity', '1' );
					spinnerEl.hide();
					isProcessing = false;

					$( '<span class="error">' )
						.text( error?.message )
						.wrap( `<div class="classifai-title-generation__result-item" />` )
						.appendTo( '#classifai-title-generation__results-content' );

					$( '#classifai-title-generation__results' )
						.show()
						.addClass( 'classifai-title-generation--fade-in' );
				} );
		};

		// Event handler registration to generate the title.
		$( document ).on(
			'click',
			'#classifai-title-generation__title-generate-btn',
			generateTitle
		);

		// Event handler registration to hide the popup.
		$( document ).on( 'click', '#classifai-title-generation__overlay', hidePopup );
		$( document ).on(
			'click',
			'#classifai-title-generation__close-modal-button',
			hidePopup
		);

		// Event handler registration to apply the selected title to the post title.
		$( document ).on(
			'click',
			'.classifai-title-generation__select-title',
			applyTitle
		);

		// Sets the modal title.
		const resultWrapper = $( '#classifai-title-generation__results' );
		resultWrapper
			.find( '#classifai-title-generation__results-title' )
			.text( scriptData.title.modalTitle );
	}
} )( jQuery );
