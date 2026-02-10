/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { SettingsRow } from '../settings-row';
import { GoogleAISettings } from './googleai';
import { __ } from '@wordpress/i18n';

/**
 * Component for Google AI (Gemini) Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} GoogleAIGeminiSettings component.
 */
export const GoogleAIGeminiSettings = ( { isConfigured = false } ) => {
	const providerName = 'googleai_gemini_api';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	const models = [];

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
	} else {
		models.push( {
			label: __( '-- Choose Model --', 'classifai' ),
			value: '',
		} );
	}

	const ModelDescription = () => (
		<>
			{ __(
				'Choose the model you want to use for requests.',
				'classifai'
			) }{ ' ' }
			{ __(
				'Not sure which model to use? You can find more details on models',
				'classifai'
			) }{ ' ' }
			<a
				title={ __( 'Learn more about models', 'classifai' ) }
				href="https://ai.google.dev/gemini-api/docs/models"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'here', 'classifai' ) }
			</a>
			.
		</>
	);

	return (
		<>
			{ ! isConfigured && (
				<GoogleAISettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			<SettingsRow
				label={ __( 'Model', 'classifai' ) }
				description={ <ModelDescription /> }
			>
				<SelectControl
					id={ `${ providerName }_model` }
					onChange={ ( value ) => onChange( { model: value } ) }
					value={ providerSettings?.model || '' }
					options={ models }
					disabled={ models.length <= 1 }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
