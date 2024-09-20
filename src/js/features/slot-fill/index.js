import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { Icon, SlotFillProvider, Slot, Fill } from '@wordpress/components';
import { PluginArea, registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

const ClassifAIIcon = ( { icon } ) => (
	<Icon className="components-panel__icon" icon={ icon } size={ 24 } />
);

const ClassifAIPluginArea = () => {
	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={ ClassifAIIcon }
			className="classifai-panel"
		>
			<SlotFillProvider>
				<Slot name="classifai-editor-setting-panel" />
				<PluginArea />
			</SlotFillProvider>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'classifai-plugin-area', {
	render: ClassifAIPluginArea,
} );

window.ClassifaiEditorSettingPanel = ( { children } ) => {
	return <Fill name="classifai-editor-setting-panel">{ children }</Fill>;
};
