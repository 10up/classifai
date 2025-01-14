import { registerPlugin } from '@wordpress/plugins';

import { useSelectedBlocks } from '../../hooks';
import { filterAndFlattenAllowedBlocks } from '../../utils';

const { ClassifaiEditorSettingPanel } = window;
const allowedTextBlocks = [
	'core/paragraph',
	'core/heading',
	'core/list',
	'core/list-item',
];

const RewriteTonePlugin = () => {
	const allSelectedBlocks = useSelectedBlocks();
	const filteredBlocks = filterAndFlattenAllowedBlocks( allSelectedBlocks, allowedTextBlocks );

	return (
		<ClassifaiEditorSettingPanel>
		</ClassifaiEditorSettingPanel>
	);
};

registerPlugin( 'classifai-rewrite-tone-plugin', {
	render: RewriteTonePlugin,
} );
