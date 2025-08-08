/* global tinyMCE */

import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
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

		// Get target field settings and value.
		let targetFieldSettings = null;
		let targetFieldValue = '';
		const postId = $( '#post_ID' ).val();

		// Creates and appends the "Generate excerpt" button.
		const regenerateButton = $( '<span />', {
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
			);

		// Insert the button after the excerpt container, but we'll move it later if custom field is enabled.
		regenerateButton.insertAfter( excerptContainer );

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

		// Get target field settings.
		apiFetch( {
			path: '/classifai/v1/get-target-field-settings/',
		} ).then( ( result ) => {
			targetFieldSettings = result;

			// If custom field is enabled, hide the default excerpt field and show custom field info.
			if ( targetFieldSettings && targetFieldSettings.field_type !== 'post_excerpt' ) {
				hideDefaultExcerptField();
				showCustomFieldInfo();
			}
		} ).catch( () => {
			// If endpoint doesn't exist, use default settings.
			targetFieldSettings = {
				field_type: 'post_excerpt',
				field_name: __( 'Default excerpt field', 'classifai' ),
			};
		} );

		// Function to hide the default excerpt field.
		function hideDefaultExcerptField() {
			// Hide the default excerpt textarea and label, but keep the #postexcerpt container.
			const excerptTextarea = $( '#excerpt' );
			const excerptLabel = $( 'label[for="excerpt"]' );
			
			if ( excerptTextarea.length ) {
				excerptTextarea.hide();
			}
			
			if ( excerptLabel.length ) {
				excerptLabel.hide();
			}
			
			// Hide any other default excerpt content, but not the regenerate button.
			const defaultExcerptContent = $( '#postexcerpt .inside' );
			if ( defaultExcerptContent.length ) {
				defaultExcerptContent.hide();
			}
			
			// Make sure the regenerate button is visible.
			const regenerateButton = $( '#classifai-excerpt-generation__excerpt-generate-btn' );
			if ( regenerateButton.length ) {
				regenerateButton.show();
			}
		}

		// Function to show custom field info.
		function showCustomFieldInfo() {
			if ( ! targetFieldSettings || targetFieldSettings.field_type === 'post_excerpt' ) {
				return;
			}

			// Get the current value.
			apiFetch( {
				path: `/classifai/v1/get-target-field-value/${ postId }`,
			} ).then( ( result ) => {
				targetFieldValue = result.value || '';
				
				// Create custom field info container.
				const customFieldContainer = $( '<div>', {
					class: 'classifai-custom-excerpt-info',
					style: 'margin: 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;',
				} );

				const label = $( '<label>', {
					text: targetFieldSettings.field_name || __( 'Custom excerpt', 'classifai' ),
					style: 'display: block; margin-bottom: 8px; font-weight: 600; color: #23282d;',
				} );

				const textarea = $( '<textarea>', {
					id: 'classifai-custom-excerpt',
					text: targetFieldValue, // Use text() instead of value for textarea.
					style: 'width: 100%; min-height: 120px; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-family: inherit; font-size: 14px; line-height: 1.4; resize: vertical; background: #f5f5f5;',
					readonly: true,
					placeholder: __( 'This field is read-only. The value is stored in a custom field.', 'classifai' ),
				} );

				const notice = $( '<div>', {
					class: 'notice notice-info',
					style: 'margin-top: 10px;',
				} ).append(
					$( '<p>', {
						html: ( targetFieldSettings.field_type === 'acf_field' 
							? __( 'This excerpt is stored in an ACF field. To edit it, you can:', 'classifai' )
							: __( 'This excerpt is stored in a custom field. To edit it, you can:', 'classifai' )
						) +
							'<ul style="margin: 5px 0 0 20px;">' +
							'<li>' + __( 'Use the regenerate excerpt button to regenerate it', 'classifai' ) + '</li>' +
							( targetFieldSettings.field_type === 'acf_field'
								? '<li>' + __( 'Edit the ACF field directly in the post editor or ACF fields panel', 'classifai' ) + '</li>'
								: '<li>' + __( 'Edit the custom field directly in the post editor or custom fields panel', 'classifai' ) + '</li>'
							) +
							'<li>' + __( 'Change the target field in ', 'classifai' ) + 
							'<a href="' + window.location.origin + '/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_excerpt_generation" target="_blank" rel="noopener noreferrer">' + 
							__( 'ClassifAI Settings', 'classifai' ) + '</a></li>' +
							'</ul>'
					} )
				);

				customFieldContainer.append( label, textarea, notice );

				// Insert inside the #postexcerpt div.
				const postExcerptDiv = $( '#postexcerpt' );
				if ( postExcerptDiv.length ) {
					postExcerptDiv.append( customFieldContainer );
					
					// Move the regenerate button to after the custom field container.
					const regenerateButton = $( '#classifai-excerpt-generation__excerpt-generate-btn' );
					if ( regenerateButton.length ) {
						regenerateButton.detach().insertAfter( customFieldContainer ).show();
					}
					
					// Move the error message as well.
					const errorMessage = $( '.classifai-excerpt-generation__excerpt-generate-error' );
					if ( errorMessage.length ) {
						errorMessage.detach().insertAfter( regenerateButton );
					}
					
					// Move the disable link as well
					const disableLink = $( '.classifai-excerpt-generation__excerpt-generate-disable-link' );
					if ( disableLink.length ) {
						disableLink.detach().insertAfter( errorMessage );
					}
				} else {
					// Fallback: insert after the title
					$( '#title' ).closest( 'tr' ).after( customFieldContainer );
				}

				// Set up listeners for meta field changes
				setupMetaFieldListenersAfterCustomField();
			} ).catch( () => {
				// If error, show custom field info with empty value.
				showCustomFieldInfoWithValue( '' );
			} );
		}

		// Function to show custom field info with a specific value
		function showCustomFieldInfoWithValue( value ) {
			if ( ! targetFieldSettings || targetFieldSettings.field_type === 'post_excerpt' ) {
				return;
			}

			const customFieldContainer = $( '<div>', {
				class: 'classifai-custom-excerpt-info',
				style: 'margin: 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;',
			} );

			const label = $( '<label>', {
				text: targetFieldSettings.field_name || __( 'Custom excerpt', 'classifai' ),
				style: 'display: block; margin-bottom: 8px; font-weight: 600; color: #23282d;',
			} );

			const textarea = $( '<textarea>', {
				id: 'classifai-custom-excerpt',
				text: value,
				style: 'width: 100%; min-height: 120px; padding: 8px; border: 1px solid #ddd; border-radius: 3px; font-family: inherit; font-size: 14px; line-height: 1.4; resize: vertical; background: #f5f5f5;',
				readonly: true,
				placeholder: __( 'This field is read-only. The value is stored in a custom field.', 'classifai' ),
			} );

			const notice = $( '<div>', {
				class: 'notice notice-info',
				style: 'margin-top: 10px;',
			} ).append(
				$( '<p>', {
					html: ( targetFieldSettings.field_type === 'acf_field' 
						? __( 'This excerpt is stored in an ACF field. To edit it, you can:', 'classifai' )
						: __( 'This excerpt is stored in a custom field. To edit it, you can:', 'classifai' )
					) +
						'<ul style="margin: 5px 0 0 20px;">' +
						'<li>' + __( 'Use the regenerate excerpt button to regenerate it', 'classifai' ) + '</li>' +
						( targetFieldSettings.field_type === 'acf_field'
							? '<li>' + __( 'Edit the ACF field directly in the post editor or ACF fields panel', 'classifai' ) + '</li>'
							: '<li>' + __( 'Edit the custom field directly in the post editor or custom fields panel', 'classifai' ) + '</li>'
						) +
						'<li>' + __( 'Change the target field in ', 'classifai' ) + 
						'<a href="' + window.location.origin + '/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_excerpt_generation" target="_blank" rel="noopener noreferrer">' + 
						__( 'ClassifAI Settings', 'classifai' ) + '</a></li>' +
						'</ul>'
				} )
			);

			customFieldContainer.append( label, textarea, notice );

			// Insert inside the #postexcerpt div.
			const postExcerptDiv = $( '#postexcerpt' );
			if ( postExcerptDiv.length ) {
				postExcerptDiv.append( customFieldContainer );
				
				// Move the regenerate button to after the custom field container.
				const regenerateButton = $( '#classifai-excerpt-generation__excerpt-generate-btn' );
				if ( regenerateButton.length ) {
					regenerateButton.detach().insertAfter( customFieldContainer ).show();
				}
				
				// Move the error message as well.
				const errorMessage = $( '.classifai-excerpt-generation__excerpt-generate-error' );
				if ( errorMessage.length ) {
					errorMessage.detach().insertAfter( regenerateButton );
				}
				
				// Move the disable link as well.
				const disableLink = $( '.classifai-excerpt-generation__excerpt-generate-disable-link' );
				if ( disableLink.length ) {
					disableLink.detach().insertAfter( errorMessage );
				}
			} else {
				// Fallback: insert after the title.
				$( '#title' ).closest( 'tr' ).after( customFieldContainer );
			}

			// Set up listeners for meta field changes.
			setupMetaFieldListenersAfterCustomField();
		}

		// Function to generate the excerpt.
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
						// For WooCommerce products, we need to update the TinyMCE editor.
						if (
							typeof tinyMCE !== 'undefined' &&
							tinyMCE.get( 'excerpt' )
						) {
							tinyMCE.get( 'excerpt' ).setContent( result );
						} else {
							$( excerptContainer ).val( result );
						}
					} else {
						// Check if we should update the custom field or default excerpt.
						if ( targetFieldSettings && targetFieldSettings.field_type !== 'post_excerpt' ) {
							// Update custom field display first.
							const customField = $( '#classifai-custom-excerpt' );
							if ( customField.length ) {
								customField.text( result );
								targetFieldValue = result;
							}
							
							// Update visible meta field values on the page (this also updates custom field display).
							updateVisibleMetaFields( result );
						} else {
							// Update default excerpt field.
							$( excerptContainer ).val( result );
						}
					}
					$( excerptContainer ).trigger( 'input' );
					generateTextEl.text(
						isProduct
							? __( 'Re-generate short description', 'classifai' )
							: classifaiExcerptData?.regenerateText ?? ''
					);

					// Show notification about target field if different from excerpt.
					if ( classifaiExcerptData?.target_field_info ) {
						const targetInfo = classifaiExcerptData.target_field_info;
						if ( targetInfo.field_type !== 'post_excerpt' ) {
							const notification = $( '<div>', {
								class: 'notice notice-success',
								style: 'margin-top: 10px;',
							} ).append(
								$( '<p>', {
									text: __( 'Excerpt generated and saved to target field: ', 'classifai' ) + targetInfo.field_name,
								} )
							);
							$( excerptContainer ).after( notification );
							
							// Remove notification after 5 seconds.
							setTimeout( () => {
								notification.fadeOut();
							}, 5000 );
						}
					}
				} )
				.catch( ( error ) => {
					generateTextEl.css( 'opacity', '1' );
					spinnerEl.hide();
					isProcessing = false;
					errorEl.text( error?.message ).show();
				} );
		};

		// Function to update visible meta field values on the page.
		function updateVisibleMetaFields( value ) {
			if ( ! targetFieldSettings || targetFieldSettings.field_type === 'post_excerpt' ) {
				return;
			}

			const metaKey = targetFieldSettings.meta_key || targetFieldSettings.field_name;
			if ( ! metaKey ) {
				return;
			}

			// Update meta field input that is visible on the page.
			// Try multiple selectors to find the meta field.
			let metaInput = document.querySelector( `textarea[name="meta[${ metaKey }][value]"]` );
			
			// If not found with exact match, try a more flexible approach.
			if ( ! metaInput ) {
				const allMetaInputs = document.querySelectorAll( 'textarea[name*="[value]"]' );
				allMetaInputs.forEach( ( input ) => {
					const name = input.getAttribute( 'name' );
					if ( name && name.includes( '[value]' ) ) {
						// Check if this is the meta field we want to update.
						const row = input.closest( 'tr' );
						if ( row ) {
							const keyInput = row.querySelector( 'input[name*="[key]"]' );
							if ( keyInput && keyInput.value === metaKey ) {
								metaInput = input;
							}
						}
					}
				} );
			}

			if ( metaInput ) {
				metaInput.value = value;

				// Trigger input event to notify any listeners.
				const inputEvent = new Event( 'input', { bubbles: true } );
				metaInput.dispatchEvent( inputEvent );

				// Also trigger change event.
				const changeEvent = new Event( 'change', { bubbles: true } );
				metaInput.dispatchEvent( changeEvent );
			}

			// Also check for ACF fields if this is an ACF field.
			if ( targetFieldSettings.field_type === 'acf_field' && targetFieldSettings.meta_key ) {
				const acfInputs = document.querySelectorAll( `input[name*="${targetFieldSettings.meta_key}"], textarea[name*="${targetFieldSettings.meta_key}"]` );
				acfInputs.forEach( ( input ) => {
					input.value = value;
					
					// Trigger change events.
					const inputEvent = new Event( 'input', { bubbles: true } );
					input.dispatchEvent( inputEvent );
					
					const changeEvent = new Event( 'change', { bubbles: true } );
					input.dispatchEvent( changeEvent );
				} );
			}

			// Always update our custom field display, regardless of whether meta field was found.
			updateCustomFieldDisplay( value );
		}

		// Function to update the custom field display when meta field values change.
		function updateCustomFieldDisplay( value ) {
			const customField = $( '#classifai-custom-excerpt' );
			if ( customField.length ) {
				customField.text( value );
				targetFieldValue = value;
			} else {
				// If custom field is not found, try to find it with a more flexible selector.
				const customFieldAlt = $( '.classifai-custom-excerpt-info textarea' );
				if ( customFieldAlt.length ) {
					customFieldAlt.text( value );
					targetFieldValue = value;
				}
			}
		}

		// Function to listen for changes in meta fields and update our custom field display.
		function setupMetaFieldListeners() {
			if ( ! targetFieldSettings || targetFieldSettings.field_type === 'post_excerpt' ) {
				return;
			}

			const metaKey = targetFieldSettings.meta_key || targetFieldSettings.field_name;
			if ( ! metaKey ) {
				return;
			}

			// Listen for changes in meta field inputs (including both textarea and input for consistency).
			// Try multiple selectors to find the meta field.
			let metaInput = document.querySelector( `textarea[name="meta[${ metaKey }][value]"]` );
			
			// If not found with exact match, try a more flexible approach
			if ( ! metaInput ) {
				const allMetaInputs = document.querySelectorAll( 'textarea[name*="[value]"]' );
				allMetaInputs.forEach( ( input ) => {
					const name = input.getAttribute( 'name' );
					if ( name && name.includes( '[value]' ) ) {
						// Check if this is the meta field we want to update.
						const row = input.closest( 'tr' );
						if ( row ) {
							const keyInput = row.querySelector( 'input[name*="[key]"]' );
							if ( keyInput && keyInput.value === metaKey ) {
								metaInput = input;
							}
						}
					}
				} );
			}

			if ( metaInput ) {
				metaInput.addEventListener( 'input', ( event ) => {
					updateCustomFieldDisplay( event.target.value );
				} );
				
				metaInput.addEventListener( 'change', ( event ) => {
					updateCustomFieldDisplay( event.target.value );
				} );
			}

			// Listen for ACF field changes.
			if ( targetFieldSettings.field_type === 'acf_field' && targetFieldSettings.meta_key ) {
				const acfInputs = document.querySelectorAll( `input[name*="${targetFieldSettings.meta_key}"], textarea[name*="${targetFieldSettings.meta_key}"]` );
				acfInputs.forEach( ( input ) => {
					input.addEventListener( 'input', ( event ) => {
						updateCustomFieldDisplay( event.target.value );
					} );
					
					input.addEventListener( 'change', ( event ) => {
						updateCustomFieldDisplay( event.target.value );
					} );
				} );
			}
		}

		// Set up listeners when custom field info is shown.
		function setupMetaFieldListenersAfterCustomField() {
			if ( targetFieldSettings && targetFieldSettings.field_type !== 'post_excerpt' ) {
				// Small delay to ensure DOM elements are available.
				setTimeout( () => {
					setupMetaFieldListeners();
				}, 200 ); // Increased delay to ensure DOM is ready.
			}
		}

		// Event handler registration to generate the excerpt.
		$( document ).on(
			'click',
			'#classifai-excerpt-generation__excerpt-generate-btn',
			generateExcerpt
		);
	}
} )( jQuery );
