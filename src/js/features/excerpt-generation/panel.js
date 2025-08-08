/**
 * External Dependencies.
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	ExternalLink,
	TextareaControl,
	Notice,
} from '@wordpress/components';
import { withSelect, withDispatch, useSelect, select } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal Dependencies.
 */
import { DisableFeatureButton } from '../../components';
import { browserAITextGeneration } from '../../helpers';

/**
 * PostExcerpt component.
 *
 * Note that the majority of the code here is copied from Gutenberg,
 * see https://github.com/WordPress/gutenberg/blob/4b4c4befb34d815634b85cbee23cad169ab0e073/packages/editor/src/components/post-excerpt/index.js. We do this so we can add our
 * custom button but keep the rest of the functionality the same.
 *
 * @param {Object}   props                 Component props.
 * @param {string}   props.excerpt         The post excerpt.
 * @param {Function} props.onUpdateExcerpt Callback to update the post excerpt.
 */
function PostExcerpt( { excerpt, onUpdateExcerpt } ) {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( false );
	const [ targetFieldValue, setTargetFieldValue ] = useState( '' );
	const [ targetFieldSettings, setTargetFieldSettings ] = useState( null );

	const postId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);
	const isPublishPanelOpen = useSelect( ( select ) =>
		select( 'core/edit-post' ).isPublishSidebarOpened()
	);

	// Get target field settings and value on component mount.
	useEffect( () => {
		if ( postId ) {
			// Get target field settings
			apiFetch( {
				path: '/classifai/v1/get-target-field-settings/',
			} )
				.then( ( result ) => {
					setTargetFieldSettings( result );
				} )
				.catch( () => {
					// Fall back to default settings.
					setTargetFieldSettings( {
						field_type: 'post_excerpt',
						field_name: __( 'Default excerpt field', 'classifai' ),
					} );
				} );

			// Get target field value
			apiFetch( {
				path: `/classifai/v1/get-target-field-value/${ postId }`,
			} )
				.then( ( result ) => {
					setTargetFieldValue( result.value || '' );
				} )
				.catch( () => {
					// Fall back to default excerpt.
					setTargetFieldValue( excerpt || '' );
				} );
		}
	}, [ postId, excerpt ] );

	const buttonText = excerpt
		? __( 'Re-generate excerpt', 'classifai' )
		: __( 'Generate excerpt', 'classifai' );

	const buttonClick = async ( path ) => {
		const postContent =
			select( 'core/editor' ).getEditedPostAttribute( 'content' );
		const postTitle =
			select( 'core/editor' ).getEditedPostAttribute( 'title' );
		const authorId =
			select( 'core/editor' ).getEditedPostAttribute( 'author' );

		// Get author display name.
		let authorName = '';
		if ( authorId ) {
			const author = select( 'core' ).getUser( authorId );
			if ( author && author.name ) {
				authorName = author.name;
			}
		}

		setIsLoading( true );

		// Prepare the payload.
		const payload = {
			id: postId,
			content: postContent,
			title: postTitle,
		};

		// Only include author in payload if we have it, otherwise let server fetch it.
		if ( authorName ) {
			payload.author = authorName;
		}

		apiFetch( {
			path,
			method: 'POST',
			data: payload,
		} ).then(
			async ( res ) => {
				// Support calling a function from the response for browser AI.
				if ( typeof res === 'object' ) {
					if ( res.hasOwnProperty( 'func' ) ) {
						res = await browserAITextGeneration(
							res.func,
							res?.prompt,
							res?.content
						);
					} else {
						res = '';
					}
				}

				// Update both the excerpt field and target field value.
				onUpdateExcerpt( res.trim() );
				setTargetFieldValue( res.trim() );
				setError( false );
				setIsLoading( false );

				// Update visible meta field values on the page if they exist.
				updateVisibleMetaFields( res.trim() );
			},
			( err ) => {
				setError( err?.message );
				setIsLoading( false );
			}
		);
	};

	// Function to update visible meta field values on the page.
	const updateVisibleMetaFields = ( value ) => {
		if (
			! targetFieldSettings ||
			targetFieldSettings.field_type === 'post_excerpt'
		) {
			return;
		}

		const metaKey =
			targetFieldSettings.meta_key || targetFieldSettings.field_name;
		if ( ! metaKey ) {
			return;
		}

		// Update meta field input that is visible on the page.
		// Directly target the textarea by its name attribute using the metaKey.
		const metaInput = document.querySelector(
			`textarea[name="meta[${ metaKey }][value]"]`
		);
		if ( metaInput ) {
			metaInput.value = value;

			// Trigger input event to notify any listeners.
			const inputEvent = new Event( 'input', { bubbles: true } );
			metaInput.dispatchEvent( inputEvent );

			// Also trigger change event.
			const changeEvent = new Event( 'change', { bubbles: true } );
			metaInput.dispatchEvent( changeEvent );
		}

		// Also check for ACF fields if this is an ACF field
		if (
			targetFieldSettings.field_type === 'acf_field' &&
			targetFieldSettings.meta_key
		) {
			const acfInputs = document.querySelectorAll(
				`input[name*="${ targetFieldSettings.meta_key }"], textarea[name*="${ targetFieldSettings.meta_key }"]`
			);
			acfInputs.forEach( ( input ) => {
				input.value = value;

				// Trigger change events
				const inputEvent = new Event( 'input', { bubbles: true } );
				input.dispatchEvent( inputEvent );

				const changeEvent = new Event( 'change', { bubbles: true } );
				input.dispatchEvent( changeEvent );
			} );
		}
	};

	// Function to update the custom field display when meta field values change
	const updateCustomFieldDisplay = ( value ) => {
		if ( ! shouldShowCustomField ) {
			return;
		}

		// Update the target field value state
		setTargetFieldValue( value );
	};

	// Function to listen for changes in meta fields and update our custom field display
	const setupMetaFieldListeners = () => {
		if (
			! targetFieldSettings ||
			targetFieldSettings.field_type === 'post_excerpt'
		) {
			return;
		}

		const metaKey =
			targetFieldSettings.meta_key || targetFieldSettings.field_name;
		if ( ! metaKey ) {
			return;
		}

		// Listen for changes in meta field inputs (including both textarea and input for consistency).
		// Directly target the textarea by its name attribute using the metaKey.
		const metaInput = document.querySelector(
			`textarea[name="meta[${ metaKey }][value]"]`
		);

		if ( metaInput ) {
			metaInput.addEventListener( 'input', ( event ) => {
				updateCustomFieldDisplay( event.target.value );
			} );

			metaInput.addEventListener( 'change', ( event ) => {
				updateCustomFieldDisplay( event.target.value );
			} );
		}

		// Listen for ACF field changes
		if (
			targetFieldSettings.field_type === 'acf_field' &&
			targetFieldSettings.meta_key
		) {
			const acfInputs = document.querySelectorAll(
				`input[name*="${ targetFieldSettings.meta_key }"], textarea[name*="${ targetFieldSettings.meta_key }"]`
			);
			acfInputs.forEach( ( input ) => {
				input.addEventListener( 'input', ( event ) => {
					updateCustomFieldDisplay( event.target.value );
				} );

				input.addEventListener( 'change', ( event ) => {
					updateCustomFieldDisplay( event.target.value );
				} );
			} );
		}
	};

	// Set up listeners when component mounts or target field settings change
	useEffect( () => {
		if (
			targetFieldSettings &&
			targetFieldSettings.field_type !== 'post_excerpt'
		) {
			// Small delay to ensure DOM elements are available.
			const timer = setTimeout( () => {
				setupMetaFieldListeners();
			}, 100 );

			return () => {
				clearTimeout( timer );
			};
		}
	}, [ targetFieldSettings ] );

	// Check if we should show the custom field instead of the default excerpt field
	const shouldShowCustomField =
		targetFieldSettings &&
		targetFieldSettings.field_type !== 'post_excerpt';
	const fieldLabel = shouldShowCustomField
		? targetFieldSettings?.field_name || __( 'Custom excerpt', 'classifai' )
		: __( 'Write an excerpt (optional)', 'classifai' );

	return (
		<div className="editor-post-excerpt">
			{ ! shouldShowCustomField && (
				<>
					<TextareaControl
						__nextHasNoMarginBottom
						label={
							! isPublishPanelOpen
								? __( 'Write an excerpt (optional)' )
								: null
						}
						className="editor-post-excerpt__textarea"
						onChange={ ( value ) => onUpdateExcerpt( value ) }
						value={ excerpt }
					/>
					{ ! isPublishPanelOpen && (
						<ExternalLink
							href={ __(
								'https://wordpress.org/support/article/settings-sidebar/#excerpt'
							) }
						>
							{ __( 'Learn more about manual excerpts' ) }
						</ExternalLink>
					) }
				</>
			) }

			{ shouldShowCustomField && (
				<>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ ! isPublishPanelOpen ? fieldLabel : null }
						className="editor-post-excerpt__textarea"
						value={ targetFieldValue }
						readOnly
						help={ __(
							'This field is read-only. The value is stored in a custom field.',
							'classifai'
						) }
					/>
					<Notice
						status="info"
						isDismissible={ false }
						className="classifai-custom-field-notice"
					>
						{ targetFieldSettings?.field_type === 'acf_field'
							? __(
									'This excerpt is stored in an ACF field. To edit it, you can:',
									'classifai'
							  )
							: __(
									'This excerpt is stored in a custom field. To edit it, you can:',
									'classifai'
							  ) }
						<ul style={ { margin: '5px 0 0 20px' } }>
							<li>
								{ __(
									'Use the button below to regenerate it',
									'classifai'
								) }
							</li>
							<li>
								{ targetFieldSettings?.field_type ===
								'acf_field'
									? __(
											'Edit the ACF field directly in the post editor or ACF fields panel',
											'classifai'
									  )
									: __(
											'Edit the custom field directly in the post editor or custom fields panel',
											'classifai'
									  ) }
							</li>
							<li>
								{ __(
									'Change the target field in',
									'classifai'
								) }
								<a
									href={ `${ window.location.origin }/wp-admin/tools.php?page=classifai&tab=language_processing&feature=feature_excerpt_generation` }
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'ClassifAI Settings', 'classifai' ) }
								</a>
							</li>
						</ul>
					</Notice>
				</>
			) }

			<Button
				className="classifai-post-excerpt"
				variant={ 'secondary' }
				disabled={ isLoading }
				data-id={ postId }
				style={ { marginTop: '1rem' } }
				onClick={ () =>
					buttonClick( '/classifai/v1/generate-excerpt/' )
				}
			>
				{ buttonText }
			</Button>
			{ isLoading && (
				<span
					className="spinner is-active"
					style={ { float: 'none' } }
				></span>
			) }
			{ error && ! isLoading && (
				<span
					className="error"
					style={ {
						color: '#bc0b0b',
						display: 'inline-block',
						paddingTop: '5px',
					} }
				>
					{ error }
				</span>
			) }
			<DisableFeatureButton feature="feature_excerpt_generation" />
		</div>
	);
}

export default compose( [
	withSelect( ( select ) => {
		return {
			excerpt:
				select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ),
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		onUpdateExcerpt( excerpt ) {
			dispatch( 'core/editor' ).editPost( { excerpt } );
		},
	} ) ),
] )( PostExcerpt );
