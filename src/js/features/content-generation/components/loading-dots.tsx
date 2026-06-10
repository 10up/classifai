/**
 * External dependencies
 */
import type { CSSProperties } from 'react';
import React from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { motion } from 'motion/react';

// Define style objects outside of JSX
const containerStyles: CSSProperties = {
	display: 'inline-flex',
	alignItems: 'center',
	fontSize: '20px',
};

const dotStyles: CSSProperties = {
	marginRight: '4px',
};

/**
 * LoadingDots component
 *
 * Animated dots to indicate loading state
 *
 * @return {React.ReactElement} Animated loading dots
 */
export const LoadingDots: React.FC = () => {
	return (
		<div style={ containerStyles }>
			<motion.span
				initial={ { opacity: 0.3 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0.3 } }
				transition={ {
					duration: 0.5,
					repeat: Infinity,
					repeatType: 'reverse',
				} }
				style={ dotStyles }
			>
				.
			</motion.span>
			<motion.span
				initial={ { opacity: 0.3 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0.3 } }
				transition={ {
					duration: 0.5,
					repeat: Infinity,
					repeatType: 'reverse',
					delay: 0.2,
				} }
				style={ dotStyles }
			>
				.
			</motion.span>
			<motion.span
				initial={ { opacity: 0.3 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0.3 } }
				transition={ {
					duration: 0.5,
					repeat: Infinity,
					repeatType: 'reverse',
					delay: 0.4,
				} }
			>
				.
			</motion.span>
			<span className="screen-reader-text">
				{ __( 'Loading', 'classifai' ) }
			</span>
		</div>
	);
};
