import { useSelect, useDispatch } from '@wordpress/data';
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

export const AmazonPollySettings = () => {
	const providerName = 'aws_polly';
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			<SettingsRow label={ __( 'Access key', 'classifai' ) }>
				<InputControl
					type="text"
					value={ providerSettings.access_key_id || '' }
					onChange={ ( value ) =>
						onChange( { access_key_id: value } )
					}
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Secret access key', 'classifai' ) }
				description={ __(
					'Enter the AWS secret access key.',
					'classifai'
				) }
			>
				<InputControl
					type="password"
					value={ providerSettings.secret_access_key || '' }
					onChange={ ( value ) =>
						onChange( { secret_access_key: value } )
					}
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Region', 'classifai' ) }
				description={ __(
					'Enter the AWS Region. eg: <code>us-east-1</code>.',
					'classifai'
				) }
			>
				<InputControl
					type="text"
					value={ providerSettings.aws_region || '' }
					onChange={ ( value ) => onChange( { aws_region: value } ) }
				/>
			</SettingsRow>
			<SettingsRow
				label={ __( 'Engine', 'classifai' ) }
				description={
					<>
						{ __( 'Amazon Polly offers ', 'classifai' ) }
						<a href="https://docs.aws.amazon.com/polly/latest/dg/long-form-voice-overview.html">
							{ __( 'Long-Form', 'classifai' ) }
						</a>
						,{ ' ' }
						<a href="https://docs.aws.amazon.com/polly/latest/dg/NTTS-main.html">
							{ __( 'Neural', 'classifai' ) }{ ' ' }
						</a>
						{ __(
							' and Standard text-to-speech voices. Please check the ',
							'classifai'
						) }
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
					onChange={ ( value ) =>
						onChange( { voice_engine: value } )
					}
					value={ providerSettings.voice_engine || 'standard' }
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
				/>
			</SettingsRow>
			<SettingsRow label={ __( 'Voice', 'classifai' ) }>
				<SelectControl
					onChange={ ( value ) => onChange( { voice: value } ) }
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
				/>
			</SettingsRow>
		</>
	);
};
