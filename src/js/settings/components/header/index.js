/**
 * External dependencies
 */
import { NavLink } from 'react-router-dom';

/**
 * WordPress dependencies
 */
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

/**
 * Header component for the ClassifAI settings.
 *
 * This component renders the header for the ClassifAI settings page and the onboarding process.
 *
 * @return {React.ReactElement} Header component.
 */
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
		finish: {
			step: __( '4', 'classifai' ),
			title: __( 'Finish', 'classifai' ),
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
									if ( stepKey === 'finish' ) {
										return null;
									}

									const isCompleted =
										stepIndex <
										Object.keys( onBoardingSteps ).indexOf(
											step
										);
									const isCurrent = step === stepKey;
									const shouldShowLink =
										isCompleted || isCurrent;
									const classes = [];
									if ( isCompleted ) {
										classes.push( 'is-complete' );
									}
									if ( isCurrent ) {
										classes.push( 'is-active' );
									}

									const stepLabel = (
										<>
											<span className="step-count">
												{ isCompleted ? (
													<Icon icon="yes" />
												) : (
													<>
														{
															onBoardingSteps[
																stepKey
															].step
														}
													</>
												) }
											</span>
											<span className="step-title">
												{
													onBoardingSteps[ stepKey ]
														.title
												}
											</span>
										</>
									);

									return (
										<React.Fragment key={ stepIndex }>
											<div
												className={ `classifai-setup__step ${ classes.join(
													' '
												) }` }
											>
												<div className="classifai-setup__step__label">
													{ shouldShowLink ? (
														<NavLink
															to={ `/classifai_setup/${ stepKey }` }
															key={ stepKey }
														>
															{ stepLabel }
														</NavLink>
													) : (
														<>{ stepLabel }</>
													) }
												</div>
											</div>
											{ Object.keys( onBoardingSteps )
												.length !==
												stepIndex + 2 && (
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
