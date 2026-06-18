/* global ClassifAI */

/**
 * Internal dependencies
 */
import { handleClick } from '../../helpers';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

( function( $ ) { // eslint-disable-line

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
		const transcribeButton = document.getElementById(
			'classifai-retranscribe'
		);

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

		if ( transcribeButton ) {
			transcribeButton.addEventListener( 'click', ( e ) =>
				handleClick( {
					button: e.target,
					endpoint: '/classifai/v1/generate-transcript/',
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
					buttonText: __( 'Re-transcribe', 'classifai' ),
				} )
			);
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

	/**
	 * Map of async run type to its media-modal rescan button ID.
	 */
	const imageProcessButtons = {
		descriptive_text: 'classifai-rescan-alt-tags',
		tags: 'classifai-rescan-image-tags',
		ocr: 'classifai-rescan-ocr',
		crop: 'classifai-rescan-smart-crop',
	};

	let imageProcessInterval = null;
	const imageProcessing = new Set();

	/**
	 * Set the value of the first matching attachment-details field.
	 *
	 * @param {string[]} ids   Candidate element IDs (two-column and single-column).
	 * @param {string}   value Value to set.
	 */
	const setAttachmentField = ( ids, value ) => {
		if ( ! value ) {
			return;
		}

		const field = ids
			.map( ( id ) => document.getElementById( id ) )
			.find( Boolean );

		if ( field ) {
			field.value = value;
		}
	};

	/**
	 * Populate the media-modal fields once an async job completes.
	 *
	 * @param {string} type Run type that finished.
	 * @param {Object} info Status payload for that type.
	 */
	const populateImageFields = ( type, info ) => {
		if ( type === 'descriptive_text' && info.fields ) {
			const { enabledAltTextFields = [] } = classifaiMediaVars;

			if ( enabledAltTextFields.includes( 'alt' ) ) {
				setAttachmentField(
					[
						'attachment-details-two-column-alt-text',
						'attachment-details-alt-text',
					],
					info.fields.alt
				);
			}

			if ( enabledAltTextFields.includes( 'caption' ) ) {
				setAttachmentField(
					[
						'attachment-details-two-column-caption',
						'attachment-details-caption',
					],
					info.fields.caption
				);
			}

			if ( enabledAltTextFields.includes( 'description' ) ) {
				setAttachmentField(
					[
						'attachment-details-two-column-description',
						'attachment-details-description',
					],
					info.fields.description
				);
			}
		} else if ( type === 'ocr' ) {
			setAttachmentField(
				[
					'attachment-details-two-column-description',
					'attachment-details-description',
				],
				info.description
			);
		}
	};

	/**
	 * Poll the async processing status and reflect it in the media modal,
	 * updating fields when a job finishes without requiring a page reload.
	 */
	const checkImageProcessStatus = () => {
		let postId = null;

		Object.values( imageProcessButtons ).some( ( id ) => {
			const button = document.getElementById( id );

			if ( button ) {
				postId = button.getAttribute( 'data-id' );
				return true;
			}

			return false;
		} );

		if ( ! postId ) {
			return;
		}

		const processingLabel = __( 'ClassifAI is processing…', 'classifai' );

		$.ajax( {
			url: classifaiMediaVars.ajaxUrl || ajaxurl,
			type: 'POST',
			data: {
				action: 'classifai_get_image_process_status',
				attachment_id: postId,
				nonce: classifaiMediaVars.nonce || ClassifAI.ajax_nonce,
			},
			success: ( resp ) => {
				if ( ! resp?.success || ! resp?.data ) {
					return;
				}

				let anyProcessing = false;

				Object.keys( imageProcessButtons ).forEach( ( type ) => {
					const button = document.getElementById(
						imageProcessButtons[ type ]
					);

					if ( ! button ) {
						return;
					}

					const info = resp.data[ type ] || {};
					const status = info.status;
					const [ spinner ] =
						button.parentNode.getElementsByClassName( 'spinner' );

					if ( status === 'scheduled' || status === 'running' ) {
						anyProcessing = true;
						imageProcessing.add( type );
						button.setAttribute( 'disabled', 'disabled' );
						button.textContent = processingLabel;

						if ( spinner ) {
							spinner.style.display = 'inline-block';
							spinner.classList.add( 'is-active' );
						}
					} else {
						// No longer processing: surface the result if tracked.
						if ( imageProcessing.has( type ) ) {
							imageProcessing.delete( type );

							if ( status === 'error' ) {
								const [ errorContainer ] =
									button.parentNode.getElementsByClassName(
										'error'
									);

								if ( errorContainer ) {
									errorContainer.style.display =
										'inline-block';
									errorContainer.textContent =
										info.message ||
										__( 'Processing failed.', 'classifai' );
								}
							} else {
								populateImageFields( type, info );
							}
						}

						button.removeAttribute( 'disabled' );

						if ( spinner ) {
							spinner.style.display = 'none';
							spinner.classList.remove( 'is-active' );
						}

						if ( button.textContent === processingLabel ) {
							button.textContent = __( 'Rescan', 'classifai' );
						}
					}
				} );

				if ( anyProcessing && ! imageProcessInterval ) {
					imageProcessInterval = setInterval(
						checkImageProcessStatus,
						5000
					);
				} else if ( ! anyProcessing && imageProcessInterval ) {
					clearInterval( imageProcessInterval );
					imageProcessInterval = null;
				}
			},
		} );
	};

	$( document ).ready( function () {
		if ( wp.media ) {
			wp.media.view.Modal.prototype.on( 'open', function () {
				wp.media.frame.on( 'selection:toggle', handleButtonsClick );
				wp.media.frame.on( 'selection:toggle', checkPdfReadStatus );
				wp.media.frame.on(
					'selection:toggle',
					checkImageProcessStatus
				);
			} );
		}

		if ( wp.media.frame ) {
			wp.media.frame.on( 'edit:attachment', handleButtonsClick );
			wp.media.frame.on( 'edit:attachment', checkPdfReadStatus );
			wp.media.frame.on( 'edit:attachment', checkImageProcessStatus );
		}

		// For new uploaded media.
		if ( wp.Uploader && wp.Uploader.queue ) {
			wp.Uploader.queue.on( 'reset', handleButtonsClick );
			wp.Uploader.queue.on( 'reset', checkImageProcessStatus );
		}
	} );
} )( jQuery );
