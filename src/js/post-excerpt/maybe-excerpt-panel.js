/**
 * WordPress dependencies
 */
const { __ } = wp.i18n;
const { PluginPrePublishPanel } = wp.editPost;
const { useSelect } = wp.data;

/**
 * Internal dependencies
 */
import ExcerptForm from './panel';

function MaybeExcerptPanel() {
	const excerpt = useSelect( select => select( 'core/editor' ).getEditedPostAttribute( 'excerpt' ) );

	const panelTitle = [
		__( 'Suggestion:', 'classifai' ),
		<span className="editor-post-publish-panel__link" key="label">
			{ __( 'Generate excerpt', 'classifai' ) }
		</span>,
	];

	if ( '' !== excerpt ) {
		return null;
	}

	return (
		<PluginPrePublishPanel initialOpen={ false } title={ panelTitle } icon='aside'>
			<ExcerptForm />
		</PluginPrePublishPanel>
	)
}

export default MaybeExcerptPanel;