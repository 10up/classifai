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
import { ContentResizingSettings } from './content-resizing';
import { ExcerptGenerationSettings } from './excerpt-generation';
import { ClassificationSettings } from './classification';
import { ModerationSettings } from './moderation';
import { Smart404Settings } from './smart-404';
import { RecommendedContentSettings } from './recommended-content';

const AdditionalSettingsFields = () => {
	const { featureName } = useFeatureContext();

	switch ( featureName ) {
		case 'feature_classification':
			return <ClassificationSettings />;

		case 'feature_title_generation':
			return <TitleGenerationSettings />;

		case 'feature_excerpt_generation':
			return <ExcerptGenerationSettings />;

		case 'feature_content_resizing':
			return <ContentResizingSettings />;

		case 'feature_descriptive_text_generator':
			return <DescriptiveTextGeneratorSettings />;

		case 'feature_image_tags_generator':
			return <ImageTagGeneratorSettings />;

		case 'feature_text_to_speech_generation':
			return <TextToSpeechSettings />;

		case 'feature_moderation':
			return <ModerationSettings />;

		case 'feature_smart_404':
			return <Smart404Settings />;

		case 'feature_recommended_content':
			return <RecommendedContentSettings />;

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
