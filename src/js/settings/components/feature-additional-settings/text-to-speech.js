/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

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
	const { postTypes, postStatuses } = window.classifAISettings;

	return (
		<>
			<SettingsRow
				label={ __( 'Allowed post statuses', 'classifai' ) }
				description={ __(
					'Choose which post statuses are allowed to generate audio, e.g. disable this for Draft to avoid generating audio for content that is still being edited.',
					'classifai'
				) }
				className="settings-allowed-post-statuses"
			>
				{ Object.keys( postStatuses || {} ).map( ( key ) => {
					return (
						<CheckboxControl
							id={ `post_status_${ key }` }
							key={ key }
							checked={
								featureSettings.post_statuses?.[ key ] === key
							}
							label={ decodeEntities( postStatuses?.[ key ] ) }
							onChange={ ( value ) => {
								setFeatureSettings( {
									post_statuses: {
										...featureSettings.post_statuses,
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
		</>
	);
};
