/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { Button, ExternalLink, TextareaControl } = wp.components;
const { withSelect, withDispatch } = wp.data;
const { compose } = wp.compose;

/**
 * Internal dependencies
 */
import { handleClick } from '../helpers';

function PostExcerpt( { excerpt, onUpdateExcerpt } ) {
	const { select } = wp.data;
	const postId = select( 'core/editor' ).getCurrentPostId();
	const buttonText = '' === excerpt ? __( 'Generate excerpt', 'classifai' ) : __( 'Re-generate excerpt', 'classifai' );
	const isPublishPanelOpen = select( 'core/edit-post' ).isPublishSidebarOpened();

	return (
		<div className="editor-post-excerpt">
			<TextareaControl
				__nextHasNoMarginBottom
				label={ ! isPublishPanelOpen ? __( 'Write an excerpt (optional)' ) : null }
				className="editor-post-excerpt__textarea"
				onChange={ ( value ) => onUpdateExcerpt( value ) }
				value={ excerpt }
			/>
			{ ! isPublishPanelOpen &&
				<ExternalLink
					href={ __(
						'https://wordpress.org/support/article/settings-sidebar/#excerpt'
					) }
				>
					{ __( 'Learn more about manual excerpts' ) }
				</ExternalLink>
			}
			<Button
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
