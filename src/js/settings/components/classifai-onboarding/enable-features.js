/**
 * External dependencies
 */
import { useNavigate } from 'react-router-dom';

/**
 * WordPress dependencies
 */
import {
	ToggleControl,
	Flex,
	FlexItem,
	BaseControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { FeatureContext } from '../feature-settings/context';
import { EnableToggleControl } from '../feature-settings/enable-feature';
import { SaveSettingsButton } from '../../components/feature-settings/save-settings-button';
import { useSetupPage } from './hooks';

/**
 * React Component for the feature enabling step in the onboarding process.
 *
 * This component renders the initial step of the onboarding process, allowing users to enable or disable various features.
 * It utilizes the EnableToggleControl component to display the settings for each feature.
 *
 * @return {React.ReactElement} EnableFeatures component.
 */
export const EnableFeatures = () => {
	const [ registrationSettings, setRegistrationSettings ] = useState( {} );
	const { features, services, dashboardUrl } = window.classifAISettings;
	const { nextStepPath } = useSetupPage();
	const navigate = useNavigate();

	// Load the settings.
	useEffect( () => {
		( async () => {
			let regSettings = {};
			try {
				regSettings = await apiFetch( {
					path: '/classifai/v1/registration',
				} );
			} catch ( error ) {
				console.error( error ); // eslint-disable-line no-console
			}

			setRegistrationSettings( regSettings );
		} )();
	}, [ setRegistrationSettings ] );

	const onSaveSuccess = () => {
		if ( registrationSettings?.valid_license ) {
			navigate(
				nextStepPath?.replace(
					'/classifai_registration',
					'/configure_features'
				)
			);
		} else {
			navigate( nextStepPath );
		}
	};

	const featureToggles = Object.keys( services ).map(
		( service, serviceIndex ) => (
			<React.Fragment key={ service }>
				<div className="classifai-feature-box">
					<h4 className="classifai-feature-box-title">
						{ services[ service ] }
					</h4>
					<div className="classifai-features">
						{ Object.keys( features[ service ] ).map(
							( featureSlug ) => (
								<BaseControl key={ featureSlug }>
									<FeatureContext.Provider
										value={ { featureName: featureSlug } }
									>
										<EnableToggleControl>
											{ ( {
												feature,
												status,
												setFeatureSettings,
											} ) => {
												return (
													<Flex>
														<FlexItem>
															<span className="classifai-feature-text">
																{
																	feature.label
																}
															</span>
														</FlexItem>
														<FlexItem>
															<ToggleControl
																checked={
																	status ===
																	'1'
																}
																onChange={ (
																	value
																) =>
																	setFeatureSettings(
																		{
																			status: value
																				? '1'
																				: '0', // TODO: Use boolean, currently using string for compatibility.
																		}
																	)
																}
															/>
														</FlexItem>
													</Flex>
												);
											} }
										</EnableToggleControl>
									</FeatureContext.Provider>
								</BaseControl>
							)
						) }
					</div>
				</div>
				{ Object.keys( services ).length !== serviceIndex + 1 && (
					<hr />
				) }
			</React.Fragment>
		)
	);

	return (
		<>
			<h1 className="classifai-setup-heading">
				{ __( 'Welcome to ClassifAI', 'classifai' ) }
			</h1>
			<div className="classifai-onboarding__configure classifai-onboarding__configure--enable-features classifai-setup__content__row">
				<div className="classifai-setup__content__row__column">
					<div className="classifai-setup-image">
						<img
							src={ `${ window.ClassifAI.plugin_url }assets/img/onboarding-1.png` }
							alt={ __( 'ClassifAI Set up', 'classifai' ) }
						/>
					</div>
				</div>
				<div className="classifai-setup__content__row__column">
					<div className="classifai-step1-content">
						<h2 className="classifai-setup-title">
							{ __(
								'Set up ClassifAI to meet your needs',
								'classifai'
							) }
						</h2>
						{ featureToggles }
						<div className="classifai-settings-footer">
							<Button
								onClick={ () => {
									window.location.href = dashboardUrl;
								} }
							>
								{ __( 'Skip for now', 'classifai' ) }
							</Button>
							<SaveSettingsButton
								label={ __( 'Start Setup', 'classifai' ) }
								onSaveSuccess={ onSaveSuccess }
							/>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
