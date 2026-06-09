/**
 * WordPress dependencies
 */
import { __experimentalPluginPostExcerpt as PluginPostExcerpt } from '@wordpress/edit-post'; // eslint-disable-line @wordpress/no-unsafe-wp-apis
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import ExcerptGeneration from './components/ExcerptGeneration';
import PostExcerptForm from './components/PostExcerptForm';
import MaybeExcerptPrePublishPanel from './components/MaybeExcerptPrePublishPanel';

/**
 * Plugin component that adds a generate button to the excerpt panel.
 */
const ExcerptGenerationPlugin = () => {
	// __experimentalPluginPostExcerpt from @wordpress/edit-post is a function
	// that returns the component (or null in site editor)
	const PluginExcerptComponent = PluginPostExcerpt();

	// If we're in the site editor, the function returns null
	if ( ! PluginExcerptComponent ) {
		return null;
	}

	return (
		<>
			<PluginExcerptComponent className="classifai-excerpt-generation">
				<ExcerptGeneration />
			</PluginExcerptComponent>
			<MaybeExcerptPrePublishPanel>
				<PostExcerptForm />
			</MaybeExcerptPrePublishPanel>
		</>
	);
};

registerPlugin( 'classifai-excerpt-generation', {
	render: ExcerptGenerationPlugin,
} );
