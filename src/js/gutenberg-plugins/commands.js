import { useCommand } from '@wordpress/commands';
import { settings } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Any general ClassifAI commands can go here.
 *
 * For commands specific to a certain feature,
 * those should probably be placed with the
 * rest of the functionality for that feature.
 */
const Commands = () => {
	useCommand( {
		name: 'classifai/settings',
		label: __( 'ClassifAI settings', 'classifai' ),
		icon: settings,
		callback: () => {
			document.location.href = 'tools.php?page=classifai';
		},
	} );

	return null;
};

registerPlugin( 'classifai-commands', { render: Commands } );
