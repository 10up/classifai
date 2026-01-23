/**
 * WordPress dependencies
 */
import {
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';

/**
 * Component for the base Ollama Provider settings.
 *
 * This component is used within the ProviderSettings component to
 * allow users to configure the Ollama Provider settings.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 * @param {string}   props.providerName     Name of Provider.
 * @param {boolean}  props.showModels       Whether to show the models dropdown.
 * @return {React.ReactElement} OllamaBaseSettings component.
 */
export const OllamaBaseSettings = ( {
	providerSettings,
	onChange,
	providerName,
	showModels = true,
} ) => {
	const Description = () => (
		<>
			{ __(
				"URL of the locally hosted Ollama instance. Defaults to http://localhost:11434/. Don't have Ollama installed yet?",
				'classifai'
			) }
			<a
				title={ __( 'Install Ollama', 'classifai' ) }
				href="https://ollama.com/"
				target="_blank"
				rel="noopener noreferrer"
			>
				{ ' ' }
				{ __( 'Download Ollama', 'classifai' ) }
			</a>
		</>
	);

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

	return (
		<>
			<SettingsRow
				label={ __( 'Endpoint URL', 'classifai' ) }
				description={ <Description /> }
			>
				<InputControl
					id={ `${ providerName }_endpoint_url` }
					type="text"
					value={ providerSettings?.endpoint_url || '' }
					onChange={ ( value ) =>
						onChange( { endpoint_url: value } )
					}
					__next40pxDefaultSize
				/>
			</SettingsRow>
			{ showModels && (
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
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</SettingsRow>
			) }
		</>
	);
};
