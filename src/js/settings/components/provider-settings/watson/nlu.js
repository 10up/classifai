/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_NAME } from '../../../data/store';
import { IBMWatsonBaseSettings } from './base';

/**
 * Component for IBM Watson NLU settings.
 *
 * @param {Object}  props              Component props.
 * @param {string}  props.providerName The provider name.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} IBMWatsonNLUSettings component.
 */
export const IBMWatsonNLUSettings = ( {
	providerName,
	isConfigured = false,
} ) => {
	const providerSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings( providerName ) || {}
	);
	const { setProviderSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );

	return (
		<>
			{ ! isConfigured && (
				<>
					<IBMWatsonBaseSettings
						providerSettings={ providerSettings }
						onChange={ onChange }
					/>
				</>
			) }
		</>
	);
};
