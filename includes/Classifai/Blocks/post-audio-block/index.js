import { registerBlockType } from '@wordpress/blocks';

import './store/register';

import Edit from './edit';
import metadata from './block.json';
import './index.scss';

registerBlockType( metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,
	/**
	 * @see ./save.js
	 */
	save: () => null,
} );
