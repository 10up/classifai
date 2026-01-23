/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';
import { STORE_NAME } from '../../../data/store';
import { useFeatureContext } from '../../feature-settings/context';
import { AmazonPollyBaseSettings } from './base';

/**
 * Component for Amazon Polly Text to Speech settings.
 *
 * @param {Object}  props              Component props.
 * @param {string}  props.providerName The provider name.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} AmazonPollyTextToSpeechSettings component.
 */
export const AmazonPollyTextToSpeechSettings = ( {
	providerName,
	isConfigured = false,
} ) => {
	const { featureName } = useFeatureContext();
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<>
					<AmazonPollyBaseSettings
						providerSettings={ providerSettings }
						onChange={ onChange }
					/>
				</>
			) }

			{ 'feature_text_to_speech' === featureName && (
				<>
					<SettingsRow
						label={ __( 'Engine', 'classifai' ) }
						description={
							<>
								{ __( 'Amazon Polly offers', 'classifai' ) }{ ' ' }
								<a href="https://docs.aws.amazon.com/polly/latest/dg/long-form-voice-overview.html">
									{ __( 'Long-Form', 'classifai' ) }
								</a>
								,{ ' ' }
								<a href="https://docs.aws.amazon.com/polly/latest/dg/NTTS-main.html">
									{ __( 'Neural', 'classifai' ) }{ ' ' }
								</a>{ ' ' }
								{ __(
									'and Standard text-to-speech voices. Please check the',
									'classifai'
								) }{ ' ' }
								<a
									href="https://aws.amazon.com/polly/pricing/"
									title="Pricing"
								>
									{ __( 'documentation', 'classifai' ) }
								</a>{ ' ' }
								{ __(
									'to review pricing for Long-Form, Neural and Standard usage.',
									'classifai'
								) }
							</>
						}
					>
						<SelectControl
							id={ `${ providerName }_voice_engine` }
							onChange={ ( value ) =>
								onChange( { voice_engine: value } )
							}
							value={
								providerSettings.voice_engine || 'standard'
							}
							options={ [
								{
									label: __( 'Standard', 'classifai' ),
									value: 'standard',
								},
								{
									label: __( 'Neural', 'classifai' ),
									value: 'neural',
								},
								{
									label: __( 'Long Form', 'classifai' ),
									value: 'long-form',
								},
							] }
							__nextHasNoMarginBottom
						/>
					</SettingsRow>
					<SettingsRow label={ __( 'Voice', 'classifai' ) }>
						<SelectControl
							id={ `${ providerName }_voice` }
							onChange={ ( value ) =>
								onChange( { voice: value } )
							}
							value={ providerSettings.voice || '' }
							options={ ( providerSettings.voices || [] )
								.filter( ( voice ) =>
									voice.SupportedEngines?.includes(
										providerSettings.voice_engine
									)
								)
								.map( ( voice ) => {
									return {
										value: voice.Id,
										label: `${ voice?.LanguageName } - ${ voice?.Name } (${ voice?.Gender })`,
									};
								} ) }
							__nextHasNoMarginBottom
						/>
					</SettingsRow>
				</>
			) }
		</>
	);
};
