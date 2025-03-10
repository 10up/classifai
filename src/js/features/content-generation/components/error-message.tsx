import React from 'react';
import { __ } from '@wordpress/i18n';

/**
 * Props for the ErrorMessage component
 */
export interface ErrorMessageProps {
	error: string | false;
}

/**
 * ErrorMessage component
 *
 * Displays error messages in the chat UI
 *
 * @param {ErrorMessageProps} props Component props
 * @return {React.ReactElement|null} Error message or null if no error
 */
export const ErrorMessage: React.FC< ErrorMessageProps > = ( { error } ) => {
	if ( ! error ) {
		return null;
	}

	return (
		<div
			style={ {
				color: '#cc1818',
				marginBottom: '10px',
			} }
		>
			{ __( 'Error', 'classifai' ) }: { error }
		</div>
	);
};
