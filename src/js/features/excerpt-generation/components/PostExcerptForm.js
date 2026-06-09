/**
 * External Dependencies.
 */
import { __ } from '@wordpress/i18n';
import { TextareaControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal Dependencies.
 */
import ExcerptGeneration from './ExcerptGeneration';

/**
 * PostExcerptForm component.
 *
 * Note that this was originally copied from Gutenberg,
 * see https://github.com/WordPress/gutenberg/blob/4b4c4befb34d815634b85cbee23cad169ab0e073/packages/editor/src/components/post-excerpt/index.js. We've changed our approach since then.
 *
 */
export default function PostExcerptForm() {
	const { excerpt, isPublishPanelOpen } = useSelect( ( select ) => {
		return {
			excerpt: select( editorStore ).getEditedPostAttribute( 'excerpt' ),
			isPublishPanelOpen: select( editorStore ).isPublishSidebarOpened(),
		};
	}, [] );
	const { editPost } = useDispatch( editorStore );

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
				onChange={ ( value ) => editPost( { excerpt: value } ) }
				value={ excerpt }
			/>
			<ExcerptGeneration />
		</div>
	);
}
