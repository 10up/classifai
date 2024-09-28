import { Button, Fill } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { useNavigate } from 'react-router-dom';
import { ClassifAIRegistrationForm } from '../classifai-registration';
import { useSetupPage } from './hooks';

export const ClassifAIRegistrationStep = () => {
	const navigate = useNavigate();
	const { nextStepPath } = useSetupPage();
	return (
		<>
			<h1 className="classifai-setup-heading">
				{ __( 'Register ClassifAI', 'classifai' ) }
			</h1>
			<div className="classifai-setup__content__row classifai-onboarding__configure ">
				<div className="classifai-setup__content__row__column">
					<ClassifAIRegistrationForm
						onSaveSuccess={ () => navigate( nextStepPath ) }
					/>
					<Fill name="ClassifAIBeforeRegisterSaveButton">
						<Button onClick={ () => navigate( nextStepPath ) }>
							{ __( 'Skip for now', 'classifai' ) }
						</Button>
					</Fill>
				</div>
			</div>
		</>
	);
};
