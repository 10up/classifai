/**
 * External dependencies
 */
import { SlotFillProvider, Slot, Fill, Button } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';
import { createRoot, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

window._wpLoadBlockEditor.then( async () => {
	let headerSettingsPanel = null;

	await new Promise( ( resolve ) => {
		const intervalId = setInterval( () => {
			headerSettingsPanel = document.querySelector(
				'.editor-header__settings'
			);

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
	classifaiHeaderSettingButton.classList.add(
		'classifai-editor-header-setting-wrapper'
	);
	classifaiHeaderSettingButton.style.position = 'relative';
	headerSettingsPanel.insertBefore(
		classifaiHeaderSettingButton,
		headerSettingsPanel.firstChild
	);

	const wrapperRoot = createRoot( classifaiHeaderSettingButton );
	wrapperRoot.render( <RenderClassifAIEditorHeaderPluginArea /> );
} );

function RenderClassifAIEditorHeaderPluginArea() {
	const [ isOpen, setIsOpen ] = useState( false );

	const popoverStyles = {
		position: 'absolute',
		top: 'calc(100% + 4px)',
		right: 'calc(100% - 20px)',
		boxShadow:
			'0 4px 5px #0000000a,0 12px 12px #00000008,0 16px 16px #00000005',
		borderRadius: '4px',
		transform: 'scale(1)',
		transition: 'transform 0.2s ease',
		'transform-origin': 'top right',
	};

	if ( ! isOpen ) {
		popoverStyles.opacity = 0;
		popoverStyles.pointerEvents = 'none';
		popoverStyles.transform = 'scale(0)';
		popoverStyles.transition = 'transform 0.2s ease';
		popoverStyles.zIndex = -10;
	}

	return (
		<SlotFillProvider>
			{ /* ✋🏻🛑⛔️ Don't use the Gutenberg's Popover component here. When clicked outside, it unmounts and breaks any ongoing
			ClassifAI related processes, since we're rendering inside the Popover here.
			This is why we implemented a custom popover which hides in plan sight, instead of unmounting. */ }
			<div
				className="classifai-editor-header-setting-custom-popover"
				style={ popoverStyles }
			>
				<Slot name="classifai-editor-header-setting-panel" />
				<PluginArea />
			</div>
			<Button icon={ icon } onClick={ () => setIsOpen( ! isOpen ) } />
		</SlotFillProvider>
	);
}

window.ClassifaiEditorHeaderSettingPanel = ( { children } ) => {
	return (
		<Fill name="classifai-editor-header-setting-panel">{ children }</Fill>
	);
};
