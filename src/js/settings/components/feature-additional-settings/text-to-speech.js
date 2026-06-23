/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { CheckboxControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';

/**
 * Component for Text to Speech feature settings.
 *
 * This component is used within the FeatureSettings component to allow users to configure the Text to Speech feature.
 *
 * @return {React.ReactElement} TextToSpeechSettings component.
 */
export const TextToSpeechSettings = () => {
	const featureSettings = useSelect( ( select ) =>
		select( STORE_NAME ).getFeatureSettings()
	);
	const { setFeatureSettings } = useDispatch( STORE_NAME );
	const { postTypes } = window.classifAISettings;

	return (
		<>
			<SettingsRow
				label={ __( 'Allowed post types', 'classifai' ) }
				description={ __(
					'Choose which post types support this feature.',
					'classifai'
				) }
				className="settings-allowed-post-types"
			>
				{ Object.keys( postTypes || {} ).map( ( key ) => {
					return (
						<CheckboxControl
							id={ key }
							key={ key }
							checked={
								featureSettings.post_types?.[ key ] === key
							}
							label={ postTypes?.[ key ] }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_types: {
										...featureSettings.post_types,
										[ key ]: value ? key : '0',
									},
								} );
							} }
							__nextHasNoMarginBottom
						/>
					);
				} ) }
			</SettingsRow>
			<SettingsRow
				label={ __( 'Audio generation', 'classifai' ) }
				description={ __(
					'Choose when audio is generated. "On demand" skips generation until a visitor clicks to listen on the front-end, then stores the result for reuse.',
					'classifai'
				) }
				className="settings-audio-generation-timing"
			>
				<SelectControl
					// eslint-disable-next-line no-restricted-syntax
					id="generation_timing"
					value={ featureSettings.generation_timing || 'automatic' }
					options={ [
						{
							label: __(
								'Automatic (on publish or update)',
								'classifai'
							),
							value: 'automatic',
						},
						{
							label: __(
								'Manual (generation needs to be manually turned on for each post)',
								'classifai'
							),
							value: 'manual',
						},
						{
							label: __(
								'On demand (generate on first front-end listen)',
								'classifai'
							),
							value: 'on_demand',
						},
					] }
					onChange={ ( value ) => {
						setFeatureSettings( {
							generation_timing: value,
						} );
					} }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</SettingsRow>
		</>
	);
};
