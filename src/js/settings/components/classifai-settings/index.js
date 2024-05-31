/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';
import { TabPanel, SlotFillProvider } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { Header, ServiceSettings } from '..';
import { getInitialService, updateUrl } from '../../utils/utils';
import { STORE_NAME } from '../../data/store';

const { services } = window.classifAISettings;

const Content = () => {
	const initialService = getInitialService();
	const { setCurrentService, setSettings, setIsLoaded } =
		useDispatch( STORE_NAME );
	const serviceKeys = Object.keys( services || {} );
	const serviceOptions = serviceKeys.map( ( slug ) => {
		return {
			name: slug,
			title: services[ slug ],
			className: slug,
		};
	} );

	useEffect( () => {
		( async () => {
			const classifAISettings = await apiFetch( {
				path: '/classifai/v1/settings',
			} ); // TODO: handle error

			setSettings( classifAISettings );
			setIsLoaded( true );
		} )();
	}, [] );

	return (
		<TabPanel
			className={ 'setting-tabs' }
			activeClass="active-tab"
			initialTabName={ initialService }
			tabs={ serviceOptions }
			onSelect={ ( tabName ) => {
				setCurrentService( tabName );
				return updateUrl( 'tab', tabName );
			} }
		>
			{ ( service ) => {
				return (
					<ServiceSettings
						service={ service.name }
						key={ service.name }
					/>
				);
			} }
		</TabPanel>
	);
};

export const ClassifAISettings = () => {
	return (
		<SlotFillProvider>
			<Header />
			<Content />
		</SlotFillProvider>
	);
};
