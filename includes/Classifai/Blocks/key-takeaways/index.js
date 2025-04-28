/**
 * Key Takeaways block
 */

/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import block from './block.json';
import { ReactComponent as icon } from '../../../../assets/img/block-icon.svg';

/**
 * Register block
 */
registerBlockType( block, {
	edit,
	save,
	icon,
} );
