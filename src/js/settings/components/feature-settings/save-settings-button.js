/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';

/**
 * Save Settings Button component.
 *
 * @param {Object} props             Component props.
 * @param {string} props.featureName Feature name.
 */
export const SaveSettingsButton = ( { featureName } ) => {
	const { setIsSaving, setSettings } = useDispatch( STORE_NAME );
	const settings = useSelect( ( select ) =>
		select( STORE_NAME ).getSettings()
	);

	/**
	 * Save settings for a feature.
	 *
	 * @param {string} featureName Feature name
	 */
	const saveSettings = () => {
		setIsSaving( true );
		apiFetch( {
			path: '/classifai/v1/settings/',
			method: 'POST',
			data: { [ featureName ]: settings[ featureName ] },
		} )
			.then( ( res ) => {
				setSettings( res );
				setIsSaving( false );
			} )
			.catch( ( error ) => {
				// eslint-disable-next-line no-console
				console.error( error ); // TODO: handle error and show a notice
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
