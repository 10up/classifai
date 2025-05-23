/**
 * WordPress dependencies
 */
import {
	Flex,
	FlexItem,
	Icon,
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SettingsRow } from '../settings-row';
import { STORE_NAME } from '../../data/store';
import { OpenAISettings } from './openai';
import { useFeatureContext } from '../feature-settings/context';
import { registerCoreBlocks } from '@wordpress/block-library';
import { store as coreStore } from '@wordpress/blocks';

// To access core/query details.
registerCoreBlocks();

/**
 * React Component for OpenAI Embeddings settings.
 *
 * This component is used within the ProviderSettings component to allow users to configure the OpenAI Embeddings settings.
 *
 * @param {Object}  props              Component props.
 * @param {boolean} props.isConfigured Whether the provider is configured.
 *
 * @return {React.ReactElement} OpenAIEmbeddingsSettings component.
 */
export const OpenAIEmbeddingsSettings = ( { isConfigured = false } ) => {
	const { featureName } = useFeatureContext();
	const providerName = 'openai_embeddings';
	const allSettings = useSelect(
		( select ) =>
			select( STORE_NAME ).getFeatureSettings() || {}
	);
	const providerSettings = allSettings[ providerName ];
	const { setProviderSettings, setFeatureSettings } = useDispatch( STORE_NAME );
	const onChange = ( data ) => setProviderSettings( providerName, data );
	const blockVariations = useSelect( select => select( coreStore ).getBlockVariations( 'core/query' ) );
	const defaultTemplate = allSettings['default_template'];

	const iconWrapperStyle = {
		border: '1px solid #e0e0e0',
		width: '100px',
		padding: '0.5rem',
		borderRadius: '6px',
		cursor: 'pointer',
	}

	return (
		<>
			{ ! isConfigured && (
				<OpenAISettings
					providerSettings={ providerSettings }
					onChange={ onChange }
				/>
			) }
			{ [ 'feature_recommended_content' ].includes( featureName ) && (
				<>
					<SettingsRow label={ __( 'Threshold %', 'classifai' ) }>
						<InputControl
							id={ 'embedding-threshold' }
							type="number"
							style={ { width: '10%' } }
							value={ providerSettings.embedding_threshold }
							onChange={ ( value ) =>
								onChange( { embedding_threshold: value } )
							}
							min="1"
							max="100"
						/>
					</SettingsRow>
					<SettingsRow label={ __( 'Default Template', 'classifai' ) }>
						<Flex align="normal" justify="start" gap={ 2 }>
							{ blockVariations.map( variation => (
								<FlexItem
									style={ {
										...iconWrapperStyle,
										borderColor: defaultTemplate === variation.name ? 'var(--wp-admin-theme-color)' : '#e0e0e0',
										borderWidth: defaultTemplate === variation.name && '2px',
										backgroundColor: defaultTemplate === variation.name && 'color-mix(in srgb, var(--wp-admin-theme-color) 10%, transparent)',
									} }
									onClick={ () => setFeatureSettings( { default_template: variation.name } ) }
								>
									<Icon
										icon={ variation.icon }
										size={ 80 }
										{ ...( defaultTemplate === variation.name && {
											fill: 'var(--wp-admin-theme-color)',
										} ) }
									/>
									<span style={ { display: "block" } }>{ variation.title }</span>
								</FlexItem>
							) ) }
						</Flex>
					</SettingsRow>
				</>
			) }
		</>
	);
};
