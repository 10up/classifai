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
import { TermCleanupSettings } from './term-cleanup';

/**
 * Component for additional settings fields for individual features.
 *
 * This component is used to render additional settings fields for a feature.
 * It determines which feature is current and renders the corresponding settings fields.
 *
 * @return {React.ReactElement} AdditionalSettingsFields component.
 */
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

		case 'feature_term_cleanup':
			return <TermCleanupSettings />;

		default:
			return null;
	}
};

/**
 * Component for additional settings fields for a feature.
 *
 * This component is used to add additional settings for a feature.
 * It also provides a slot for Third-party plugins to add additional settings for a feature.
 *
 * @return {React.ReactElement} FeatureAdditionalSettings component.
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
