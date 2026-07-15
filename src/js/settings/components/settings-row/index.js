/**
 * External dependencies
 */
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { TooltipPopover } from '../../utils/utils';

/**
 * Settings row component.
 *
 * @param {Object} props          All the props passed to this function.
 * @param {string} props.label    Settings label.
 * @param {Object} props.children The children of the component.
 */
export const SettingsRow = ( props ) => {
	return (
		<div className={ clsx( 'settings-row', props?.className ) }>
			<div className="settings-label">
				{ props.label }
				{ props.helperText && (
					<TooltipPopover tooltipContent={ props.helperText } />
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
