/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LoadingDots } from './loading-dots';

// Define style objects outside of JSX
const containerStyles: CSSProperties = {
	display: 'flex',
	justifyContent: 'flex-start',
	marginBottom: '8px',
};

const messageStyles: CSSProperties = {
	backgroundColor: '#f0f0f0',
	padding: '10px 14px',
	borderRadius: '18px 18px 18px 0',
	color: '#666',
	fontStyle: 'italic',
	display: 'flex',
	gap: '4px',
	alignItems: 'flex-end',
};

/**
 * LoadingResponse component
 *
 * Displays a loading indicator while waiting for the AI response
 *
 * @return {React.ReactElement} Loading response indicator
 */
export const LoadingResponse: React.FC = () => {
	return (
		<div style={ containerStyles }>
			<div style={ messageStyles }>
				{ __( 'Waiting for response', 'classifai' ) }
				<LoadingDots />
			</div>
		</div>
	);
};
