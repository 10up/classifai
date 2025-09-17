/**
 * Quick Draft Content Generation Integration
 */

import './index.scss';
import apiFetch from '@wordpress/api-fetch';

/**
 * Initialize Quick Draft content generation.
 */
document.addEventListener( 'DOMContentLoaded', function() {
	const titleField = document.getElementById( 'title' );
	const contentField = document.getElementById( 'content' );
	const quickPressForm = document.getElementById( 'quick-press' );

	if ( ! titleField || ! contentField || ! quickPressForm ) {
		console.log( 'ClassifAI Quick Draft: Required elements not found' );
		return;
	}

	console.log( 'ClassifAI Quick Draft: Initializing event delegation' );

	// Use event delegation to handle dynamically added button
	document.addEventListener( 'click', function( e ) {
		if ( e.target && e.target.id === 'classifai-generate-content' ) {
			e.preventDefault();
			handleGenerateContent();
		}
	} );

	/**
	 * Handle the content generation.
	 */
	function handleGenerateContent() {
		const title = titleField.value.trim();
		const content = contentField.value.trim();

		// Validate that content is provided
		if ( ! content ) {
			alert( 'Please enter some content to generate a draft from.' );
			contentField.focus();
			return;
		}

		const generateButton = document.getElementById( 'classifai-generate-content' );
		
		// Show loading state
		generateButton.disabled = true;
		generateButton.value = classifaiQuickDraft.generating;

		console.log( 'ClassifAI Quick Draft: Making API request' );

		// Make API request
		apiFetch( {
			path: 'classifai/v1/quick-draft-generate',
			method: 'POST',
			data: {
				title: title,
				content: content,
			},
		} ).then( function( response ) {
			console.log( 'ClassifAI Quick Draft: API response received', response );
			
			if ( response.success ) {
				// Clear the form
				titleField.value = '';
				contentField.value = '';

				// Show success message
				showSuccessMessage( response );

				// Refresh the recent drafts section
				refreshRecentDrafts();
			} else {
				throw new Error( response.message || classifaiQuickDraft.error );
			}
		} ).catch( function( error ) {
			console.error( 'ClassifAI Quick Draft: API error', error );
			alert( error.message || classifaiQuickDraft.error );
		} ).finally( function() {
			// Reset button state
			generateButton.disabled = false;
			generateButton.value = classifaiQuickDraft.createContent;
		} );
	}

	/**
	 * Show success message to user.
	 *
	 * @param {Object} response API response
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

		// Insert before the form
		quickPressForm.parentNode.insertBefore( message, quickPressForm );

		// Add dismiss functionality
		const dismissButton = message.querySelector( '.notice-dismiss' );
		if ( dismissButton ) {
			dismissButton.addEventListener( 'click', function() {
				message.remove();
			} );
		}

		// Auto-dismiss after 5 seconds
		setTimeout( function() {
			if ( message.parentNode ) {
				message.remove();
			}
		}, 5000 );
	}

	/**
	 * Refresh the recent drafts section.
	 */
	function refreshRecentDrafts() {
		// Trigger the same refresh mechanism as the standard Save Draft
		if ( window.quickPressLoad ) {
			// Make a request to refresh the widget content
			apiFetch( {
				path: 'wp/v2/posts',
				method: 'GET',
				data: {
					status: 'draft',
					author: window.classifaiQuickDraft?.currentUserId || '',
					per_page: 4,
					orderby: 'modified',
					order: 'desc',
				},
			} ).then( function() {
				// Reload the quick press widget content
				const widgetContainer = document.querySelector( '#dashboard_quick_press .inside' );
				if ( widgetContainer ) {
					// This will reload the entire widget content including recent drafts
					location.reload();
				}
			} ).catch( function() {
				// Silently fail - not critical
			} );
		}
	}
} );
