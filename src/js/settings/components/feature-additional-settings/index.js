/**
 * WordPress dependencies
 */
import { Slot } from '@wordpress/components';
import { PluginArea } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { getScope } from '../../utils/utils';
import { useFeatureContext } from '../feature-settings/context';
import { DescriptiveTextGeneratorSettings } from './descriptive-text-generator';

const AdditionalSettingsFields = () => {
	const { featureName } = useFeatureContext();

	switch ( featureName ) {
		case 'feature_descriptive_text_generator':
			return <DescriptiveTextGeneratorSettings />;

		default:
			return null;
	}
};

/**
 * Feature Additional Settings component.
 */
export const FeatureAdditionalSettings = () => {
	const { featureName } = useFeatureContext();

	return (
		<>
			<AdditionalSettingsFields />
			<Slot name="ClassifAIFeatureSettings">
				{ ( fills ) => <> { fills }</> }
			</Slot>
			<PluginArea scope={ getScope( featureName ) } />
		</>
	);
};
