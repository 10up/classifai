/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../../settings-row';

/**
 * React Component for Ollama Models selector.
 *
 * @param {Object}   props                  Component props.
 * @param {Object}   props.providerSettings The provider settings.
 * @param {string}   props.providerName     Name of Provider.
 * @param {Function} props.onChange         Function to call when the provider settings change.
 * @return {React.ReactElement} ModelsSelector component.
 */
export const ModelsSelector = ( {
	providerSettings,
	providerName,
	onChange,
} ) => {
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
				__next40pxDefaultSize
			/>
		</SettingsRow>
	);
};
