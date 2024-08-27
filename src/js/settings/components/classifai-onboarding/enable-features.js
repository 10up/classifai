import {
	ToggleControl,
	Flex,
	FlexItem,
	BaseControl,
	Button,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { FeatureContext } from '../feature-settings/context';
import { EnableToggleControl } from '../feature-settings/enable-feature';
import { SaveSettingsButton } from '../../components/feature-settings/save-settings-button';
import { useFeatureSettings } from '../../data/hooks';
import { useSetupPage } from './hooks';
import { useNavigate } from 'react-router-dom';

export const EnableFeatures = () => {
	const { features, services } = window.classifAISettings;
	const { isSaving } = useFeatureSettings();
	const { step, nextStepPath } = useSetupPage();
	const navigate = useNavigate();

	useEffect( () => {
		if ( 'enable_features' === step && false === isSaving ) {
			navigate( nextStepPath );
		}
	}, [ isSaving, nextStepPath, step ] );

	const featureToggles = Object.keys( services ).map(
		( service, serviceIndex ) => (
			<React.Fragment key={ service }>
				<div className="classifai-feature-box">
					<h3 className="classifai-feature-box-title">
						{ services[ service ] }
					</h3>
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
							<Button>
								{ __( 'Skip for now', 'classifai' ) }
							</Button>
							<SaveSettingsButton
								label={ __( 'Start Setup', 'classifai' ) }
								disableErrorReporting={ true }
							/>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};
