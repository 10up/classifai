/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import {
	Icon,
	Notice,
	Panel,
	PanelBody,
	Spinner,
	Tooltip,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';
import { ProviderProfileForm } from './provider-profile-form';

/**
 * ProvidersSettings component.
 *
 * Renders the Providers tab: a list of Provider profiles and their
 * configured/not configured status. Provider profiles and configs are
 * loaded from inline script data on initial page load.
 *
 * @return {import('react').ReactElement} The ProvidersSettings component.
 */
export const ProvidersSettings = () => {
	const { setProviderProfiles, setProviderConfigs } =
		useDispatch( STORE_NAME );
	const { providerProfiles, providerConfigs } = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return {
			providerProfiles: store.getProviderProfiles?.() ?? {},
			providerConfigs: store.getProviderConfigs?.() ?? {},
		};
	}, [] );

	const [ loadError, setLoadError ] = useState( null );
	const [ isRefreshing, setIsRefreshing ] = useState( false );

	/**
	 * Refresh Provider configs from the API.
	 *
	 * Used after saving to get updated authentication status.
	 */
	const refreshProviderConfigs = useCallback( async () => {
		setIsRefreshing( true );
		setLoadError( null );

		try {
			const res = await apiFetch( {
				path: '/classifai/v1/provider-configs',
			} );
			if ( res.profiles ) {
				setProviderProfiles( res.profiles );
			}
			if ( res.configs ) {
				setProviderConfigs( res.configs );
			}
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Failed to load Provider configs:', e );
			setLoadError(
				e.message ||
					__(
						'Failed to load Provider configurations. Please refresh the page and try again.',
						'classifai'
					)
			);
		} finally {
			setIsRefreshing( false );
		}
	}, [ setProviderProfiles, setProviderConfigs ] );

	// Refresh configs on mount only if profiles are empty (fallback for edge cases).
	useEffect( () => {
		if ( Object.keys( providerProfiles ).length === 0 ) {
			refreshProviderConfigs();
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const profileIds = Object.keys( providerProfiles );
	const hasProfiles = profileIds.length > 0;

	return (
		<div className="classifai-settings-dashboard classifai-providers-settings">
			{ loadError && (
				<Notice
					status="error"
					isDismissible={ true }
					onRemove={ () => setLoadError( null ) }
				>
					{ loadError }
				</Notice>
			) }

			{ ! hasProfiles && ! loadError && (
				<div className="classifai-providers-settings__loading">
					{ isRefreshing ? (
						<Spinner />
					) : (
						<p>
							{ __(
								'No Provider profiles available.',
								'classifai'
							) }
						</p>
					) }
				</div>
			) }

			{ hasProfiles &&
				profileIds.map( ( profileId ) => {
					const profile = providerProfiles[ profileId ];
					const config = providerConfigs[ profileId ];
					const credFields = profile?.credential_fields ?? [];
					const needsConfig = credFields.length > 0;
					const isAuthenticated =
						needsConfig && config && config.authenticated === true;

					let statusText;
					if ( ! needsConfig ) {
						statusText = (
							<Tooltip
								text={ __(
									'No configuration needed',
									'classifai'
								) }
							>
								<>
									<Icon icon="yes-alt" />
								</>
							</Tooltip>
						);
					} else if ( isAuthenticated ) {
						statusText = (
							<Tooltip text={ __( 'Configured', 'classifai' ) }>
								<>
									<Icon icon="yes-alt" />
								</>
							</Tooltip>
						);
					} else {
						statusText = (
							<Tooltip
								text={ __( 'Not configured', 'classifai' ) }
							>
								<>
									<Icon icon="dismiss" />
								</>
							</Tooltip>
						);
					}

					const title = (
						<>
							<span className="classifai-providers-settings__status">
								{ statusText }
							</span>
							<span className="classifai-providers-settings__label">
								{ profile?.label ?? profileId }
							</span>
						</>
					);

					return (
						<Panel key={ profileId } className="settings-panel">
							<PanelBody title={ title } initialOpen={ false }>
								{ needsConfig ? (
									<ProviderProfileForm
										profileId={ profileId }
										profile={ profile }
										config={ config }
									/>
								) : (
									<p className="classifai-providers-settings__no-config">
										{ __(
											'This Provider does not require any configuration.',
											'classifai'
										) }
									</p>
								) }
							</PanelBody>
						</Panel>
					);
				} ) }
		</div>
	);
};
