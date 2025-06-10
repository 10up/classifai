/**
 * External dependencies
 */
import { useNavigate } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Slot, Flex, FlexItem, Icon } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { useFeatureSettings } from '../../data/hooks';

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
	const {
		createSuccessNotice,
		createErrorNotice,
		removeNotices,
		removeNotice,
	} = useDispatch( noticesStore );
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
	const navigate = useNavigate();

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
					window.scrollTo( {
						top: 0,
						behavior: 'smooth',
					} );
					return;
				}
				onSaveSuccess();
				createSuccessNotice(
					__( 'Settings saved successfully.', 'classifai' ),
					{
						type: 'snackbar',
					}
				);
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
				window.scrollTo( {
					top: 0,
					behavior: 'smooth',
				} );
			} );
	};

	return (
		<Flex justify="end" expanded={ false }>
			<FlexItem>
				<Button
					icon={ <Icon icon="arrow-left-alt2" /> }
					iconSize={ 16 }
					onClick={ () => navigate( -1 ) }
					className="classifai-back-button"
					variant="secondary"
				>
					{ __( 'Back to dashboard', 'classifai' ) }
				</Button>
			</FlexItem>

			<FlexItem>
				<Button
					variant="primary"
					onClick={ saveSettings }
					isBusy={ isSaving }
					className="save-settings-button"
				>
					{ isSaving ? __( 'Saving…', 'classifai' ) : label }
				</Button>
			</FlexItem>
		</Flex>
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
