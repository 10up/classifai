/**
 * WordPress dependencies
 */
import { TabPanel, SlotFillProvider } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Header, SettingsWrapper } from '../components';
import { updateUrl } from '../utils/utils';
import { useSettings } from '../hooks/use-settings';

const { services } = window.classifAISettings;

const Content = () => {
	useSettings( true ); // Load settings.

	// Switch the default settings tab based on the URL tab query
	const urlParams = new URLSearchParams( window.location.search );
	const requestedTab = urlParams.get( 'tab' );
	const initialService = Object.keys( services || {} ).includes(
		requestedTab
	)
		? requestedTab
		: 'language_processing';

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
				return updateUrl( 'tab', tabName );
			} }
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
