/* global tinyMCE */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import './index.scss';

const ClassifAI = window.ClassifAI || {};
const classifaiExcerptData = window.classifaiGenerateExcerpt || {};

( function ( $ ) {
	$( document ).ready( () => {
		if ( document.getElementById( 'postexcerpt' ) ) {
			generateExcerptInit();
		}
	} );

	/**
	 * This function is solely responsible for rendering, generating
	 * and applying the generated excerpt in the classic editor.
	 */
	function generateExcerptInit() {
		const excerptContainer = document.getElementById( 'excerpt' );
		const isProduct =
			document.getElementById( 'post_type' )?.value === 'product';

		let buttonText;
		if ( isProduct ) {
			buttonText = excerptContainer.value
				? __( 'Re-generate short description', 'classifai' )
				: __( 'Generate short description', 'classifai' );
		} else {
			buttonText = excerptContainer.value
				? classifaiExcerptData?.regenerateText ?? ''
				: classifaiExcerptData?.buttonText ?? '';
		}

		const buttonWidth = isProduct ? 'fit-content' : '100%';

		// Boolean indicating whether generation is in progress.
		let isProcessing = false;

		// Creates and appends the "Generate excerpt" button.
		$( '<span />', {
			text: buttonText,
			class: 'classifai-excerpt-generation__excerpt-generate-btn--text',
		} )
			.wrap(
				`<div class="button" id="classifai-excerpt-generation__excerpt-generate-btn" style="width: ${ buttonWidth } ;" />`
			)
			.parent()
			.append(
				$( '<span />', {
					class: 'classifai-excerpt-generation__excerpt-generate-btn--spinner',
				} )
			)
			.insertAfter( excerptContainer );

		$( '<p>', {
			class: 'classifai-excerpt-generation__excerpt-generate-error',
		} ).insertAfter(
			document.getElementById(
				'classifai-excerpt-generation__excerpt-generate-btn'
			)
		);

		// Append disable feature link.
		if (
			ClassifAI?.opt_out_enabled_features?.includes(
				'feature_excerpt_generation'
			)
		) {
			$( '<a>', {
				text: __( 'Disable this ClassifAI feature', 'classifai' ),
				href: ClassifAI?.profile_url,
				target: '_blank',
				rel: 'noopener noreferrer',
				class: 'classifai-disable-feature-link',
			} )
				.wrap(
					`<div class="classifai-excerpt-generation__excerpt-generate-disable-link" />`
				)
				.parent()
				.insertAfter(
					document.getElementById(
						'classifai-excerpt-generation__excerpt-generate-btn'
					)
				);
		}

		// The current post ID.
		const postId = $( '#post_ID' ).val();

		// Callback to generate the excerpt.
		const generateExcerpt = () => {
			if ( isProcessing ) {
				return;
			}

			const generateTextEl = $(
				'.classifai-excerpt-generation__excerpt-generate-btn--text'
			);
			const spinnerEl = $(
				'.classifai-excerpt-generation__excerpt-generate-btn--spinner'
			);
			const errorEl = $(
				'.classifai-excerpt-generation__excerpt-generate-error'
			);

			generateTextEl.css( 'opacity', '0' );
			spinnerEl.show();
			errorEl.text( '' ).hide();
			isProcessing = true;

			const path = classifaiExcerptData?.path + postId;

			apiFetch( {
				path,
			} )
				.then( ( result ) => {
					generateTextEl.css( 'opacity', '1' );
					spinnerEl.hide();
					isProcessing = false;

					if ( isProduct ) {
						// For WooCommerce products, we need to update the TinyMCE editor
						if (
							typeof tinyMCE !== 'undefined' &&
							tinyMCE.get( 'excerpt' )
						) {
							tinyMCE.get( 'excerpt' ).setContent( result );
						} else {
							$( excerptContainer ).val( result );
						}
					} else {
						$( excerptContainer ).val( result );
					}
					$( excerptContainer ).trigger( 'input' );
					generateTextEl.text(
						isProduct
							? __( 'Re-generate short description', 'classifai' )
							: classifaiExcerptData?.regenerateText ?? ''
					);
				} )
				.catch( ( error ) => {
					generateTextEl.css( 'opacity', '1' );
					spinnerEl.hide();
					isProcessing = false;
					errorEl.text( error?.message ).show();
				} );
		};

		// Event handler registration to generate the excerpt.
		$( document ).on(
			'click',
			'#classifai-excerpt-generation__excerpt-generate-btn',
			generateExcerpt
		);
	}
} )( jQuery );
