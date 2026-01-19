/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { Panel, PanelBody, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../data/store';

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
		<div className="classifai-providers-settings">
			<h2 className="classifai-providers-settings__title">
				{ __( 'Providers', 'classifai' ) }
			</h2>
			<p className="classifai-providers-settings__description">
				{ __(
					'Configure your AI Provider credentials here to use them across multiple Features. You can still override credentials for individual Features when needed.',
					'classifai'
				) }
			</p>

			{ ! hasProfiles && (
				<div className="classifai-providers-settings__loading">
					<Spinner />
				</div>
			) }

			{ hasProfiles && (
				<Panel>
					<PanelBody initialOpen={ true }>
						<ul className="classifai-providers-settings__list">
							{ profileIds.map( ( profileId ) => {
								const profile = providerProfiles[ profileId ];
								const config = providerConfigs[ profileId ];
								const credFields =
									profile?.credential_fields ?? [];
								const needsConfig = credFields.length > 0;
								const hasCreds =
									needsConfig &&
									config &&
									credFields.some(
										( f ) =>
											f !== 'authenticated' &&
											config[ f ] !== null &&
											String( config[ f ] ).trim() !== ''
									);

								let statusText;
								if ( ! needsConfig ) {
									statusText = __(
										'No configuration needed',
										'classifai'
									);
								} else if ( hasCreds ) {
									statusText = __(
										'Configured',
										'classifai'
									);
								} else {
									statusText = __(
										'Not configured',
										'classifai'
									);
								}

								return (
									<li
										key={ profileId }
										className="classifai-providers-settings__item"
									>
										<span className="classifai-providers-settings__label">
											{ profile?.label ?? profileId }
										</span>
										<span className="classifai-providers-settings__status">
											{ statusText }
										</span>
									</li>
								);
							} ) }
						</ul>
					</PanelBody>
				</Panel>
			) }
		</div>
	);
};
