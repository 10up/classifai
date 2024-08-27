/**
 * External dependencies
 */
import { NavLink } from 'react-router-dom';

import {
	DropdownMenu,
	MenuGroup,
	MenuItem,
	VisuallyHidden,
	Icon,
} from '@wordpress/components';
import { external, help, cog, tool } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ClassifAILogo } from '../../utils/icons';
import { useSetupPage } from '../classifai-onboarding/hooks';

export const Header = () => {
	const { isSetupPage, step } = useSetupPage();
	const onBoardingSteps = {
		enable_features: {
			step: __( '1', 'classifai' ),
			title: __( 'Enable Features', 'classifai' ),
		},
		classifai_registration: {
			step: __( '2', 'classifai' ),
			title: __( 'Register ClassifAI', 'classifai' ),
		},
		configure_features: {
			step: __( '3', 'classifai' ),
			title: __( 'Access AI', 'classifai' ),
		},
	};

	return (
		<header id="classifai-header">
			<div className="classifai-header-layout">
				<div id="classifai-branding">
					<div id="classifai-logo">{ ClassifAILogo }</div>
				</div>
				<div id="classifai-header-controls">
					{ isSetupPage && (
						<NavLink
							to="language_processing"
							key="classifai_settings"
							className="components-button has-text has-icon"
						>
							<Icon icon={ cog } />
							{ __( 'Settings', 'classifai' ) }
						</NavLink>
					) }
					{ ! isSetupPage && (
						<NavLink
							to="classifai_setup"
							key="classifai_setup"
							className="components-button has-text has-icon"
						>
							<Icon icon={ tool } />
							{ __( 'Set up', 'classifai' ) }
						</NavLink>
					) }
					<DropdownMenu
						popoverProps={ { placement: 'bottom-end' } }
						toggleProps={ { size: 'compact' } }
						menuProps={ { 'aria-label': __( 'Help options' ) } }
						icon={ help }
						text={ __( 'Help' ) }
					>
						{ ( { onClose } ) => (
							<MenuGroup>
								<MenuItem
									href={
										'https://github.com/10up/classifai#frequently-asked-questions'
									}
									target="_blank"
									rel="noopener noreferrer"
									icon={ external }
									onClick={ onClose }
								>
									{ __( 'FAQs', 'classifai' ) }
									<VisuallyHidden as="span">
										{
											/* translators: accessibility text */
											__(
												'(opens in a new tab)',
												'classifai'
											)
										}
									</VisuallyHidden>
								</MenuItem>
								<MenuItem
									href={
										'https://github.com/10up/classifai/issues/new/choose'
									}
									target="_blank"
									rel="noopener noreferrer"
									icon={ external }
									onClick={ onClose }
								>
									{ __(
										'Report issue/enhancement',
										'classifai'
									) }
									<VisuallyHidden as="span">
										{
											/* translators: accessibility text */
											__(
												'(opens in a new tab)',
												'classifai'
											)
										}
									</VisuallyHidden>
								</MenuItem>
							</MenuGroup>
						) }
					</DropdownMenu>
				</div>
			</div>
			{ isSetupPage && (
				<div className="classifai-setup__header">
					<div className="classifai-setup__step-wrapper">
						<div className="classifai-setup__steps">
							{ Object.keys( onBoardingSteps ).map(
								( stepKey, stepIndex ) => {
									return (
										<React.Fragment key={ stepIndex }>
											<div
												className={ `classifai-setup__step ${
													step === stepKey
														? 'is-active'
														: ''
												}` }
											>
												<div className="classifai-setup__step__label">
													<a href="#"> {/* TODO: Update this with action router navlinks */}
														<span className="step-count">
															{
																onBoardingSteps[
																	stepKey
																].step
															}
														</span>
														<span className="step-title">
															{
																onBoardingSteps[
																	stepKey
																].title
															}
														</span>
													</a>
												</div>
											</div>
											{ Object.keys( onBoardingSteps )
												.length !==
												stepIndex + 1 && (
												<div className="classifai-setup__step-divider"></div>
											) }
										</React.Fragment>
									);
								}
							) }
						</div>
					</div>
				</div>
			) }
		</header>
	);
};
