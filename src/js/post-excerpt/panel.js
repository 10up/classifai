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
	const buttonText = __( 'Generate excerpt', 'classifai' );

	return (
		<div className="editor-post-excerpt">
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Write an excerpt (optional)' ) }
				className="editor-post-excerpt__textarea"
				onChange={ ( value ) => onUpdateExcerpt( value ) }
				value={ excerpt }
			/>
			<ExternalLink
				href={ __(
					'https://wordpress.org/support/article/settings-sidebar/#excerpt'
				) }
			>
				{ __( 'Learn more about manual excerpts' ) }
			</ExternalLink>
			<Button
				variant={ 'secondary' }
				data-id={ postId }
				onClick={ ( e ) =>
					handleClick( {
						button: e.target,
						endpoint: '/classifai/v1/generate-excerpt/',
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
