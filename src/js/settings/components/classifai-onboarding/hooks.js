/**
 * External dependencies
 */
import { useLocation } from 'react-router-dom';

/**
 * Internal dependencies
 */
import { getNextOnboardingStep } from '../../utils/utils';

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
