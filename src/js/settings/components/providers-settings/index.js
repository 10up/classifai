/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import {
	Icon,
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
 * configured/not configured status. Fetches profiles and configs from
 * the Provider-configs REST API on mount.
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

	useEffect( () => {
		apiFetch( { path: '/classifai/v1/provider-configs' } )
			.then( ( res ) => {
				if ( res.profiles ) {
					setProviderProfiles( res.profiles );
				}
				if ( res.configs ) {
					setProviderConfigs( res.configs );
				}
			} )
			.catch( () => {
				// Error loading; leave profiles/configs empty.
			} );
	}, [ setProviderProfiles, setProviderConfigs ] );

	const profileIds = Object.keys( providerProfiles );
	const hasProfiles = profileIds.length > 0;

	return (
		<div className="classifai-settings-dashboard classifai-providers-settings">
			{ ! hasProfiles && (
				<div className="classifai-providers-settings__loading">
					<Spinner />
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
