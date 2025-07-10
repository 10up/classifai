/**
 * External dependencies
 */
import classNames from 'classnames';
import { useState } from '@wordpress/element';

/**
 * Settings row component.
 *
 * @param {Object} props          All the props passed to this function.
 * @param {string} props.label    Settings label.
 * @param {Object} props.children The children of the component.
 */
export const SettingsRow = ( props ) => {
	const [ showTooltip, setShowTooltip ] = useState( false );

	return (
		<div className={ classNames( 'settings-row', props?.className ) }>
			<div className="settings-label">
				{ props.label }
				{ props.helperText && (
					<div 
						className="tooltip-container"
						onMouseEnter={ () => setShowTooltip( true ) }
						onMouseLeave={ () => setShowTooltip( false ) }
					>
						<span className="dashicons dashicons-info-outline helper-text-icon"></span>
						{ showTooltip && (
							<div className="settings-helper-text tooltip">
								<div dangerouslySetInnerHTML={ { __html: props.helperText } } />
							</div>
						) }
					</div>
				) }
			</div>
			<div className="settings-control">
				{ props.children }
				<div className="settings-description">
					{ props.description }
				</div>
			</div>
		</div>
	);
};
