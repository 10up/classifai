/**
 * External dependencies
 */
import {
	SlotFillProvider,
	Slot,
	Fill,
	Button,
	Popover,

} from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';
import { createRoot, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

window._wpLoadBlockEditor.then( async () => {
	let headerSettingsPanel = null;

	await new Promise( ( resolve ) => {
		const intervalId = setInterval( () => {
			headerSettingsPanel = document.querySelector( '.editor-header__settings' );

			if ( headerSettingsPanel ) {
				clearInterval( intervalId );
				resolve();
			}
		}, 500 );
	} );

	if ( ! headerSettingsPanel ) {
		return;
	}

	const classifaiHeaderSettingButton = document.createElement( 'div' );
	classifaiHeaderSettingButton.classList.add( 'classifai-editor-header-setting-wrapper' );
	headerSettingsPanel.insertBefore( classifaiHeaderSettingButton, headerSettingsPanel.firstChild );

	const wrapperRoot = createRoot( classifaiHeaderSettingButton );
	wrapperRoot.render( <RenderClassifAIEditorHeaderPluginArea /> );

} );

function RenderClassifAIEditorHeaderPluginArea() {
	const [ isOpen, setIsOpen ] = useState( false );

	return (
		<SlotFillProvider>
			{
				isOpen && (
					<Popover
						onClose={ () => setIsOpen( false ) }
						onFocusOutside={ () => setIsOpen( false ) }
					>
						<div style={ { width: '200px' } }>
							<Slot name="classifai-editor-header-setting-panel" />
							<PluginArea />
						</div>
					</Popover>
				)
			}
			<Button
				icon={ icon }
				onClick={ () => setIsOpen( ! isOpen ) }
			/>
		</SlotFillProvider>
	);
}

window.ClassifaiEditorHeaderSettingPanel = ( { children } ) => {
	return <Fill name="classifai-editor-header-setting-panel">{ children }</Fill>;
};
