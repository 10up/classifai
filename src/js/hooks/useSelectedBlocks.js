import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Returns array of block objects of the blocks that are selected.
 *
 * @return {Array} Array of block objects.
 */
export const useSelectedBlocks = () => {
	return useSelect( ( select ) => {
		const selectedBlock = select( blockEditorStore ).getSelectedBlock();
		const multiSelectedBlocks =
			select( blockEditorStore ).getMultiSelectedBlocks();
		return selectedBlock ? [ selectedBlock ] : multiSelectedBlocks;
	} );
};
