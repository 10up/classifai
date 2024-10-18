/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Slot } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { useFeatureSettings } from '../../data/hooks';
import { useSetupPage } from '../classifai-onboarding/hooks';

/**
 * Save Settings Button component.
 *
 * This component renders a button that allows users to save the settings for a feature.
 * It also handles the saving of settings via the REST API.
 *
 * @param {Object}   props               Component props.
 * @param {Function} props.onSaveSuccess Callback function to be executed after saving settings.
 * @param {string}   props.label         Button label.
 */
export const SaveSettingsButton = ( {
	onSaveSuccess = () => {},
	label = __( 'Save Settings', 'classifai' ),
} ) => {
	const { featureName } = useFeatureSettings();
	const { isSetupPage, step } = useSetupPage();
	const { createErrorNotice, removeNotices, removeNotice } =
		useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);
	const { setIsSaving, setSettings } = useDispatch( STORE_NAME );
	const isSaving = useSelect( ( select ) =>
		select( STORE_NAME ).getIsSaving()
	);
	const settings = useSelect( ( select ) =>
		select( STORE_NAME ).getSettings()
	);

	/**
	 * Save settings for a feature.
	 */
	const saveSettings = () => {
		// Remove existing notices.
		if ( removeNotices ) {
			removeNotices( notices.map( ( { id } ) => id ) );
		} else if ( removeNotice ) {
			notices.forEach( ( { id } ) => removeNotice( id ) );
		}
		setIsSaving( true );

		const data = {
			settings: featureName
				? { [ featureName ]: settings[ featureName ] }
				: settings,
		};

		if ( isSetupPage ) {
			data.is_setup = true;
			data.step = step;
		}

		apiFetch( {
			path: '/classifai/v1/settings/',
			method: 'POST',
			data,
		} )
			.then( ( res ) => {
				if ( res.errors && res.errors.length ) {
					res.errors.forEach( ( error ) => {
						createErrorNotice( error.message, {
							id: `error-${ featureName }`,
						} );
					} );
					setSettings( res.settings );
					setIsSaving( false );
					return;
				}
				onSaveSuccess();
				setSettings( res.settings );
				setIsSaving( false );
			} )
			.catch( ( error ) => {
				createErrorNotice(
					error.message ||
						__(
							'An error occurred while saving settings.',
							'classifai'
						),
					{
						id: `error-${ featureName }`,
					}
				);
				setIsSaving( false );
			} );
	};

	return (
		<Button
			className="save-settings-button"
			variant="primary"
			onClick={ saveSettings }
			isBusy={ isSaving }
		>
			{ isSaving ? __( 'Saving…', 'classifai' ) : label }
		</Button>
	);
};

export const SaveButtonSlot = ( { children } ) => {
	return (
		<>
			<Slot name="ClassifAIBeforeSaveButton">
				{ ( fills ) => <>{ fills }</> }
			</Slot>
			{ children }
			<Slot name="ClassifAIAfterSaveButton">
				{ ( fills ) => <>{ fills }</> }
			</Slot>
		</>
	);
};
