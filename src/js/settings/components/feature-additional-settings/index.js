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
import { ImageTagGeneratorSettings } from './image-tag-generator';

const AdditionalSettingsFields = () => {
	const { featureName } = useFeatureContext();

	switch ( featureName ) {
		case 'feature_descriptive_text_generator':
			return <DescriptiveTextGeneratorSettings />;

		case 'feature_image_tags_generator':
			return <ImageTagGeneratorSettings />;

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
