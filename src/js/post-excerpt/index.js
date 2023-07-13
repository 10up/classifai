/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCommand } from '@wordpress/commands';
import { dispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { PostExcerptCheck } from '@wordpress/editor';
import { edit } from '@wordpress/icons';
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
	useCommand( {
		name: 'classifai/generate-excerpt',
		label: __( 'Generate excerpt', 'classifai' ),
		icon: edit,
		callback: ( { close } ) => {
			dispatch( 'core/edit-post' )
				.toggleEditorPanelOpened( 'post-excerpt' )
				.then( () => {
					const button = document.querySelector(
						'.editor-post-excerpt button'
					);

					close();

					if ( button ) {
						button.scrollIntoView( {
							block: 'center',
						} );
						button.click();
					}
				} );
		},
	} );

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
