/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../data/store';
import { OllamaBaseSettings } from './base';
import { useFeatureContext } from '../../feature-settings/context';
import { SettingsRow } from '../../settings-row';

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

	const models = [
		{ label: __( '-- Choose Model --', 'classifai' ), value: '' },
	];

	// Convert providerSettings.models to an array from an object.
	if (
		providerSettings?.models &&
		! Array.isArray( providerSettings.models )
	) {
		for ( const [ key, value ] of Object.entries(
			providerSettings.models
		) ) {
			models.push( { label: value, value: key } );
		}
	}

	// Auto-select the first model if no model is selected and models are available.
	useEffect( () => {
		if (
			! providerSettings?.model &&
			models.length > 1 &&
			models[ 1 ]?.value
		) {
			onChange( { model: models[ 1 ].value } );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ providerSettings?.models, providerSettings?.model ] );

	// Check if we have actual models (not just the placeholder).
	const hasModels = models.length > 1;

	return (
		<>
			<SettingsRow
				label={ __( 'Model', 'classifai' ) }
				description={ __(
					'Choose the model you want to use for requests. If no models are shown or you want to use a different model, please ensure this is installed in Ollama first.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_model` }
					onChange={ ( value ) => onChange( { model: value } ) }
					value={ providerSettings?.model || '' }
					options={ models }
					disabled={ ! hasModels }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
			{ ! isConfigured && (
				<OllamaBaseSettings
					providerSettings={ providerSettings }
					providerName={ providerName }
					onChange={ onChange }
				/>
			) }
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
