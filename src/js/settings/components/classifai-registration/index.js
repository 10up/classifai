/**
 * WordPress dependencies
 */
import {
	Panel,
	PanelBody,
	Spinner,
	Button,
	Slot,
	Notice,
	Flex,
	FlexItem,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { Notices } from '../feature-settings/notices';

/**
 * ClassifAI Registration Form Component.
 *
 * This component renders the registration settings form for ClassifAI, allowing users to input and save their registration details.
 *
 * @param {Object}   props               The component props.
 * @param {Function} props.onSaveSuccess The callback function to be executed after successfully saving the settings.
 *
 * @return {React.ReactElement} The rendered ClassifAIRegistrationForm component.
 */
export const ClassifAIRegistrationForm = ( { onSaveSuccess = () => {} } ) => {
	const [ settings, setSettings ] = useState( {} );
	const [ isLoaded, setIsLoaded ] = useState( false );
	const [ error, setError ] = useState( null );

	// Load the settings.
	useEffect( () => {
		( async () => {
			let registrationSettings = {};
			try {
				registrationSettings = await apiFetch( {
					path: '/classifai/v1/registration',
				} );
			} catch ( e ) {
				console.error( e ); // eslint-disable-line no-console
				setError(
					sprintf(
						/* translators: %s: error message */
						__( 'Error: %s', 'classifai' ),
						e.message ||
							__(
								'An error occurred while loading registration settings. Please try again.',
								'classifai'
							)
					)
				);
			}

			setSettings( registrationSettings );
			setIsLoaded( true );
		} )();
	}, [ setSettings, setIsLoaded ] );

	// If settings are not loaded yet, show a spinner.
	if ( ! isLoaded ) {
		return (
			<div className="classifai-loading-settings">
				<Spinner />
				<span className="description">
					{ __( 'Loading settings…', 'classifai' ) }
				</span>
			</div>
		);
	}

	// If there is an error, show an error notice.
	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	return (
		<>
			<Notices feature="registration" />
			<Panel
				header={ __( 'Classifai Registration Settings', 'classifai' ) }
				className="settings-panel"
			>
				<PanelBody>
					<SettingsRow
						label={ __( 'Registered Email', 'classifai' ) }
					>
						<InputControl
							type="email"
							value={ settings.email || '' }
							onChange={ ( value ) => {
								setSettings( { ...settings, email: value } );
							} }
						/>
					</SettingsRow>
					<SettingsRow
						label={ __( 'Registration Key', 'classifai' ) }
						description={
							<>
								{
									// eslint-disable-next-line @wordpress/i18n-translator-comments
									__(
										'Registration is 100% free and provides update notifications and upgrades inside the dashboard.',
										'classifai'
									)
								}{ ' ' }
								<a
									href="https://classifaiplugin.com/#cta"
									target="_blank"
									rel="noreferrer"
								>
									{ __(
										'Register for your key',
										'classifai'
									) }
								</a>
							</>
						}
					>
						<InputControl
							type="password"
							value={ settings.license_key || '' }
							onChange={ ( value ) => {
								setSettings( {
									...settings,
									license_key: value,
								} );
							} }
						/>
					</SettingsRow>
				</PanelBody>
			</Panel>
			<div className="classifai-settings-footer">
				<Slot name="ClassifAIBeforeRegisterSaveButton">
					{ ( fills ) => <>{ fills }</> }
				</Slot>
				<SaveSettingsButton
					settings={ settings }
					setSettings={ setSettings }
					onSaveSuccess={ onSaveSuccess }
				/>
			</div>
		</>
	);
};

/**
 * Save Settings Button component.
 *
 * This component renders a button that allows users to save the settings for the ClassifAI registration form.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.settings      Settings object.
 * @param {Function} props.setSettings   Set settings function.
 * @param {Function} props.onSaveSuccess Callback function to be executed after saving settings.
 * @return {Object} SaveSettingsButton Component.
 */
export const SaveSettingsButton = ( {
	settings,
	setSettings,
	onSaveSuccess = () => {},
} ) => {
	const { createErrorNotice, removeNotices } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);
	const [ isSaving, setIsSaving ] = useState( false );

	/**
	 * Save settings for a feature.
	 */
	const saveSettings = () => {
		removeNotices( notices.map( ( { id } ) => id ) );
		setIsSaving( true );
		apiFetch( {
			path: '/classifai/v1/registration/',
			method: 'POST',
			data: settings,
		} )
			.then( ( res ) => {
				if ( res.errors && res.errors.length ) {
					res.errors.forEach( ( error ) =>
						createErrorNotice( error.message, {
							id: 'error-registration',
						} )
					);
					setSettings( res.settings );
					setIsSaving( false );
					return;
				}

				setSettings( res.settings );
				onSaveSuccess();
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
						id: 'error-registration',
					}
				);
				setIsSaving( false );
			} );
	};

	return (
		<Flex justify="end" expanded={ false }>
			<FlexItem>
				<Button
					variant="primary"
					onClick={ saveSettings }
					isBusy={ isSaving }
				>
					{ isSaving
						? __( 'Saving…', 'classifai' )
						: __( 'Save Settings', 'classifai' ) }
				</Button>
			</FlexItem>
		</Flex>
	);
};

/**
 * ClassifAI Registration Component.
 *
 * This component serves as a wrapper for the ClassifAIRegistrationForm component.
 *
 * @return {React.ReactElement} The ClassifAIRegistration component.
 */
export const ClassifAIRegistration = () => {
	return (
		<div className="classifai-settings-dashboard">
			<ClassifAIRegistrationForm />
		</div>
	);
};
