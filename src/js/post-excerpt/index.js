/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { PostExcerptCheck } from '@wordpress/editor';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import PostExcerptForm from './panel';
import MaybeExcerptPanel from './maybe-excerpt-panel';

// Remove core Post Excerpt panel.
( () => {
	dispatch( 'core/edit-post' ).removeEditorPanel( 'post-excerpt' );
} )();

// Add our own custom Post Excerpt panel.
const PostExcerpt = () => {
	return (
		<PostExcerptCheck>
			<PluginDocumentSettingPanel title={ __( 'Excerpt' ) }>
				<PostExcerptForm />
			</PluginDocumentSettingPanel>
			<MaybeExcerptPanel>
				<PostExcerptForm />
			</MaybeExcerptPanel>
		</PostExcerptCheck>
	);
};
registerPlugin( 'post-excerpt', { render: PostExcerpt } );
