/**
 * External dependencies
 */
import { Outlet } from 'react-router-dom';

// Export the steps of the onboarding process.
export { EnableFeatures } from './enable-features';
export { ConfigureFeatures } from './configure-features';
export { FinishStep } from './finish-step';
export { ClassifAIRegistrationStep } from './classifai-registration';

/**
 * ClassifAI Onboarding Component.
 *
 * This component handles the rendering of the entire onboarding process for ClassifAI.
 * It guides users through the necessary steps to configure and enable various features.
 *
 * @return {React.ReactElement} ClassifAIOnboarding component.
 */
export const ClassifAIOnboarding = () => {
	return (
		<div className="classifai-setup__content">
			<Outlet />
		</div>
	);
};
