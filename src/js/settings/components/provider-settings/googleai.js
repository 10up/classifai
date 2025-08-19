import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { SettingsRow } from '../settings-row';

/**
 * Component for Google AI Provider settings.
 *
 * This is the base component for Google AI settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 * @param {string}   props.providerName     The provider name. Default is 'googleai'.
 *
 * @return {React.ReactElement} GoogleAISettings component.
 */
export const GoogleAISettings = ( {
	providerSettings,
	onChange,
	providerName = 'googleai',
} ) => {
	const Description = () => (
		<>
			{ __( "Don't have an Google AI (Gemini API) key?", 'classifai' ) }{ ' ' }
			<a
				title={ __( 'Get an API key', 'classifai' ) }
				href="https://makersuite.google.com/app/apikey"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ __( 'Get an API key', 'classifai' ) }
			</a>{ ' ' }
			{ __( 'now.', 'classifai' ) }
		</>
	);

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
			<SettingsRow
				label={ __( 'API key', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					id="googleai_gemini_api_key"
					type="password"
					value={ providerSettings.api_key || '' }
					onChange={ ( value ) => onChange( { api_key: value } ) }
				/>
			</SettingsRow>
			{ 'googleai_gemini_api' === providerName && (
				<SettingsRow
					label={ __( 'Model', 'classifai' ) }
					description={ <ModelDescription /> }
				>
					<SelectControl
						id={ `${ providerName }_model` }
						onChange={ ( value ) => onChange( { model: value } ) }
						value={ providerSettings?.model || '' }
						options={ models }
						__nextHasNoMarginBottom
					/>
				</SettingsRow>
			) }
		</>
	);
};
