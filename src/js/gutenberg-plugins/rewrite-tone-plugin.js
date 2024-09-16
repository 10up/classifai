/**
 * External dependencies.
 */
import {
	store as blockEditorStore,
	BlockEditorProvider,
	BlockList,
} from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Modal } from '@wordpress/components';
import {
	useState,
	useEffect,
	useRef,
	createPortal,
	render,
} from '@wordpress/element';
import { getBlockContent, createBlock } from '@wordpress/blocks';
import { create, toHTMLString } from '@wordpress/rich-text';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { ClassifaiEditorSettingsPanel } from '../gutenberg-plugin';

const InjectIframeStyles = ( { children } ) => {
	// Stores the Gutenberg editor canvas iframe.
	const [ iframeCanvas, setIframeCanvas ] = useState( null );

	// Reference to the iframe in which we show blocks for preview.
	const iframeRef = useRef( null );

	useEffect( () => {
		// We wait for the editor canvas to load.
		( async () => {
			let __iframeCanvas;

			await new Promise( ( resolve ) => {
				const intervalId = setInterval( () => {
					__iframeCanvas =
						document.getElementsByName( 'editor-canvas' );
					if ( __iframeCanvas.length > 0 ) {
						__iframeCanvas = __iframeCanvas[ 0 ];
						clearInterval( intervalId );
						resolve();
					}
				}, 100 );
			} );

			setIframeCanvas( __iframeCanvas );
		} )();
	}, [] );

	useEffect( () => {
		if ( ! iframeCanvas || ! iframeRef.current ) {
			return;
		}

		// Get the newly created iframe's document.
		const iframeDocument =
			iframeRef.current.contentDocument ||
			iframeRef.current.contentWindow.document;

		// Copy the styles from the existing iframe (editor canvas).
		const editorIframeDocument =
			iframeCanvas.contentDocument || iframeCanvas.contentWindow.document;
		const iframeStyles = editorIframeDocument.querySelectorAll(
			'link[rel="stylesheet"], style'
		);

		// Append styles (external & internal) to the new iframe's body.
		iframeStyles.forEach( ( style ) => {
			if ( style.tagName === 'LINK' ) {
				iframeDocument.head.appendChild( style.cloneNode( true ) );
			} else if ( style.tagName === 'STYLE' ) {
				const clonedStyle = document.createElement( 'style' );
				clonedStyle.textContent = style.textContent;
				iframeDocument.head.appendChild( clonedStyle );
			}
		} );

		// Append a container to the iframe body for injecting the children.
		const iframeContainer = iframeDocument.createElement( 'div' );
		iframeDocument.body.appendChild( iframeContainer );

		// setTimeout( () => {
		// 	iframeContainer.classList.add( 'block-editor-iframe__body', 'editor-styles-wrapper', 'post-type-post', 'admin-color-fresh', 'wp-embed-responsive' );
		// 	iframeContainer.querySelector( '.is-root-container' ).classList.add( 'is-desktop-preview', 'is-layout-constrained', 'wp-block-post-content-is-layout-constrained', 'has-global-padding', 'alignfull', 'wp-block-post-content', 'block-editor-block-list__layout' );

		// 	console.log(
		// 		[ 'block-editor-iframe__body', 'editor-styles-wrapper', 'post-type-post', 'admin-color-fresh', 'wp-embed-responsive' ].join( ' ' ),
		// 		[ 'is-root-container', 'is-desktop-preview', 'is-layout-constrained', 'wp-block-post-content-is-layout-constrained', 'has-global-padding', 'alignfull', 'wp-block-post-content', 'block-editor-block-list__layout' ].join( ' ' )
		// 	)
		// }, 3500 );

		// Use React Portal to render the children into the iframe container.
		// TODO: Might need to replace with `createPortal` due to React 18.
		const portal = createPortal( children, iframeContainer );
		render( portal, iframeContainer );
	}, [ iframeCanvas ] );

	if ( ! iframeCanvas ) {
		return null;
	}

	return (
		<>
			<div style={ { height: '100vh' } }>
				<iframe
					ref={ iframeRef }
					style={ { width: '100%', height: '100%', border: 'none' } }
				></iframe>
			</div>
		</>
	);
};

const RewriteTonePlugin = () => {
	const allowedTextBlocks = [
		'core/paragraph',
		'core/heading',
		'core/list-item',
	];

	const apiUrl = `${ wpApiSettings.root }classifai/v1/rewrite-tone`;

	// Stores ChatGPT response.
	const [ response, setResponse ] = useState( null );

	// Flag indicating if a rewrite is in progress.
	const [ isRewriteInProgress, setIsRewriteInProgress ] = useState( false );

	// Stores all the editor blocks (modified and unmodified) that are created for preview.
	const [ previewBlocks, setPreviewBlocks ] = useState( [] );

	// Stores the subset of editor blocks that have undergone tone rewriting.
	const [ modifiedBlocks, setModifiedBlocks ] = useState( [] );

	// Flag indicating if the previewer modal is open.
	const [ isPopupVisible, setIsPopupVisible ] = useState( false );

	// Holds a reference to the original, unmodified editor blocks.
	const blocksBackup = useRef( null );

	// We use this to replace blocks if the user is happy with the result.
	const { replaceBlock } = useDispatch( blockEditorStore );

	/**
	 * Replaces subset of blocks in the copy of the editor's original blocks with
	 * modified blocks and returns a new array.
	 *
	 * Suppose the editor originally has 6 blocks and blocks 3 & 4 have undergone tone
	 * rewriting which returns blocks 3' and 4'. This function returns 1-2-3'-4'-5-6.
	 *
	 * @param {Array} originalBlocks  Array of original, unmodified editor blocks.
	 * @param {Array} rewrittenBlocks Subset of editor blocks which have undergone tone rewriting.
	 * @return {Array} Array of blocks that include original and modified blocks.
	 */
	function updateBlocksWithModified( originalBlocks, rewrittenBlocks ) {
		const updateBlock = ( blocks ) => {
			return blocks.map( ( block ) => {
				const modified = rewrittenBlocks.find(
					( modifiedBlock ) =>
						modifiedBlock.clientId === block.clientId
				);

				if ( modified ) {
					return modified.blocks[ 0 ];
				}

				return {
					...block,
					innerBlocks: block.innerBlocks
						? updateBlock( block.innerBlocks )
						: [],
				};
			} );
		};

		return updateBlock( originalBlocks );
	}

	/**
	 * Removes the <!-- --> delimiters from the content.
	 *
	 * @param {Array} blocks Array of { clientId, content } objects.
	 * @return {Array} Array of objects with content without delimiters.
	 */
	const removeDelimiters = ( blocks ) =>
		blocks.map( ( { clientId, content } ) => {
			return {
				clientId,
				content: content.replace( /<!--[\s\S]*?-->/g, '' ),
			};
		} );

	/**
	 * Returns a transformer function depending on the transforms passed as args.
	 *
	 * @param {...any} fns Array of functions that forms the pipeline.
	 * @return {Function} The transformer function.
	 */
	const blocksTransformerPipeline =
		( ...fns ) =>
		( value ) =>
			fns.reduce( ( acc, fn ) => fn( acc ), value );

	// `selectedBlocks` contains array of blocks that are selected in the editor.
	// `postId` is the current post ID.
	const { selectedBlocks, postId } = useSelect( ( select ) => {
		const selectedBlock = select( blockEditorStore ).getSelectedBlock();
		const multiSelectedBlocks =
			select( blockEditorStore ).getMultiSelectedBlocks();
		const __selectedBlocks = selectedBlock
			? [ selectedBlock ]
			: multiSelectedBlocks;

		const getSelectedRootBlocks = () => {
			const selectedRootBlocks = __selectedBlocks.map(
				( { clientId } ) => {
					return select( blockEditorStore ).getBlock( clientId );
				}
			);

			return [
				...new Map(
					selectedRootBlocks.map( ( item ) => [
						item.clientId,
						item,
					] )
				).values(),
			];
		};

		const flattenAllowedBlocks = ( blocks ) =>
			blocks.reduce(
				( acc, block ) => [
					...acc,
					...( allowedTextBlocks.includes( block.name )
						? [ block ]
						: [] ),
					...( block.innerBlocks
						? flattenAllowedBlocks( block.innerBlocks )
						: [] ),
				],
				[]
			);

		/**
		 * Returns { clientId, content } of a block.
		 *
		 * @param {Array} blocks Array of blocks.
		 * @return {Array} Array of { clientId, content } objects extracted from `block`.
		 */
		const gatherPostData = ( blocks ) =>
			blocks.map( ( block ) => ( {
				clientId: block.clientId,
				content: getBlockContent( block ),
			} ) );

		const blocksTransformer = blocksTransformerPipeline(
			flattenAllowedBlocks,
			gatherPostData,
			removeDelimiters
		);

		return {
			postId: select( editorStore ).getCurrentPostId(),
			selectedBlocks: blocksTransformer( getSelectedRootBlocks() ),
		};
	} );

	/**
	 * Performs rewrite when triggered by the user on Button click.
	 *
	 * @return {void}
	 */
	async function rewriteTone() {
		try {
			// We backup the original blocks.
			blocksBackup.current = wp.data
				.select( blockEditorStore )
				.getBlocks();

			setIsPopupVisible( false );
			setIsRewriteInProgress( true );
			setPreviewBlocks( [] );

			let __response = await fetch( apiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify( {
					id: postId,
					content: selectedBlocks,
				} ),
			} );

			setIsRewriteInProgress( false );

			if ( ! __response.ok ) {
				return;
			}

			__response = await __response.json();
			setResponse( JSON.parse( __response ) );
		} catch ( e ) {
			setIsRewriteInProgress( false );
		}
	}

	/**
	 * Applies the result to the editor canvas when the user
	 * accepts it.
	 */
	const applyResult = () => {
		modifiedBlocks.forEach( ( { clientId, blocks } ) => {
			replaceBlock( clientId, blocks );
		} );

		setIsPopupVisible( false );
	};

	useEffect( () => {
		if ( ! Array.isArray( response ) ) {
			return;
		}

		const __modifiedBlocks = response.map( ( { clientId, content } ) => {
			const currentBlock = wp.data
				.select( blockEditorStore )
				.getBlock( clientId );

			// We apply the original block attributes to the newly created.
			currentBlock.attributes = wp.data
				.select( blockEditorStore )
				.getBlockAttributes( clientId );

			// Generating and applying rich-text content to the new block.
			const richText = create( { html: content } );
			const blockContent = toHTMLString( { value: richText } );

			const newItemListBlock = createBlock( currentBlock.name, {
				content: blockContent,
			} );

			return {
				clientId,
				blocks: [ newItemListBlock ],
			};
		} );

		const __previewBlocks = updateBlocksWithModified(
			blocksBackup.current,
			__modifiedBlocks
		);

		setPreviewBlocks( __previewBlocks );
		setModifiedBlocks( __modifiedBlocks );
		setIsPopupVisible( true );
	}, [ response ] );

	return (
		<ClassifaiEditorSettingsPanel>
			<Button
				variant="secondary"
				onClick={ rewriteTone }
				isBusy={ isRewriteInProgress }
			>
				{ __( 'Rewrite tone', 'classifai' ) }
			</Button>
			{ isPopupVisible && (
				<Modal
					isFullScreen={ true }
					onRequestClose={ () => setIsPopupVisible( false ) }
				>
					<InjectIframeStyles>
						<BlockEditorProvider
							value={ previewBlocks }
							settings={ {
								...wp.data
									.select( 'core/block-editor' )
									.getSettings(),
								inserter: false,
								templateLock: 'all',
							} }
						>
							<BlockList />
						</BlockEditorProvider>
						<div>
							<Button variant="secondary" onClick={ applyResult }>
								{ __( 'Apply this result', 'classifai' ) }
							</Button>
							<Button variant="link" onClick={ rewriteTone }>
								{ __( 'Regenerate', 'classifai' ) }
							</Button>
						</div>
					</InjectIframeStyles>
				</Modal>
			) }
		</ClassifaiEditorSettingsPanel>
	);
};

registerPlugin( 'classifai-rewrite-tone-plugin', {
	render: RewriteTonePlugin,
} );
