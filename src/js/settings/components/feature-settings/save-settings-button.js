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
 */
export const SaveSettingsButton = ( {
	disableErrorReporting = false,
	label = __( 'Save Settings', 'classifai' ),
} ) => {
	const { featureName } = useFeatureSettings();
	const { isSetupPage, step } = useSetupPage();
	const { createErrorNotice, removeNotices } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);
	const { setIsSaving, setSettings, setSaveErrors } =
		useDispatch( STORE_NAME );
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
		removeNotices( notices.map( ( { id } ) => id ) );
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
					if ( ! disableErrorReporting ) {
						res.errors.forEach( ( error ) => {
							createErrorNotice( error.message, {
								id: `error-${ featureName }`,
							} );
						} );
					}
					setSettings( res.settings );
					setIsSaving( false );
					setSaveErrors( res.errors );
					return;
				}
				setSaveErrors( [] );
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
			<Slot name="BeforeSaveButton">{ ( fills ) => <>{ fills }</> }</Slot>
			{ children }
			<Slot name="AfterSaveButton">{ ( fills ) => <>{ fills }</> }</Slot>
		</>
	);
};
