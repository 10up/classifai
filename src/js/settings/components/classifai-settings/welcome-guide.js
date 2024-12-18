/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, Button, Flex, FlexItem } from '@wordpress/components';
import { close } from '@wordpress/icons';

/**
 * ClassifAI Welcome Guide Component.
 *
 * This component handles the rendering of the Welcome Guide for ClassifAI.
 * It guides users through the necessary steps to configure and enable various features.
 *
 * @param {Object}   props                   Component props.
 * @param {Function} props.closeWelcomeGuide Function to close the welcome guide.
 *
 * @return {React.ReactElement} ClassifAIWelcomeGuide component.
 */
export const ClassifAIWelcomeGuide = ( { closeWelcomeGuide } ) => {
	return (
		<div className="classifai-setup__content classifai-welcome-guide">
			<Flex justifyContent="space-between" align="flex-start">
				<FlexItem>
					<h1>{ __( 'Welcome to ClassifAI', 'classifai' ) }</h1>
				</FlexItem>
				<FlexItem>
					<Button
						icon={ <Icon icon={ close } /> }
						onClick={ () => closeWelcomeGuide() }
						className="classifai-welcome-guide-close"
						label={ __( 'Close welcome guide', 'classifai' ) }
						showTooltip={ true }
					/>
				</FlexItem>
			</Flex>
			<div className="classifai-onboarding__welcome-note">
				<p>
					{ __(
						'ClassifAI helps thousands of people across the world speed up their publishing workflows, using the power of AI.',
						'classifai'
					) }
				</p>
				<p>
					{ __(
						"To get started, you'll need to follow a few simple steps:",
						'classifai'
					) }
				</p>
				<ol>
					<li>
						<strong>
							{ __(
								'Turn on the Feature you want to use. ',
								'classifai'
							) }
						</strong>
					</li>
					<li>
						<strong>
							{ __(
								'Choose which AI Provider you want to use for that Feature. Ensure you have an account setup with that Provider.',
								'classifai'
							) }
						</strong>
					</li>
					<li>
						<strong>
							{ __(
								'Connect that AI provider to the Feature you enabled. ',
								'classifai'
							) }
						</strong>
						<a
							href="https://github.com/10up/classifai?tab=readme-ov-file#set-up-classification-via-ibm-watson"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __(
								'More details on configuring settings',
								'classifai'
							) }
						</a>
					</li>
					<li>
						<strong>
							{ __(
								'Repeat for all additional Features you want to use',
								'classifai'
							) }
						</strong>
					</li>
				</ol>

				<p>
					{ __(
						'Once done, you can start using ClassifAI and see for yourself how much time you save.',
						'classifai'
					) }
				</p>
				<p>
					{ __(
						'If you need any help along the way, ',
						'classifai'
					) }
					<a
						href="https://github.com/10up/classifai#frequently-asked-questions"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'browse our help topics.', 'classifai' ) }
					</a>
				</p>
			</div>
		</div>
	);
};
