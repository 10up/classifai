/**
 * External dependencies
 */
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getNextOnboardingStep } from '../../utils/utils';

/**
 * Custom hook to determine if the current page is a setup page and to retrieve the current step, next step, and next step path.
 *
 * This hook provides an object containing:
 * - `isSetupPage`: A boolean indicating whether the current page is a setup page.
 * - `step`: The current step in the setup process.
 * - `nextStep`: The next step in the setup process.
 * - `nextStepPath`: The URL path for the next step.
 *
 * @return {Object} An object containing the setup page status, current step, next step, and next step path.
 */
export const useSetupPage = () => {
	const location = useLocation();
	const isSetupPage =
		location?.pathname?.startsWith( '/classifai_setup' ) || false;
	const step = isSetupPage ? location?.pathname?.split( '/' )[ 2 ] || '' : '';
	const nextStep = step ? getNextOnboardingStep( step ) : '';
	const nextStepPath = nextStep ? `/classifai_setup/${ nextStep }` : '';

	return {
		isSetupPage,
		step,
		nextStep,
		nextStepPath,
	};
};
