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
import { TextToSpeechSettings } from './text-to-speech';
import { TitleGenerationSettings } from './title-generation';

const AdditionalSettingsFields = () => {
	const { featureName } = useFeatureContext();

	switch ( featureName ) {
		case 'feature_title_generation':
			return <TitleGenerationSettings />;

		case 'feature_descriptive_text_generator':
			return <DescriptiveTextGeneratorSettings />;

		case 'feature_image_tags_generator':
			return <ImageTagGeneratorSettings />;

		case 'feature_text_to_speech_generation':
			return <TextToSpeechSettings />;

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
