/**
 * WordPress dependencies
 */
import {
	DropdownMenu,
	MenuGroup,
	MenuItem,
	VisuallyHidden,
} from '@wordpress/components';
import { external, help } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ClassifAILogo } from '../../utils/icons';

/**
 * Header component for the ClassifAI settings.
 *
 * This component renders the header for the ClassifAI settings page and the onboarding process.
 *
 * @return {React.ReactElement} Header component.
 */
export const Header = () => {
	return (
		<header id="classifai-header">
			<div className="classifai-header-layout">
				<div id="classifai-branding">
					<div id="classifai-logo">{ ClassifAILogo }</div>
				</div>
				<div id="classifai-header-controls">
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
		</header>
	);
};
