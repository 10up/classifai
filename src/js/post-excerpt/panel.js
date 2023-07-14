/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, ExternalLink, TextareaControl } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';
import { compose } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { handleClick } from '../helpers';

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
	const { select } = wp.data;
	const postId = select( 'core/editor' ).getCurrentPostId();
	const buttonText =
		'' === excerpt
			? __( 'Generate excerpt', 'classifai' )
			: __( 'Re-generate excerpt', 'classifai' );
	const isPublishPanelOpen =
		select( 'core/edit-post' ).isPublishSidebarOpened();

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
				data-id={ postId }
				onClick={ ( e ) =>
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/openai/generate-excerpt/',
						callback: onUpdateExcerpt,
						buttonText,
					} )
				}
			>
				{ buttonText }
			</Button>
			<span
				className="spinner"
				style={ { display: 'none', float: 'none' } }
			></span>
			<span
				className="error"
				style={ {
					display: 'none',
					color: '#bc0b0b',
					paddingTop: '5px',
				} }
			></span>
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
