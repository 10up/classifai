/**
 * Quick Draft Content Generation Integration.
 */

import './index.scss';
import apiFetch from '@wordpress/api-fetch';

/**
 * Initialize Quick Draft content generation.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	const titleField = document.getElementById( 'title' );
	const contentField = document.getElementById( 'content' );
	const quickPressForm = document.getElementById( 'quick-press' );

	if ( ! titleField || ! contentField || ! quickPressForm ) {
		// Required elements not found, abort initialization.
		return;
	}

	// Use event delegation to handle dynamically added button.
	document.addEventListener( 'click', function ( e ) {
		if ( e.target && e.target.id === 'classifai-generate-content' ) {
			e.preventDefault();
			handleGenerateContent();
		}
	} );

	/**
	 * Show a temporary error message to the user.
	 *
	 * @param {string} message The error message to display.
	 */
	function showErrorMessage( message ) {
		const errorNotice = document.createElement( 'div' );
		errorNotice.className = 'notice notice-error is-dismissible';
		errorNotice.innerHTML = `
			<p>${ message }</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
			</button>
		`;

		quickPressForm.parentNode.insertBefore( errorNotice, quickPressForm );

		const dismissButton = errorNotice.querySelector( '.notice-dismiss' );
		if ( dismissButton ) {
			dismissButton.addEventListener( 'click', function () {
				errorNotice.remove();
			} );
		}

		setTimeout( function () {
			if ( errorNotice.parentNode ) {
				errorNotice.remove();
			}
		}, 5000 );
	}

	/**
	 * Handle the content generation.
	 */
	function handleGenerateContent() {
		// Only assign variables after validation.
		const content = contentField.value.trim();

		// Validate that content is provided.
		if ( ! content ) {
			showErrorMessage(
				'Please enter some content to generate a draft from.'
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

					// Reload the page to show the new draft.
					// This is more reliable than trying to refresh just the widget.
					setTimeout( function () {
						window.location.reload();
					}, 1000 );
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
		const message = document.createElement( 'div' );
		message.className = 'notice notice-success is-dismissible';
		message.innerHTML = `
			<p>
				Draft created successfully! 
				<a href="${ response.edit_url }" target="_blank">Edit draft</a>
			</p>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text">Dismiss this notice.</span>
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

		// Auto-dismiss after 5 seconds.
		setTimeout( function () {
			if ( message.parentNode ) {
				message.remove();
			}
		}, 5000 );
	}
} );
