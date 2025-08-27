/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';
import { STORE_NAME } from '../../../data/store';
import { ElevenLabsSettings } from './base';

/**
 * Component for the ElevenLabs Speech to Text Provider settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} ElevenLabsSpeechToTextSettings component.
 */
export const ElevenLabsSpeechToTextSettings = ( { isConfigured = false } ) => {
	const providerName = 'elevenlabs_speech_to_text';
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
		models.push(
			...Object.entries( providerSettings.models ).map(
				( [ key, model ] ) => ( {
					label: model.display_name || key,
					value: model.id || key,
				} )
			)
		);
	}

	return (
		<>
			{ ! isConfigured && (
				<ElevenLabsSettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			<SettingsRow
				label={ __( 'Model', 'classifai' ) }
				description={ __(
					'Choose the model you want to use.',
					'classifai'
				) }
			>
				<SelectControl
					id={ `${ providerName }_model` }
					onChange={ ( value ) => onChange( { model: value } ) }
					value={ providerSettings?.model || '' }
					options={ models }
					disabled={ models.length <= 1 }
					__nextHasNoMarginBottom
				/>
			</SettingsRow>
		</>
	);
};
