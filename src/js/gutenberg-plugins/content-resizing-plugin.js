/* eslint-disable @wordpress/no-unsafe-wp-apis */
import { registerPlugin } from '@wordpress/plugins';
import { PluginBlockSettingsMenuItem } from '@wordpress/edit-post';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import {
	select,
	dispatch,
	useSelect,
	createReduxStore,
	register,
} from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { __unstableStripHTML as stripHTML } from '@wordpress/dom';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Modal, Spinner } from '@wordpress/components';
import {
	count as getWordCount,
	count as getCharacterCount,
} from '@wordpress/wordcount';
import { __ } from '@wordpress/i18n';

import '../../scss/content-resizing-plugin.scss';

/**
 * Custom store to access common data in a block and a higer order
 * component created through filters.
 */
const DEFAULT_STATE = {
	clientId: '',
};

const resizeContentStore = createReduxStore( 'resize-content-store', {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_CLIENT_ID':
				return {
					...state,
					clientId: action.clientId,
				};
		}

		return state;
	},
	actions: {
		setClientId( clientId ) {
			return {
				type: 'SET_CLIENT_ID',
				clientId,
			};
		},
	},
	selectors: {
		getClientId( state ) {
			return state.clientId;
		},
	},
} );

register( resizeContentStore );

const ContentResizingPlugin = () => {
	// Holds the original text of the block being procesed.
	const [ ogText, setOgText ] = useState( '' );

	// Holds the currently selected block data.
	const [ selectedBlock, setSelectedBlock ] = useState( null );

	// Holds the start selection index of the content selected in a block.
	// Defaults to `0` in cause the block is selected but no text is selected.
	const [ startIndex, setStartIndex ] = useState( 0 );

	// Holds the end selection index of the content selected in a block.
	// Defaults to `null` in cause the block is selected but no text is selected.
	const [ endIndex, setEndIndex ] = useState( null );

	// Holds the GPT response array.
	const [ textArray, setTextArray ] = useState( [] );

	// Holds the selected text content within the block.
	// If no text was selected, then this value is the same as `ogText`.
	const [ selectedText, setSelectedText ] = useState( '' );

	// Holds details of whether to `shrink` or `grow` the content.
	const [ resizeType, setResizeType ] = useState( 'grow' );

	// Indicates if content resixing is in progress.
	const [ isResizing, setIsResizing ] = useState( false );

	// Indicates if the modal window with the result is open/closed.
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	// Indicates if multiple blocks are selected.
	const { isMultiBlocksSelected } = useSelect( ( __select ) => {
		return {
			isMultiBlocksSelected:
				__select( blockEditorStore ).hasMultiSelection(),
		};
	} );

	// Resets to default states.
	function resetStates() {
		setSelectedBlock( null );
		setStartIndex( 0 );
		setEndIndex( null );
		setTextArray( [] );
		setSelectedText( '' );
		setIsModalOpen( false );
	}

	/**
	 * Triggered when either `Grow content` or `Shrink content` is clicked from
	 * the Block's "more options" menu.
	 *
	 * @param {string} __resizeType The type of resizing. `grow` or `shrink`.
	 * @return {void}
	 */
	async function resizeContent( __resizeType = 'grow' ) {
		const start = select( blockEditorStore ).getSelectionStart();
		const end = select( blockEditorStore ).getSelectionEnd();
		const block = select( blockEditorStore ).getSelectedBlock();

		const blockContent = block.attributes.content;
		const blockContentPlainText = toPlainText( blockContent );

		setResizeType( __resizeType );

		if ( 0 === end.offset - start.offset ) {
			setSelectedBlock( block );
			setStartIndex( 0 );
			setEndIndex( null );
			setSelectedText( blockContentPlainText );
			setOgText( blockContentPlainText );
			setIsResizing( true );
			return;
		}

		setSelectedBlock( block );
		setStartIndex( start.offset );
		setEndIndex( end.offset );
		setSelectedText(
			blockContentPlainText.substring( start.offset, end.offset )
		);
		setOgText( blockContentPlainText );
		setIsResizing( true );
	}

	// Triggers AJAX request to resize the content.
	useEffect( () => {
		if ( isResizing ) {
			( async () => {
				const __textArray = await getResizedContent();
				setTextArray( __textArray );
				setIsModalOpen( true );
			} )();
		}
	}, [ isResizing ] );

	/**
	 * The AJAX callback that returns with an array of suggestion.
	 *
	 * @return {Array} Array of suggestions.
	 */
	async function getResizedContent() {
		let __textArray = [];
		const apiUrl = `${ wpApiSettings.root }classifai/v1/openai/resize-content`;
		const postId = select( editorStore ).getCurrentPostId();
		const formData = new FormData();

		formData.append( 'id', postId );
		formData.append( 'content', selectedText );
		formData.append( 'resize_type', resizeType );

		dispatch( resizeContentStore ).setClientId( selectedBlock.clientId );

		const response = await fetch( apiUrl, {
			method: 'POST',
			body: formData,
		} );

		if ( 200 === response.status ) {
			__textArray = await response.json();
		} else {
			setIsResizing( false );
			dispatch( resizeContentStore ).setClientId( '' );
			resetStates();
		}

		setIsResizing( false );
		dispatch( resizeContentStore ).setClientId( '' );

		return __textArray;
	}

	/**
	 * Updates the text selection.
	 *
	 * @param {string} updateWith The content that will be used to replace the selection.
	 */
	function updateContent( updateWith ) {
		const fullBlockContent = toPlainText( ogText );
		const beforeReplaceable = fullBlockContent.substring( 0, startIndex );
		const afterReplaceable = fullBlockContent.substring( endIndex );
		const updatedContent =
			beforeReplaceable + updateWith + afterReplaceable;

		dispatch( blockEditorStore ).updateBlockAttributes(
			selectedBlock.clientId,
			{
				content: endIndex ? updatedContent : updateWith,
			}
		);

		dispatch( blockEditorStore ).selectionChange(
			selectedBlock.clientId,
			'content',
			startIndex,
			startIndex + updateWith.length
		);
		resetStates();
	}

	// We don't want to use the reszing feature when multiple blocks are selected.
	// Nor do we want to use it when a block's content resizing is under process.
	if ( isMultiBlocksSelected || isResizing ) {
		return null;
	}

	// Result Modal JSX.
	const suggestionModal = ! isResizing && textArray.length && isModalOpen && (
		<Modal
			title={ __( 'Suggestions', 'classifai' ) }
			isFullScreen={ false }
			className="classifai-content-resize__suggestion-modal"
			onRequestClose={ () => {
				setIsModalOpen( false );
				resetStates();
			} }
		>
			<p>{ __( 'Click on a row to apply:' ) }</p>
			<div className="classifai-content-resize__result-wrapper">
				<table className="classifai-content-resize__result-table">
					<thead>
						<th>{ __( 'Suggestion', 'classifai' ) }</th>
						<th className="classifai-content-resize__stat-header">
							{ __( 'Stats', 'classifai' ) }
						</th>
					</thead>
					<tbody>
						{ textArray.map( ( textItem, index ) => {
							const selectedTextWordCount = getWordCount(
								selectedText,
								'words'
							);
							const selectedTextCharCount = getCharacterCount(
								selectedText,
								'characters_including_spaces'
							);
							const suggestionWordCount = getWordCount(
								textItem,
								'words'
							);
							const suggestionCharCount = getCharacterCount(
								textItem,
								'characters_including_spaces'
							);

							const wordDiff =
								suggestionWordCount - selectedTextWordCount;
							const charDiff =
								suggestionCharCount - selectedTextCharCount;

							return (
								<tr
									key={ index }
									onClick={ () => updateContent( textItem ) }
								>
									<td>{ textItem }</td>
									<td>
										<ResizeStat count={ wordDiff } />
										<ResizeStat
											count={ charDiff }
											countEntity="character"
										/>
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
			</div>
		</Modal>
	);

	return (
		<>
			{ suggestionModal }
			<PluginBlockSettingsMenuItem
				allowedBlocks={ [ 'core/paragraph' ] }
				icon="smiley"
				label={ __( 'Grow content', 'classifai' ) }
				onClick={ () => resizeContent( 'grow' ) }
			/>
			<PluginBlockSettingsMenuItem
				allowedBlocks={ [ 'core/paragraph' ] }
				icon="smiley"
				label={ __( 'Shrink content', 'classifai' ) }
				onClick={ () => resizeContent( 'shrink' ) }
			/>
		</>
	);
};

const ResizeStat = ( { count = 0, countEntity = 'word' } ) => {
	if ( 0 === count ) {
		return (
			<div>
				{ 'word' === countEntity
					? __( 'No change in word count.', 'classifai' )
					: __( 'No change in character count.', 'classifai' ) }
			</div>
		);
	}

	if ( count < 0 ) {
		return (
			<div className="classifai-content-resize__shrink-stat">
				{ 'word' === countEntity ? (
					<>
						<strong>{ count }</strong>{ ' ' }
						{ __( 'words', 'classifai' ) }
					</>
				) : (
					<>
						<strong>{ count }</strong>{ ' ' }
						{ __( 'characters', 'classifai' ) }
					</>
				) }
			</div>
		);
	}

	return (
		<div className="classifai-content-resize__grow-stat">
			{ 'word' === countEntity ? (
				<>
					<strong>+{ count }</strong> { __( 'words', 'classifai' ) }
				</>
			) : (
				<>
					<strong>+{ count }</strong>{ ' ' }
					{ __( 'characters', 'classifai' ) }
				</>
			) }
		</div>
	);
};

/**
 * Strips-off all the HTML from a string and returns plain-text.
 *
 * @param {string} html Content with HTML.
 * @return {string} plain-text string.
 */
function toPlainText( html ) {
	// Manually handle BR tags as line breaks prior to `stripHTML` call.
	html = html.replace( /<br>/g, '\n' );

	const plainText = stripHTML( html ).trim();

	// Merge any consecutive line breaks.
	return plainText.replace( /\n\n+/g, '\n\n' );
}

registerPlugin( 'tenup-openai-expand-reduce-content', {
	render: ContentResizingPlugin,
} );

/**
 * Adds an overlay on the block under process.
 */
const withInspectorControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { currentClientId } = useSelect( ( __select ) => {
			return {
				currentClientId: __select( resizeContentStore ).getClientId(),
			};
		} );

		if ( currentClientId !== props.clientId ) {
			return <BlockEdit { ...props } />;
		}

		if ( 'core/paragraph' !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<div style={ { position: 'relative' } }>
					<div className="classifai-content-resize__overlay">
						<div>
							<Spinner />
							{ __( 'Processing content…' ) }
						</div>
					</div>
					<BlockEdit { ...props } />
				</div>
			</>
		);
	};
}, 'withInspectorControl' );

wp.hooks.addFilter(
	'editor.BlockEdit',
	'resize-content/lock-block-editing',
	withInspectorControls
);
