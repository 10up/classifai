/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';

/**
 * Component for Chrome AI Provider settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the Chrome AI Provider settings.
 *
 * @return {React.ReactElement} AzureAIVisionSettings component.
 */
export const ChromeAISettings = () => {
	const [ supported, setSupported ] = useState( false );

	useEffect( () => {
		const checkBrowserSupport = async () => {
			if ( ! window.ai ) {
				return setSupported( false );
			}

			try {
				const capabilities =
					await window.ai.languageModel.capabilities();
				if (
					capabilities &&
					capabilities.available &&
					'readily' === capabilities.available
				) {
					setSupported( true );
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Error getting capabilities: ', error );
				setSupported( false );
			}
		};

		checkBrowserSupport();
	}, [] );

	if ( ! window.ai ) {
		return null;
	}

	const Description = ( { hasSupport = true } ) => {
		if ( hasSupport ) {
			return null;
		}

		return (
			<>
				{ __(
					'Chrome built-in AI is not available on your browser. Please follow the steps ',
					'classifai'
				) }
				<a
					href="https://10up.github.io/classifai/tutorial-chrome-built-in-ai.html"
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'here', 'classifai' ) }
				</a>
				{ __( ' to enable it.', 'classifai' ) }
			</>
		);
	};

	return (
		<>
			<SettingsRow
				label={ __( 'Built-in AI Support', 'classifai' ) }
				description={ <Description hasSupport={ supported } /> }
			>
				{ supported && <Icon icon="yes-alt" /> }
				{ ! supported && <Icon icon="dismiss" /> }
				{ supported
					? __( ' Supported', 'classifai' )
					: __( ' Not Supported', 'classifai' ) }
			</SettingsRow>
		</>
	);
};
