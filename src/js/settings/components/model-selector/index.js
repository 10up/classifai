/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';

const ModelDescription = ( { modelsDocUrl } ) => (
	<>
		{ __( 'Choose the model you want to use.', 'classifai' ) }
		{ modelsDocUrl && (
			<>
				{ ' ' }
				{ __(
					'Not sure which model to use? You can find more details on models',
					'classifai'
				) }{ ' ' }
				<a
					title={ __( 'Learn more about models', 'classifai' ) }
					href={ modelsDocUrl }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'here', 'classifai' ) }
				</a>
			</>
		) }
		.
	</>
);

export const ModelSelector = ( {
	providerName,
	providerSettings,
	onChange,
	modelsDocUrl = '',
} ) => {
	const models = [
		{ label: __( '-- Choose Model --', 'classifai' ), value: '' },
	];

	// Convert providerSettings.models to an array from an object.
	if ( providerSettings?.models ) {
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
		<SettingsRow
			label={ __( 'Model', 'classifai' ) }
			description={ <ModelDescription modelsDocUrl={ modelsDocUrl } /> }
		>
			<SelectControl
				id={ `${ providerName }_model` }
				onChange={ onChange }
				value={ providerSettings?.model || '' }
				options={ models }
				disabled={ models.length <= 1 }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</SettingsRow>
	);
};
