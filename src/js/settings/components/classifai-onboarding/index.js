import { Outlet } from 'react-router-dom';

// Export the steps of the onboarding process.
export { EnableFeatures } from './enable-features';
export { ConfigureFeatures } from './configure-features';
export { FinishStep } from './finish-step';

export const ClassifAIOnboarding = () => {
	return (
		<div className="classifai-setup__content">
			<Outlet />
		</div>
	);
};
