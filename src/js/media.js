/* global ClassifAI */

import { handleClick } from './helpers';

( function( $ ) {
	const { __ } = wp.i18n;

	/**
	 * Handle click events for Image Processing buttons added to media modal.
	 */
	const handleButtonsClick = () => {
		const altTagsButton = document.getElementById(
			'classifai-rescan-alt-tags'
		);
		const imageTagsButton = document.getElementById(
			'classifai-rescan-image-tags'
		);
		const ocrScanButton = document.getElementById( 'classifai-rescan-ocr' );
		const smartCropButton = document.getElementById(
			'classifai-rescan-smart-crop'
		);
		const readButton = document.getElementById( 'classifai-rescan-pdf' );

		if ( altTagsButton ) {
			altTagsButton.addEventListener( 'click', ( e ) =>
				handleClick( {
					button: e.target,
					endpoint: '/classifai/v1/alt-tags/',
					callback: ( resp ) => {
						const { enabledAltTextFields } = classifaiMediaVars;

						if ( resp ) {
							if ( enabledAltTextFields.includes( 'alt' ) ) {
								const textField =
									document.getElementById(
										'attachment-details-two-column-alt-text'
									) ??
									document.getElementById(
										'attachment-details-alt-text'
									);

								if ( textField ) {
									textField.value = resp;
								}
							}

							if ( enabledAltTextFields.includes( 'caption' ) ) {
								const textField =
									document.getElementById(
										'attachment-details-two-column-caption'
									) ??
									document.getElementById(
										'attachment-details-caption'
									);

								if ( textField ) {
									textField.value = resp;
								}
							}

							if (
								enabledAltTextFields.includes( 'description' )
							) {
								const textField =
									document.getElementById(
										'attachment-details-two-column-description'
									) ??
									document.getElementById(
										'attachment-details-description'
									);

								if ( textField ) {
									textField.value = resp;
								}
							}
						}
					},
				} )
			);
		}

		if ( imageTagsButton ) {
			imageTagsButton.addEventListener( 'click', ( e ) =>
				handleClick( {
					button: e.target,
					endpoint: '/classifai/v1/image-tags/',
				} )
			);
		}

		if ( ocrScanButton ) {
			ocrScanButton.addEventListener( 'click', ( e ) =>
				handleClick( {
					button: e.target,
					endpoint: '/classifai/v1/ocr/',
					callback: ( resp ) => {
						if ( resp ) {
							const textField =
								document.getElementById(
									'attachment-details-two-column-description'
								) ??
								document.getElementById(
									'attachment-details-description'
								);
							if ( textField ) {
								textField.value = resp;
							}
						}
					},
				} )
			);
		}

		if ( smartCropButton ) {
			smartCropButton.addEventListener( 'click', ( e ) =>
				handleClick( {
					button: e.target,
					endpoint: '/classifai/v1/smart-crop/',
				} )
			);
		}

		if ( readButton ) {
			readButton.addEventListener( 'click', ( e ) => {
				const postID = e.target.getAttribute( 'data-id' );
				wp.apiRequest( { path: `/classifai/v1/read-pdf/${ postID }` } );
				e.target.setAttribute( 'disabled', 'disabled' );
				e.target.textContent = __( 'Read API requested!', 'classifai' );
			} );
		}
	};

	/**
	 * Check the PDF Scanner status and disable button if in progress.
	 */
	const checkPdfReadStatus = () => {
		const readButton = document.getElementById( 'classifai-rescan-pdf' );

		if ( ! readButton ) {
			return;
		}

		const postId = readButton.getAttribute( 'data-id' );

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'classifai_get_read_status',
				attachment_id: postId,
				nonce: ClassifAI.ajax_nonce,
			},
			success: ( resp ) => {
				if ( resp?.success ) {
					if ( resp?.data?.running ) {
						readButton.setAttribute( 'disabled', 'disabled' );
						readButton.textContent = __(
							'In progress!',
							'classifai'
						);
					} else if ( resp?.data?.read ) {
						readButton.textContent = __( 'Rescan', 'classifai' );
					}
				}
			},
		} );
	};

	$( document ).ready( function () {
		if ( wp.media ) {
			wp.media.view.Modal.prototype.on( 'open', function () {
				wp.media.frame.on( 'selection:toggle', handleButtonsClick );
				wp.media.frame.on( 'selection:toggle', checkPdfReadStatus );
			} );
		}

		if ( wp.media.frame ) {
			wp.media.frame.on( 'edit:attachment', handleButtonsClick );
			wp.media.frame.on( 'edit:attachment', checkPdfReadStatus );
		}

		// For new uploaded media.
		if ( wp.Uploader && wp.Uploader.queue ) {
			wp.Uploader.queue.on( 'reset', handleButtonsClick );
		}
	} );
} )( jQuery );
