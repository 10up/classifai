import React from 'react';
import { __ } from '@wordpress/i18n';
import { LoadingDots } from './loading-dots';

/**
 * LoadingResponse component
 *
 * Displays a loading indicator while waiting for the AI response
 *
 * @return {React.ReactElement} Loading response indicator
 */
export const LoadingResponse: React.FC = () => {
	return (
		<div
			style={ {
				display: 'flex',
				justifyContent: 'flex-start',
				marginBottom: '8px',
			} }
		>
			<div
				style={ {
					backgroundColor: '#f0f0f0',
					padding: '10px 14px',
					borderRadius: '18px 18px 18px 0',
					color: '#666',
					fontStyle: 'italic',
					display: 'flex',
					gap: '4px',
					alignItems: 'flex-end',
				} }
			>
				{ __( 'Waiting for response', 'classifai' ) }
				<LoadingDots />
			</div>
		</div>
	);
};
