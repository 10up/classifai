/**
 * WordPress dependencies
 */
import { Fill, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Component for Notice about the deprecation of the Personalizer provider.
 *
 * This component displays a notice to inform users about the deprecation of the Personalizer provider.
 *
 * @return {React.ReactElement} The PersonalizerDeprecationNotice component.
 */
const PersonalizerDeprecationNotice = () => (
	<Notice
		status="warning"
		isDismissible={ false }
		className="personalizer-deprecation-notice"
	>
		<p>
			<a
				href="https://learn.microsoft.com/en-us/azure/ai-services/personalizer/"
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'As of September 2023', 'classifai' ) }
			</a>
			{ ', ' }
			{ __(
				'new Personalizer resources can no longer be created in Azure. This is currently the only provider available for the Recommended Content feature and as such, this feature will not work unless you had previously created a Personalizer resource. The Azure AI Personalizer provider is deprecated and will be removed in a future release. We hope to replace this provider with another one in a coming release to continue to support this feature',
				'classifai'
			) }
			{ __( '(see ', 'classifai' ) }
			<a
				href="https://github.com/10up/classifai/issues/392"
				target="_blank"
				rel="noreferrer"
			>
				{ __( 'issue#392', 'classifai' ) }
			</a>
			{ ').' }
		</p>
	</Notice>
);

/**
 * Component for the Recommended Content settings.
 *
 * This component displays a notice to inform users about the deprecation of the Personalizer provider.
 *
 * @return {React.ReactElement} The RecommendedContentSettings component.
 */
export const RecommendedContentSettings = () => {
	return (
		<Fill name="ClassifAIBeforeFeatureSettingsPanel">
			<PersonalizerDeprecationNotice />
		</Fill>
	);
};
