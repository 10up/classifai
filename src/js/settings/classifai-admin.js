import { useSelect } from '@wordpress/data';

import { STORE_NAME } from './data/store';
import { ClassifAISettings, ClassifAIOnboarding } from './components';

export const ClassifAIAdmin = () => {
	const settingsScreen = useSelect( select => select( STORE_NAME ).getSettingsScreen() );

	return (
		<>
			{ 'settings' === settingsScreen && <ClassifAISettings /> }
			{ 'onboarding' === settingsScreen && <ClassifAIOnboarding /> }
		</>
	);
};
