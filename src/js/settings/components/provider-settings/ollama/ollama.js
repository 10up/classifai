/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { __experimentalInputControl as InputControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../data/store';
import { OllamaBaseSettings } from './base';
import { useFeatureContext } from '../../feature-settings/context';
import { SettingsRow } from '../../settings-row';
import { ModelsSelector } from './models';

/**
 * Component for Ollama Provider settings.
 *
 * This component is used within the ProviderSettings component to
 * allow users to configure the Ollama Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OllamaSettings component.
 */
export const OllamaSettings = ( { isConfigured = false } ) => {
	const { featureName } = useFeatureContext();
	const providerName = 'ollama';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<OllamaBaseSettings
					providerSettings={ providerSettings }
					providerName={ providerName }
					onChange={ onChange }
				/>
			) }

			<ModelsSelector
				providerSettings={ providerSettings }
				providerName={ providerName }
				onChange={ onChange }
			/>

			{ [
				'feature_content_resizing',
				'feature_title_generation',
			].includes( featureName ) && (
				<SettingsRow
					label={ __( 'Number of suggestions', 'classifai' ) }
					description={ __(
						'Number of suggestions that will be generated in one request.',
						'classifai'
					) }
				>
					<InputControl
						id={ `${ providerName }_number_of_suggestions` }
						type="number"
						min={ 1 }
						max={ 10 }
						value={ providerSettings.number_of_suggestions || 1 }
						onChange={ ( value ) =>
							onChange( { number_of_suggestions: value } )
						}
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
		</>
	);
};
