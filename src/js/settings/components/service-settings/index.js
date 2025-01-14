/**
 * External dependencies
 */
import { NavLink, useParams } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Flex,
	FlexItem,
	FlexBlock,
	ToggleControl,
	Button,
	Panel,
	PanelBody,
	Notice,
	Icon,
} from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { isProviderConfigurationNeeded } from '../../utils/utils';
const { features } = window.classifAISettings;

/**
 * ConfigureProviderNotice component to render the notice when the provider is not configured correctly.
 *
 * @return {Object} The ServiceSettings component.
 */
const ConfigureProviderNotice = () => (
	<Notice
		status="warning"
		isDismissible={ false }
		className="classifai-configure-provider-notice"
	>
		<Icon icon="warning" />{ ' ' }
		{ __(
			'This Feature is enabled but needs a Provider configured. Please click the Settings button to complete the setup.',
			'classifai'
		) }
	</Notice>
);

/**
 * ServiceSettings component to render the feature navigation tabs and the feature settings.
 *
 * @return {Object} The ServiceSettings component.
 */
export const ServiceSettings = () => {
	const [ enabled, setEnabled ] = useState( false );
	const { setCurrentService, setIsSaving, setSettings } =
		useDispatch( STORE_NAME );
	const {
		createSuccessNotice,
		createErrorNotice,
		removeNotices,
		removeNotice,
	} = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);

	const { service } = useParams();
	const isInitialPageLoad = useRef( true );

	const { settings, getFeatureSettings } = useSelect( ( select ) => {
		const store = select( STORE_NAME );

		return {
			settings: store.getSettings(),
			getFeatureSettings: ( key, featureName ) =>
				store.getFeatureSettings( key, featureName ),
		};
	} );

	useEffect( () => {
		setCurrentService( service );
	}, [ service, setCurrentService ] );

	const serviceFeatures = features[ service ] || {};

	const saveSettings = () => {
		// Remove existing notices.
		if ( removeNotices ) {
			removeNotices( notices.map( ( { id } ) => id ) );
		} else if ( removeNotice ) {
			notices.forEach( ( { id } ) => removeNotice( id ) );
		}

		setIsSaving( true );
		apiFetch( {
			path: '/classifai/v1/settings/',
			method: 'POST',
			data: {
				settings,
				is_setup: true,
				step: 'enable_features',
			},
		} )
			.then( ( res ) => {
				if ( res.errors && res.errors.length ) {
					res.errors.forEach( ( error ) => {
						createErrorNotice( error.message, {
							id: 'error-generic-notices',
						} );
					} );
					setIsSaving( false );
					window.scrollTo( {
						top: 0,
						behavior: 'smooth',
					} );
					return;
				}

				const message = enabled
					? __( 'Feature enabled successfully.', 'classifai' )
					: __( 'Feature disabled successfully.', 'classifai' );

				createSuccessNotice( message, {
					type: 'snackbar',
				} );
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
						id: 'error-generic-notices',
					}
				);
				setIsSaving( false );
				window.scrollTo( {
					top: 0,
					behavior: 'smooth',
				} );
			} );
	};

	const statuses = Object.keys( settings )
		.map( ( key ) => settings[ key ]?.status )
		.join( '' );

	useEffect( () => {
		if ( isInitialPageLoad.current ) {
			isInitialPageLoad.current = false;
			return;
		}
		saveSettings();
	}, [ statuses ] );

	return (
		<div className="classifai-settings-dashboard">
			{ Object.keys( serviceFeatures ).map( ( feature, index ) => (
				<Panel key={ index }>
					<PanelBody>
						<Flex gap={ 8 } align="top">
							<FlexItem style={ { marginTop: '4px' } }>
								<Flex gap={ 2 }>
									<ToggleControl
										className="classifai-feature-status"
										checked={
											'1' ===
											getFeatureSettings(
												'status',
												feature
											)
										}
										onChange={ ( value ) => {
											setEnabled( value );
											wp.data
												.dispatch( STORE_NAME )
												.setFeatureSettings(
													{
														status: value
															? '1'
															: '0',
													},
													feature
												);
										} }
										__nextHasNoMarginBottom
									/>
								</Flex>
							</FlexItem>
							<FlexBlock>
								<strong>
									{ serviceFeatures[ feature ]?.label }
								</strong>
								<div
									dangerouslySetInnerHTML={ {
										__html: serviceFeatures[ feature ]
											?.enable_description,
									} }
								/>
								{ !! isProviderConfigurationNeeded(
									getFeatureSettings( '', feature )
								) && <ConfigureProviderNotice /> }
							</FlexBlock>
							<FlexItem>
								<NavLink to={ feature } key={ feature }>
									<Button
										variant="secondary"
										disabled={
											'1' !==
											getFeatureSettings(
												'status',
												feature
											)
										}
									>
										{ __( 'Settings', 'classifai' ) }
									</Button>
								</NavLink>
							</FlexItem>
						</Flex>
					</PanelBody>
				</Panel>
			) ) }
		</div>
	);
};
