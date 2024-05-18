/**
 * WordPress dependencies
 */
import { TabPanel, SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Header, ServiceSettings } from '..';
import { getInitialService, updateUrl } from '../../utils/utils';
import { useSettings } from '../../hooks';

const { services } = window.classifAISettings;

const Content = () => {
	const { setCurrentService } = useSettings( true ); // Load settings.
	const initialService = getInitialService();

	const serviceKeys = Object.keys( services || {} );
	const serviceOptions = serviceKeys.map( ( slug ) => {
		return {
			name: slug,
			title: services[ slug ],
			className: slug,
		};
	} );

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
