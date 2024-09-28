/**
 * External dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import {
	Icon,
	SlotFillProvider,
	Slot,
	Fill,
	Button,
	Popover,
} from '@wordpress/components';
import { useEffect, useState, useRef } from '@wordpress/element';
import { PluginArea, registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

const ClassifAIPluginArea = () => {
	return (
		<PluginDocumentSettingPanel
			title={ __( 'ClassifAI', 'classifai' ) }
			icon={
				<Icon
					className="components-panel__icon"
					icon={ icon }
					size={ 24 }
				/>
			}
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

function ClassifaiButtonPlugin() {
	const [ isReady, setIsReady ] = useState( false );
	const [ isPopoverOpen, setIsPopoverOpen ] = useState( false );
	const parentEl = useRef( null );
	const buttonContainer = useRef( null );


	useEffect( () => {
		if ( buttonContainer.current ) {
			return;
		}

		window._wpLoadBlockEditor.then( async () => {
			if ( document.getElementById( 'classifai-toolbar-button' ) ) {
				return null;
			}

			await new Promise( ( resolve ) => {
				const intervalId = setInterval( () => {
					parentEl.current = document.querySelector('.editor-header__settings');
	
					if ( parentEl.current && buttonContainer.current ) {
						parentEl.current.insertBefore(
							buttonContainer.current,
							parentEl.current.firstChild
						);
					}

					clearInterval( intervalId );
					resolve();
				}, 100 );
			} );
		} );

		setIsReady( true );
	} );

	if ( ! isReady ) {
		return null;
	}

	const popoverStyle = {
		minWidth: '280px',
		padding: '1rem',
	}

	return (
		<div ref={ buttonContainer } id="classifai-toolbar-button">
			<Button
				icon={
					<Icon
						className="components-panel__icon"
						icon={ icon }
					/>
				}
				onClick={ () => setIsPopoverOpen( ! isPopoverOpen ) }
			/>
			{
				isPopoverOpen && (
					<Popover
						anchor={ buttonContainer.current }
						position="bottom"
						onFocusOutside={ () => setIsPopoverOpen( ! isPopoverOpen ) }
					>
						<div style={ popoverStyle }>
							<Slot name="classifai-editor-setting-panel" />
						</div>
					</Popover>
				)
			}
		</div>
	);
}

registerPlugin( 'classifai-plugin-area-v2', {
	render: ClassifaiButtonPlugin,
} );