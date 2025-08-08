/**
 * External Dependencies.
 */
import { __ } from '@wordpress/i18n';
import { Button, ExternalLink, TextareaControl } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { useState } from '@wordpress/element';
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

	const postId = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostId()
	);
	const isPublishPanelOpen = useSelect( ( select ) =>
		select( 'core/edit-post' ).isPublishSidebarOpened()
	);

	// Get target field value on component mount.
	useEffect( () => {
		if ( postId ) {
			apiFetch( {
				path: `/classifai/v1/get-target-field-value/${ postId }`,
			} ).then( ( result ) => {
				setTargetFieldValue( result.value || '' );
			} ).catch( () => {
				// If endpoint doesn't exist, fall back to excerpt.
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
			},
			( err ) => {
				setError( err?.message );
				setIsLoading( false );
			}
		);
	};

	return (
		<div className="editor-post-excerpt">
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
			{ targetFieldValue && targetFieldValue !== excerpt && (
				<div style={ { marginTop: '1rem', padding: '10px', backgroundColor: '#f0f0f0', borderRadius: '4px' } }>
					<strong>{ __( 'Target field value:', 'classifai' ) }</strong>
					<p style={ { margin: '5px 0 0 0' } }>{ targetFieldValue }</p>
				</div>
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
