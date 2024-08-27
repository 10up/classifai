import { Outlet } from 'react-router-dom';

// Export the steps of the onboarding process.
export { EnableFeatures } from './enable-features';
export { ConfigureFeatures } from './configure-features';
export { ConfigurationStatus } from './configuration-status';

export const ClassifAIOnboarding = () => {
	return (
		<div className="classifai-setup__content">
			<Outlet />
		</div>
	);
};
