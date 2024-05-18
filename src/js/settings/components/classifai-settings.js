/**
 * WordPress dependencies
 */
import { TabPanel, SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Header, SettingsWrapper } from '.';
import { getInitialService, updateUrl } from '../utils/utils';
import { useSettings } from '../hooks';

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
			{ ( tab ) => {
				return <SettingsWrapper tab={ tab.name } key={ tab.name } />;
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
