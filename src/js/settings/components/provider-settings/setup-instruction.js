/**
 * WordPress dependencies
 */
import { Panel, PanelBody } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { useFeatureContext } from '../feature-settings/context';

/**
 * External Dependencies
 */
import { marked } from 'marked';

export const SetupInstruction = ( { provider } ) => {
	const { featureName } = useFeatureContext();
	const [ readmeContent ] = useState(
		ClassifAI.instruction[ featureName ][ provider ] || ''
	);
	const instruction = marked.parse( readmeContent || '' );

	return (
		<SettingsRow>
			<Panel>
				<PanelBody
					title={ __( 'How to Configure?', 'classifai' ) }
					initialOpen={ false }
				>
					{ instruction ? (
						<div
							dangerouslySetInnerHTML={ { __html: instruction } }
						/>
					) : (
						<p>
							{ __( 'No instructions available.', 'classifai' ) }
						</p>
					) }
				</PanelBody>
			</Panel>
		</SettingsRow>
	);
};
