import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import { STORE_NAME } from '../../data/store';
import { EnableFeatures } from './enable-features';
import { ConfigureFeatures } from './configure-features';
import { ConfigurationStatus } from './configuration-status';
import { Header, Layout } from '../../components';

export const ClassifAIOnboarding = () => {
	const { setSettings, setIsLoaded } = useDispatch( STORE_NAME );
	const [ step, setStep ] = useState( 'enable_features' );

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
		<>
			<Header />
			<Layout>
				{ 'enable_features' === step && <EnableFeatures step={ step } setStep={ setStep } /> }
				{ 'configure_features' === step && <ConfigureFeatures step={ step } setStep={ setStep } /> }
				{ 'configuration_status' === step && <ConfigurationStatus step={ step } setStep={ setStep } /> }
			</Layout>
		</>
	)
};
