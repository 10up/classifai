/* global lodash */
const { select, useSelect, dispatch, subscribe } = wp.data;
const { createBlock } = wp.blocks;
const { apiFetch } = wp;
const { find, debounce, filter } = lodash;
const { addFilter } = wp.hooks;
const { createHigherOrderComponent } = wp.compose;
const { BlockControls } = wp.blockEditor; // eslint-disable-line no-unused-vars
const { Button, Modal, Flex, FlexItem, ToolbarGroup, ToolbarButton } = wp.components; // eslint-disable-line no-unused-vars
const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { useState, Fragment } = wp.element; // eslint-disable-line no-unused-vars

import classnames from 'classnames/dedupe';

/**
 * Icon for insert button.
 */
const insertIcon = <span className="dashicons dashicons-editor-paste-text"></span>;

/**
 * Get image scanned text using media api.
 *
 * @param {int} imageId - Image ID.
*/
const getImageOcrScannedText = async ( imageId ) => {
	const media = await apiFetch( { path: `/wp/v2/media/${imageId}` } );

	if (
		! Object.prototype.hasOwnProperty.call( media, 'classifai_has_ocr' )
		|| ! media.classifai_has_ocr
	) {
		return false;
	}

	if (
		! Object.prototype.hasOwnProperty.call( media, 'description' )
		|| ! Object.prototype.hasOwnProperty.call( media.description, 'rendered' )
		|| ! media.description.rendered
	) {
		return false;
	}

	return media.description.rendered
		.replace( /(<([^>]+)>)/gi, '' )
		.replace( /(\r\n|\n|\r)/gm,'' )
		.trim();
};

/**
 * Insert scanned text as a paragraph block to the editor.
 *
 * @param {int} clientId - Client ID of image block.
 * @param {int} imageId - Image ID.
 * @param {string} scannedText - Text to insert to editor.
*/
const insertOcrScannedText = async ( clientId, imageId, scannedText = '' ) => {
	const { getBlockIndex } = select( 'core/block-editor' );

	if( ! scannedText ) {
		scannedText = await getImageOcrScannedText( imageId );
	}

	if( ! scannedText ) {
		return;
	}

	const groupBlock = createBlock( 'core/group', {
		anchor: `classifai-ocr-${imageId}`,
		className: 'is-style-classifai-ocr-text',
	} );

	const textBlock = createBlock( 'core/paragraph', {
		content: scannedText,
	} );

	dispatch( 'core/block-editor' ).insertBlock( groupBlock, getBlockIndex( clientId ) + 1 );
	dispatch( 'core/block-editor' ).insertBlock( textBlock, 0, groupBlock.clientId );
};

/**
 * Check if current post has OCR block.
 *
 * @param {int} imageId - Image ID.
 * @param {array} blocks - Current blocks of current post.
 */
const hasOcrBlock = ( imageId, blocks = [] ) => {
	if ( 0 === blocks.length ) {
		const { getBlocks } = select( 'core/block-editor' );
		blocks = getBlocks();
	}
	return !! find( blocks, block => block.attributes.anchor === `classifai-ocr-${imageId}` );
};

/**
 * An Modal allows user to insert scanned text to block if detected.
 */
const imageOcrModal = () => {
	const [ isOpen, setOpen ] = useState( false );
	const [ imageId, setImageId ] = useState( 0 );
	const [ clientId, setClientId ] = useState( 0 );
	const [ ocrScannedText, setOcrScannedText ] = useState( '' );
	const openModal = () => setOpen( true ); // eslint-disable-line require-jsdoc
	const closeModal = () => setOpen( false ); // eslint-disable-line require-jsdoc
	let currentBlocks;

	useSelect( debounce( async ( select ) => {
		const { getSelectedBlock, getBlocks } = select( 'core/block-editor' );
		const { updateBlockAttributes } = dispatch( 'core/block-editor' );
		const newBlocks = getBlocks();
		const prevBlocks = currentBlocks;
		currentBlocks = newBlocks;

		const currentBlock = getSelectedBlock();

		if ( ! currentBlock || 'core/image' !== currentBlock.name ) {
			return;
		}

		if ( ! currentBlock.attributes.id ) {
			return;
		}

		const prevBlock = find( prevBlocks, block => block.clientId === currentBlock.clientId );

		if ( ! prevBlock || prevBlock.attributes.id === currentBlock.attributes.id ) {
			return;
		}

		setClientId( currentBlock.clientId );
		setImageId( currentBlock.attributes.id );

		const _ocrText = await getImageOcrScannedText( currentBlock.attributes.id );

		if ( ! _ocrText ) {
			return;
		}

		setOcrScannedText( _ocrText );

		updateBlockAttributes( currentBlock.clientId, {
			ocrScannedText: _ocrText,
		} );

		if ( ! hasOcrBlock( currentBlock.attributes.id, newBlocks ) ) {
			openModal();
		}
	}, 10 ) );

	return isOpen && <Modal title={__( 'ClassifAI detected text in your image', 'classifai' )}>
		<p>{__( 'Would you like you insert the scanned text under this image block? This enhances search indexing and accessibility for readers.', 'classifai' )}</p>
		<Flex align='flex-end' justify='flex-end'>
			<FlexItem>
				<Button isPrimary onClick={() => {
					insertOcrScannedText( clientId, imageId, ocrScannedText );
					return closeModal();
				}}>
					{__( 'Insert text', 'classifai' )}
				</Button>
			</FlexItem>
			<FlexItem>
				<Button isSecondary onClick={ closeModal }>
					{__( 'Dismiss', 'classifai' )}
				</Button>
			</FlexItem>
		</Flex>
	</Modal>;
};

registerPlugin( 'tenup-classifai-ocr-modal', {
	render: imageOcrModal,
} );

/**
 * Add insert button to toolbar.
*/
const imageOcrControl = createHigherOrderComponent( ( BlockEdit ) => { // eslint-disable-line no-unused-vars
	return ( props ) => {
		const { attributes, clientId, isSelected, name } = props;

		if ( ! isSelected || 'core/image' != name || ! attributes.ocrScannedText ) {
			return <BlockEdit {...props} />;
		}

		return (
			<Fragment>
				<BlockEdit {...props} />
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							label={__( 'Insert scanned text into content', 'classifai' )}
							icon={insertIcon}
							onClick={() => insertOcrScannedText( clientId, attributes.id, attributes.ocrScannedText )}
							disabled={hasOcrBlock( attributes.id )}
						/>
					</ToolbarGroup>
				</BlockControls>
			</Fragment>
		);
	};
}, 'imageOcrControl' );

addFilter(
	'editor.BlockEdit',
	'classifai/image-processing-ocr',
	imageOcrControl
);

/**
 * Add custom attribute for OCR to image block.
 *
 * @param {object} settings - Block settings.
 * @param {string} name - Block name.
*/
const modifyImageAttributes = ( settings, name ) => {
	if ( 'core/image' !== name ) {
		return settings;
	}

	if ( settings.attributes ) {

		settings.attributes.ocrScannedText = {
			type: 'string',
			default: ''
		};
	}
	return settings;
};

addFilter(
	'blocks.registerBlockType',
	'classifai/image-processing-ocr',
	modifyImageAttributes
);

wp.blocks.registerBlockStyle( 'core/group', {
	name: 'classifai-ocr-text',
	label: __( 'Scanned Text from Image', 'classifai' ),
} );

/**
 * Hold contents of previously selected block to avoid firing too often.
 */
let previousSelectedBlock;
let removeClasses = false;

subscribe( debounce( () => {
	const blockEditor = select( 'core/block-editor' );
	const selectedBlock = blockEditor.getSelectedBlock();
	const blocks = blockEditor.getBlocks();

	// If the current selected block is the same as previously, return early
	if ( selectedBlock === previousSelectedBlock ) {
		return;
	}

	// If no selected block, return early and if needed, remove classes
	if ( null === selectedBlock ) {
		if ( removeClasses ) {
			removeRelatedClass( blocks );
			removeClasses = false;
		}

		return;
	}

	// If we have a selected block but our remove flag is set, remove classes first
	if ( removeClasses ) {
		removeRelatedClass( blocks );
		removeClasses = false;
	}

	previousSelectedBlock = selectedBlock;

	if ( 'core/image' === selectedBlock.name ) {
		const ocrBlock = find( blocks, block => block.attributes.anchor === `classifai-ocr-${selectedBlock.attributes.id}` );

		if ( undefined !== ocrBlock ) {
			dispatch( 'core/block-editor' ).updateBlockAttributes( ocrBlock.clientId, { className: classnames( ocrBlock.attributes.className, 'classifai-ocr-related-block' ) } );
			dispatch( 'core/block-editor' ).updateBlockAttributes( selectedBlock.clientId, { className: classnames( selectedBlock.attributes.className, 'classifai-ocr-related-block' ) } );
			removeClasses = true;
		}
	} else {
		const rootBlock = blockEditor.getBlock( blockEditor.getBlockHierarchyRootClientId( selectedBlock.clientId ) );

		if ( 'core/group' === rootBlock.name ) {
			let imageId = /classifai-ocr-([0-9]+)/.exec( rootBlock.attributes.anchor );

			if ( null !== imageId ) {
				[ , imageId ] = imageId;

				const imageBlock = find( blocks, block => block.attributes.id == imageId );

				if ( undefined !== imageBlock ) {
					dispatch( 'core/block-editor' ).updateBlockAttributes( imageBlock.clientId, { className: classnames( imageBlock.attributes.className, 'classifai-ocr-related-block' ) } );
					removeClasses = true;
				}
			}
		}
	}
}, 300 ) );

/**
 * Remove the ocr-related class
 *
 * @param {object} blocks Block data.
 */
const removeRelatedClass = ( blocks ) => {
	if ( ! blocks ) {
		return;
	}

	const blocksToEdit = filter( blocks, block => undefined !== block.attributes.className && block.attributes.className.includes( 'classifai-ocr-related-block' ) );

	blocksToEdit.forEach( ( block ) => {
		dispatch( 'core/block-editor' ).updateBlockAttributes( block.clientId, { className: classnames( block.attributes.className, { 'classifai-ocr-related-block': false } ) } );
	} );
};
