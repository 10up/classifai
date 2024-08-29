/**
 * External dependencies
 */
import {
	Route,
	Routes,
	Navigate,
	HashRouter,
	useParams,
	NavLink,
} from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { SlotFillProvider } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import {
	ClassifAIOnboarding,
	FeatureSettings,
	Header,
	ServiceSettings,
} from '..';
import { STORE_NAME } from '../../data/store';
import { FeatureContext } from '../feature-settings/context';
import { ClassifAIRegistration } from '../classifai-registration';
import {
	ConfigureFeatures,
	EnableFeatures,
	FinishStep,
	ClassifAIRegistrationStep,
} from '../classifai-onboarding';
import { useSetupPage } from '../classifai-onboarding/hooks';

const { services, features } = window.classifAISettings;

/**
 * DefaultFeatureSettings component to navigate to the default feature settings.
 * If no feature is selected, it will redirect to the first feature.
 */
const DefaultFeatureSettings = () => {
	const { service } = useParams();
	const feature = Object.keys( features[ service ] || {} )[ 0 ];
	return <Navigate to={ feature } replace />;
};

/**
 * FeatureSettingsWrapper component to render the feature settings.
 * If the feature is not available, it will redirect to the first feature.
 */
const FeatureSettingsWrapper = () => {
	const { service, feature } = useParams();
	const serviceFeatures = Object.keys( features[ service ] || {} );

	if ( ! serviceFeatures.includes( feature ) ) {
		return <Navigate to={ serviceFeatures[ 0 ] } replace />;
	}

	return (
		<FeatureContext.Provider value={ { featureName: feature } }>
			<FeatureSettings />
		</FeatureContext.Provider>
	);
};

const ServiceSettingsWrapper = () => {
	const { service } = useParams();

	// If the service is not available, redirect to the language processing page.
	if ( ! services[ service ] ) {
		return <Navigate to="/language_processing" replace />;
	}

	return <ServiceSettings />;
};

/**
 * ServiceNavigation component to render the service navigation tabs.
 *
 * @return {Object} The ServiceNavigation component.
 */
export const ServiceNavigation = () => {
	const { isSetupPage } = useSetupPage();
	if ( isSetupPage ) {
		return null;
	}

	const serviceKeys = Object.keys( services || {} );
	return (
		<div className="classifai-tabs" aria-orientation="horizontal">
			{ serviceKeys.map( ( service ) => (
				<NavLink
					to={ service }
					key={ service }
					className={ ( { isActive } ) =>
						isActive
							? 'active-tab classifai-tabs-item'
							: 'classifai-tabs-item'
					}
				>
					{ services[ service ] }
				</NavLink>
			) ) }
			<NavLink
				to="classifai_registration"
				key="classifai_registration"
				className={ ( { isActive } ) =>
					isActive
						? 'active-tab classifai-tabs-item'
						: 'classifai-tabs-item'
				}
			>
				{ __( 'ClassifAI Registration', 'classifai' ) }
			</NavLink>
		</div>
	);
};

export const ClassifAISettings = () => {
	const { setSettings, setIsLoaded } = useDispatch( STORE_NAME );

	// Load the settings.
	useEffect( () => {
		( async () => {
			const classifAISettings = await apiFetch( {
				path: '/classifai/v1/settings',
			} ); // TODO: handle error

			setSettings( classifAISettings );
			setIsLoaded( true );
		} )();
	}, [ setSettings, setIsLoaded ] );

	return (
		<SlotFillProvider>
			<HashRouter>
				<Header />
				<div className="classifai-settings-wrapper">
					<ServiceNavigation />
					<Routes>
						<Route
							path=":service"
							element={ <ServiceSettingsWrapper /> }
						>
							<Route
								index
								element={ <DefaultFeatureSettings /> }
							/>
							<Route
								path=":feature"
								element={ <FeatureSettingsWrapper /> }
							/>
						</Route>
						<Route
							path="classifai_setup"
							element={ <ClassifAIOnboarding /> }
						>
							<Route
								index
								element={
									<Navigate to="enable_features" replace />
								}
							/>
							<Route
								path="enable_features"
								element={ <EnableFeatures /> }
							/>
							<Route
								path="classifai_registration"
								element={ <ClassifAIRegistrationStep /> }
							/>
							<Route
								path="configure_features"
								element={ <ConfigureFeatures /> }
							/>
							<Route path="finish" element={ <FinishStep /> } />
							<Route
								path="*"
								element={
									<Navigate to="enable_features" replace />
								}
							/>
						</Route>
						<Route
							path="classifai_registration"
							element={ <ClassifAIRegistration /> }
						/>
						{ /* When no routes match, it will redirect to this route path. Note that it should be registered above. */ }
						<Route
							path="*"
							element={
								<Navigate to="/language_processing" replace />
							}
						/>
					</Routes>
				</div>
			</HashRouter>
		</SlotFillProvider>
	);
};
