import { TabPanel, SlotFillProvider } from '@wordpress/components';

import '../../../scss/settings.scss';
import '../providers'; // TODO: This is for testing purposes only, please remove this line

import { Header, SettingsWrapper } from '../components';
import { updateUrl } from '../utils/utils';
const { classifAISettings } = window;
const { services } = classifAISettings;

const Content = () => {
	const serviceKeys = Object.keys( services );
	const serviceOptions = serviceKeys.map( ( slug ) => {
		return {
			name: slug,
			title: services[ slug ],
			className: slug,
		};
	} );

	// Switch the default settings tab based on the URL tab query
	const urlParams = new URLSearchParams( window.location.search );
	const requestedTab = urlParams.get( 'tab' );
	const initialTab = serviceKeys.includes( requestedTab )
		? requestedTab
		: 'language_processing';

	return (
		<TabPanel
			className={ 'setting-tabs' }
			activeClass="active-tab"
			initialTabName={ initialTab }
			tabs={ serviceOptions }
			onSelect={ ( tabName ) => updateUrl( 'tab', tabName ) }
		>
			{ ( tab ) => {
				return (
					<>
						{ serviceOptions.map( ( key ) => {
							if ( key.name !== tab.name ) {
								return null;
							}

							return (
								<SettingsWrapper
									tab={ tab.name }
									key={ tab.name }
								/>
							);
						} ) }
					</>
				);
			} }
		</TabPanel>
	);
};

export const Settings = () => {
	return (
		<SlotFillProvider>
			<Header />
			<Content />
		</SlotFillProvider>
	);
};
