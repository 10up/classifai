/**
 * Recommended Content block
 * recommended-content-block
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import block from './block.json';
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

/* Uncomment for CSS overrides in the admin */
// import './index.css';

/**
 * Register block
 */
registerBlockType( block.name, {
	title: __( 'Recommended Content', 'classifai' ),
	description: __(
		'Display content recommended by Azure Personalizer',
		'classifai'
	),
	edit,
	save,
	icon,
} );
