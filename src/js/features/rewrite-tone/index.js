import { registerPlugin } from '@wordpress/plugins';
import { useSelectedBlocks } from '../../hooks';

const { ClassifaiEditorSettingPanel } = window;

const RewriteTonePlugin = () => {
	const allSelectedBlocks = useSelectedBlocks();

	return (
		<ClassifaiEditorSettingPanel>
		</ClassifaiEditorSettingPanel>
	);
};

registerPlugin( 'classifai-rewrite-tone-plugin', {
	render: RewriteTonePlugin,
} );
