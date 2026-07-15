/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React, { useState } from 'react';

/**
 * WordPress dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { motion, AnimatePresence } from 'motion/react';
import {
	search,
	update,
	paragraph,
	grid,
	table,
	formatListBullets,
} from '@wordpress/icons';

// Define style objects outside of JSX
const containerStyles: CSSProperties = {
	marginBottom: '8px',
};

const optionsContainerStyles: CSSProperties = {
	display: 'flex',
	gap: '8px',
};

const actionButtonStyles: CSSProperties = {
	flex: 1,
	justifyContent: 'center',
	border: '1px solid #e0e0e0',
	padding: '12px 8px',
	borderRadius: '4px',
	backgroundColor: '#f9f9f9',
};

const iconTextStyles: CSSProperties = {
	marginLeft: '6px',
};

const toneButtonContainerStyles: CSSProperties = {
	marginBottom: '8px',
	marginTop: '12px',
};

const toneButtonStyles: CSSProperties = {
	width: '100%',
	justifyContent: 'flex-start',
	textAlign: 'left',
	marginBottom: '4px',
	padding: '8px 12px',
	border: 'none',
};

const actionButtonTextStyles: CSSProperties = {
	marginLeft: '8px',
};

const actionSectionStyles: CSSProperties = {
	borderTop: '1px solid #e0e0e0',
	paddingTop: '8px',
};

const actionItemButtonStyles: CSSProperties = {
	width: '100%',
	justifyContent: 'flex-start',
	textAlign: 'left',
	padding: '8px 12px',
	border: 'none',
};

/**
 * Props for the QuickActionOptions component
 */
export interface QuickActionOptionsProps {
	onOptionSelect: ( option: string ) => void;
	hasContent: boolean;
}

/**
 * QuickActionOptions component
 *
 * Displays quick action buttons for common AI tasks
 *
 * @param {QuickActionOptionsProps} props Component props
 * @return {React.ReactElement} Quick action options UI
 */
export const QuickActionOptions: React.FC< QuickActionOptionsProps > = ( {
	onOptionSelect,
	hasContent = false,
} ) => {
	const [ showFullOptions, setShowFullOptions ] =
		useState< boolean >( false );

	return (
		<div
			className="classifai-quick-action-options"
			style={ containerStyles }
		>
			<AnimatePresence>
				<motion.div style={ optionsContainerStyles }>
					{ !! hasContent && (
						<>
							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'proofread' ) }
								style={ actionButtonStyles }
							>
								<Icon icon={ search } />
								<span style={ iconTextStyles }>
									{ __( 'Proofread', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => {
									setShowFullOptions( ! showFullOptions );
								} }
								style={ actionButtonStyles }
							>
								<Icon icon={ update } />
								<span style={ iconTextStyles }>
									{ __( 'Rewrite', 'classifai' ) }
								</span>
							</Button>
						</>
					) }
				</motion.div>

				{ showFullOptions && (
					<motion.div
						layoutId="quick-action-options--content"
						initial={ { opacity: 0, height: 0 } }
						animate={ { opacity: 1, height: 'auto' } }
						exit={ { opacity: 0, height: 0 } }
					>
						<div style={ toneButtonContainerStyles }>
							<Button
								className="classifai-tone-button"
								onClick={ () =>
									onOptionSelect( 'tone-friendly' )
								}
								style={ toneButtonStyles }
							>
								<span role="img" aria-label="Friendly">
									😊
								</span>
								<span style={ actionButtonTextStyles }>
									{ __( 'Friendly', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-tone-button"
								onClick={ () =>
									onOptionSelect( 'tone-professional' )
								}
								style={ toneButtonStyles }
							>
								<span role="img" aria-label="Professional">
									💼
								</span>
								<span style={ actionButtonTextStyles }>
									{ __( 'Professional', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-tone-button"
								onClick={ () =>
									onOptionSelect( 'tone-concise' )
								}
								style={ toneButtonStyles }
							>
								<span role="img" aria-label="Concise">
									⚡
								</span>
								<span style={ actionButtonTextStyles }>
									{ __( 'Concise', 'classifai' ) }
								</span>
							</Button>
						</div>

						<div style={ actionSectionStyles }>
							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'summary' ) }
								style={ actionItemButtonStyles }
							>
								<Icon icon={ paragraph } />
								<span style={ actionButtonTextStyles }>
									{ __( 'Summary', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'key-points' ) }
								style={ actionItemButtonStyles }
							>
								<Icon icon={ grid } />
								<span style={ actionButtonTextStyles }>
									{ __( 'Key Points', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'table' ) }
								style={ actionItemButtonStyles }
							>
								<Icon icon={ table } />
								<span style={ actionButtonTextStyles }>
									{ __( 'Table', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'list' ) }
								style={ actionItemButtonStyles }
							>
								<Icon icon={ formatListBullets } />
								<span style={ actionButtonTextStyles }>
									{ __( 'List', 'classifai' ) }
								</span>
							</Button>
						</div>
					</motion.div>
				) }
			</AnimatePresence>
		</div>
	);
};
