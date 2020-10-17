const { subscribe, useSelect, select, dispatch } = wp.data;
const { createBlock } = wp.blocks;
const apiFetch = wp.apiFetch;
const { countBy } = lodash;

const { getBlocks, getSelectedBlock, getBlockIndex } = select( 'core/block-editor');

let currentBlocksState = getBlocks();

subscribe( async () => {
	// we are calling the getBlocks selector function to subscribe to all
	// the changes in the block list
	const newBlocksState = getBlocks();

	// const count = countBy(newBlocksState, 'name');

	if ( currentBlocksState.length === newBlocksState.length ) {
		return;
	}

	currentBlocksState = newBlocksState;

	// when a new block gets inserted it automatically gets selected
	// also when an image gets selected the image block is the currently
	// selected block in the editor
	const currentBlock = getSelectedBlock();

	// we only whant to do anything if the block is an image block
	if ( ! currentBlock || 'core/image' !== currentBlock.name ) {
		return
	}


	// we can get access to all the attributes of the image block
	const imageId = currentBlock.attributes.id;

	if ( ! imageId ) {
		return;
	}

	const imageBlockIndex = getBlockIndex( currentBlock.clientId );
	
	const media = await apiFetch( { path: `/wp/v2/media/${imageId}` } );

	if (
		! media.hasOwnProperty('caption')
		|| ! media.caption.hasOwnProperty('rendered')
		|| ! media.caption.rendered
	) {
		return;
	}

	const newBlock = createBlock( 'core/paragraph', {
		content: media.caption.rendered,
	} );
	dispatch( 'core/block-editor' ).insertBlock(
		newBlock,
		imageBlockIndex + 1
	);
});