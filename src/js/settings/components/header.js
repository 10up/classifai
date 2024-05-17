/**
 * External dependencies
 */
import {
	DropdownMenu,
	MenuGroup,
	MenuItem,
	VisuallyHidden,
	Button,
} from '@wordpress/components';
import { external, help, cog, tool } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ClassifAILogo } from '../utils/icons';

export const Header = ( props ) => {
	const { isSetupPage } = props;

	return (
		<header id="classifai-header">
			<div className="classifai-header-layout">
				<div id="classifai-branding">
					<div id="classifai-logo">{ ClassifAILogo }</div>
				</div>
				<div id="classifai-header-controls">
					{ isSetupPage && (
						<Button href="/tools.php?page=classifai" icon={ cog }>
							{ __( 'Settings', 'classifai' ) }
						</Button>
					) }
					{ ! isSetupPage && (
						<Button
							href="/tools.php?page=classifai_setup"
							icon={ tool }
						>
							{ __( 'Set up', 'classifai' ) }
						</Button>
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
		</header>
	);
};
