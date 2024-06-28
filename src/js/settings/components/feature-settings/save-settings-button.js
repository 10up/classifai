/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { useFeatureContext } from './context';

/**
 * Save Settings Button component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.featureName Feature name.
 */
export const SaveSettingsButton = () => {
	const { featureName } = useFeatureContext();
	const { createErrorNotice, removeNotices } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);
	const { setIsSaving, setSettings } = useDispatch( STORE_NAME );
	const settings = useSelect( ( select ) =>
		select( STORE_NAME ).getSettings()
	);

	/**
	 * Save settings for a feature.
	 */
	const saveSettings = () => {
		removeNotices( notices.map( ( { id } ) => id ) );
		setIsSaving( true );
		apiFetch( {
			path: '/classifai/v1/settings/',
			method: 'POST',
			data: { [ featureName ]: settings[ featureName ] },
		} )
			.then( ( res ) => {
				if ( res.errors && res.errors.length ) {
					res.errors.forEach( ( error ) =>
						createErrorNotice( error.message )
					);
					setSettings( res.settings );
					setIsSaving( false );
					return;
				}

				setSettings( res.settings );
				setIsSaving( false );
			} )
			.catch( ( error ) => {
				createErrorNotice(
					error.message ||
						__(
							'An error occurred while saving settings.',
							'classifai'
						)
				);
				setIsSaving( false );
			} );
	};

	return (
		<Button
			className="save-settings-button"
			variant="primary"
			onClick={ saveSettings }
		>
			{ __( 'Save Settings', 'classifai' ) }
		</Button>
	);
};
