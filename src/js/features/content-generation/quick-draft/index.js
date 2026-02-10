/**
 * Quick Draft Content Generation Integration.
 */

/**
 * External dependencies.
 */
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import './index.scss';

/**
 * Initialize Quick Draft content generation.
 */
domReady( () => {
	const titleField = document.getElementById( 'title' );
	const contentField = document.getElementById( 'content' );
	const quickPressForm = document.getElementById( 'quick-press' );

	// Required elements not found, abort initialization.
	if ( ! titleField || ! contentField || ! quickPressForm ) {
		return;
	}

	/**
	 * Add the generate button to the save button.
	 *
	 * @return {boolean} True if the button was added, false otherwise.
	 */
	function addGenerateButtonWhenReady() {
		const saveButton = document.getElementById( 'save-post' );
		if ( ! saveButton ) {
			return false;
		}

		const generateButton = document.createElement( 'input' );
		generateButton.type = 'button';
		generateButton.id = 'classifai-generate-content';
		generateButton.className = 'button';
		generateButton.value = __( 'Create Draft from Prompt', 'classifai' );

		saveButton.parentNode.insertBefore(
			generateButton,
			saveButton.nextSibling
		);
		return true;
	}

	const maxAttempts = 20;
	const retryDelayMs = 100;
	let attempts = 0;

	/**
	 * Try to add the generate button to the save button.
	 */
	function tryAddGenerateButton() {
		if ( addGenerateButtonWhenReady() ) {
			return;
		}

		attempts += 1;
		if ( attempts < maxAttempts ) {
			setTimeout( tryAddGenerateButton, retryDelayMs );
		}
	}

	tryAddGenerateButton();

	// Use event delegation to handle dynamically added button.
	document.addEventListener( 'click', function ( e ) {
		if ( e.target && e.target.id === 'classifai-generate-content' ) {
			e.preventDefault();
			handleGenerateContent();
		}
	} );

	/**
	 * Remove any existing notices.
	 */
	function removeExistingNotices() {
		const notices =
			quickPressForm.parentElement.querySelectorAll( '.notice' );
		if ( notices.length > 0 ) {
			notices.forEach( function ( notice ) {
				notice.remove();
			} );
		}
	}

	/**
	 * Show a temporary error message to the user.
	 *
	 * @param {string} message The error message to display.
	 */
	function showErrorMessage( message ) {
		removeExistingNotices();

		const errorNotice = document.createElement( 'div' );
		errorNotice.className = 'notice notice-error is-dismissible';
		errorNotice.innerHTML = `
			<p>${ message }</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">
					${ __( 'Dismiss this notice.', 'classifai' ) }
				</span>
			</button>
		`;

		quickPressForm.parentNode.insertBefore( errorNotice, quickPressForm );

		const dismissButton = errorNotice.querySelector( '.notice-dismiss' );
		if ( dismissButton ) {
			dismissButton.addEventListener( 'click', function () {
				errorNotice.remove();
			} );
		}
	}

	/**
	 * Handle the content generation.
	 */
	function handleGenerateContent() {
		removeExistingNotices();

		const content = contentField.value.trim();

		// Validate that content is provided.
		if ( ! content ) {
			showErrorMessage(
				__(
					'Please enter some content to generate a draft from.',
					'classifai'
				)
			);
			contentField.focus();
			return;
		}

		const title = titleField.value.trim();
		const generateButton = document.getElementById(
			'classifai-generate-content'
		);

		// Show loading state.
		generateButton.disabled = true;
		generateButton.value = window.classifaiQuickDraft.generating;

		// Make API request.
		apiFetch( {
			path: 'classifai/v1/quick-draft-generate',
			method: 'POST',
			data: {
				title,
				content,
			},
		} )
			.then( function ( response ) {
				if ( response.success ) {
					// Clear the form.
					titleField.value = '';
					contentField.value = '';

					// Show success message.
					showSuccessMessage( response );
				} else {
					throw new Error(
						response.message || window.classifaiQuickDraft.error
					);
				}
			} )
			.catch( function ( error ) {
				showErrorMessage(
					error.message || window.classifaiQuickDraft.error
				);
			} )
			.finally( function () {
				// Reset button state.
				generateButton.disabled = false;
				generateButton.value = window.classifaiQuickDraft.createContent;
			} );
	}

	/**
	 * Show success message to user.
	 *
	 * @param {Object} response API response.
	 */
	function showSuccessMessage( response ) {
		removeExistingNotices();

		const message = document.createElement( 'div' );
		message.className = 'notice notice-success is-dismissible';
		message.innerHTML = `
			<p>
				${ __( 'Draft created successfully!', 'classifai' ) }
				<a href="${ response.edit_url }" target="_blank" rel="noopener noreferrer">
					${ __( 'Edit draft', 'classifai' ) }
				</a>
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">
					${ __( 'Dismiss this notice.', 'classifai' ) }
				</span>
			</button>
		`;

		// Insert before the form.
		quickPressForm.parentNode.insertBefore( message, quickPressForm );

		// Add dismiss functionality.
		const dismissButton = message.querySelector( '.notice-dismiss' );
		if ( dismissButton ) {
			dismissButton.addEventListener( 'click', function () {
				message.remove();
			} );
		}
	}
} );
