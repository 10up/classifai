/**
 * External dependencies
 */
import { useNavigate } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import { Button, Fill } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ClassifAIRegistrationForm } from '../classifai-registration';
import { useSetupPage } from './hooks';

/**
 * ClassifAI Registration Step Component for the Onboarding Process.
 *
 * This component is responsible for rendering the registration step in the ClassifAI onboarding process.
 * It utilizes the ClassifAIRegistrationForm component to display the registration form.
 *
 * @return {React.ReactElement} ClassifAIRegistrationStep component.
 */
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
