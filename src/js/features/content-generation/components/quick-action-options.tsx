import React, { useState } from 'react';
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
			style={ {
				marginBottom: '8px',
			} }
		>
			<AnimatePresence>
				<motion.div
					style={ {
						display: 'flex',
						gap: '8px',
					} }
				>
					{ !! hasContent && (
						<>
							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'proofread' ) }
								style={ {
									flex: 1,
									justifyContent: 'center',
									border: '1px solid #e0e0e0',
									padding: '12px 8px',
									borderRadius: '4px',
									backgroundColor: '#f9f9f9',
								} }
							>
								<Icon icon={ search } />
								<span style={ { marginLeft: '6px' } }>
									{ __( 'Proofread', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => {
									setShowFullOptions( ! showFullOptions );
								} }
								style={ {
									flex: 1,
									justifyContent: 'center',
									border: '1px solid #e0e0e0',
									padding: '12px 8px',
									borderRadius: '4px',
									backgroundColor: '#f9f9f9',
								} }
							>
								<Icon icon={ update } />
								<span style={ { marginLeft: '6px' } }>
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
						<div
							style={ { marginBottom: '8px', marginTop: '12px' } }
						>
							<Button
								className="classifai-tone-button"
								onClick={ () =>
									onOptionSelect( 'tone-friendly' )
								}
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									marginBottom: '4px',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<span role="img" aria-label="Friendly">
									😊
								</span>
								<span style={ { marginLeft: '8px' } }>
									{ __( 'Friendly', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-tone-button"
								onClick={ () =>
									onOptionSelect( 'tone-professional' )
								}
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									marginBottom: '4px',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<span role="img" aria-label="Professional">
									💼
								</span>
								<span style={ { marginLeft: '8px' } }>
									{ __( 'Professional', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-tone-button"
								onClick={ () =>
									onOptionSelect( 'tone-concise' )
								}
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									marginBottom: '4px',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<span role="img" aria-label="Concise">
									⚡
								</span>
								<span style={ { marginLeft: '8px' } }>
									{ __( 'Concise', 'classifai' ) }
								</span>
							</Button>
						</div>

						<div
							style={ {
								borderTop: '1px solid #e0e0e0',
								paddingTop: '8px',
							} }
						>
							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'summary' ) }
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<Icon icon={ paragraph } />
								<span style={ { marginLeft: '8px' } }>
									{ __( 'Summary', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'key-points' ) }
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<Icon icon={ grid } />
								<span style={ { marginLeft: '8px' } }>
									{ __( 'Key Points', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'table' ) }
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<Icon icon={ table } />
								<span style={ { marginLeft: '8px' } }>
									{ __( 'Table', 'classifai' ) }
								</span>
							</Button>

							<Button
								className="classifai-action-button"
								onClick={ () => onOptionSelect( 'list' ) }
								style={ {
									width: '100%',
									justifyContent: 'flex-start',
									textAlign: 'left',
									padding: '8px 12px',
									border: 'none',
								} }
							>
								<Icon icon={ formatListBullets } />
								<span style={ { marginLeft: '8px' } }>
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
