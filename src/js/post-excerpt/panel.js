/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ExternalLink, TextareaControl } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

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

	const { select } = wp.data;
	const postId = select( 'core/editor' ).getCurrentPostId();
	const postContent =
		select( 'core/editor' ).getEditedPostAttribute( 'content' );
	const postTitle = select( 'core/editor' ).getEditedPostAttribute( 'title' );
	const buttonText =
		'' === excerpt
			? __( 'Generate excerpt', 'classifai' )
			: __( 'Re-generate excerpt', 'classifai' );
	const isPublishPanelOpen =
		select( 'core/edit-post' ).isPublishSidebarOpened();

	const buttonClick = async ( path ) => {
		setIsLoading( true );
		apiFetch( {
			path,
			method: 'POST',
			data: { id: postId, content: postContent, title: postTitle },
		} ).then(
			( res ) => {
				onUpdateExcerpt( res );
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
				onClick={ () =>
					buttonClick( '/classifai/v1/openai/generate-excerpt/' )
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
