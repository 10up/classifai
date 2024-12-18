/* eslint-disable @wordpress/no-unsafe-wp-apis */
/* eslint-disable no-shadow */
/**
 * External Dependencies.
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	store as blockEditorStore,
	BlockControls,
} from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import {
	select,
	dispatch,
	useSelect,
	createReduxStore,
	register,
} from '@wordpress/data';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __unstableStripHTML as stripHTML } from '@wordpress/dom';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Modal, ToolbarDropdownMenu, Button } from '@wordpress/components';
import {
	count as getWordCount,
	count as getCharacterCount,
} from '@wordpress/wordcount';
import { __, _nx } from '@wordpress/i18n';

/**
 * Internal Dependencies.
 */
import { DisableFeatureButton } from '../../components';
import { browserAITextGeneration } from '../../helpers';
import './index.scss';

const aiIconSvg = (
	<svg
		width="20"
		height="15"
		viewBox="0 0 61 46"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M3.51922 0C1.57575 0 0 1.5842 0 3.53846V42.4615C0 44.4158 1.57575 46 3.51922 46H57.4808C59.4243 46 61 44.4158 61 42.4615V3.53846C61 1.5842 59.4243 0 57.4808 0H3.51922ZM16.709 8.13836H21.4478L33.58 39.5542H27.524L24.0318 30.5144H13.9699L10.5169 39.5542H4.55669L16.709 8.13836ZM19.0894 16.7007C18.9846 17.041 18.878 17.3735 18.7702 17.698L18.7582 17.7344L15.9976 25.1398H22.1464L19.4013 17.6851L19.0894 16.7007ZM40.3164 8.13836H52.9056V12.1528L49.4929 12.9715V34.6306L52.9056 35.41V39.4338H40.3164V35.41L43.7291 34.6306V12.9715L40.3164 12.1528V8.13836Z"
		/>
	</svg>
);

/**
 * Custom store to access common data in a block and a higher order
 * component created through filters.
 */
const DEFAULT_STATE = {
	clientId: '',
	resizingType: null,
	isResizing: false,
};

const resizeContentStore = createReduxStore( 'resize-content-store', {
	reducer( state = DEFAULT_STATE, action ) {
		switch ( action.type ) {
			case 'SET_CLIENT_ID':
				return {
					...state,
					clientId: action.clientId,
				};

			case 'SET_RESIZING_TYPE':
				return {
					...state,
					resizingType: action.resizingType,
				};

			case 'SET_IS_RESIZING':
				return {
					...state,
					isResizing: action.isResizing,
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
		setResizingType( resizingType ) {
			return {
				type: 'SET_RESIZING_TYPE',
				resizingType,
			};
		},
		setIsResizing( isResizing ) {
			return {
				type: 'SET_IS_RESIZING',
				isResizing,
			};
		},
	},
	selectors: {
		getClientId( state ) {
			return state.clientId;
		},
		getResizingType( state ) {
			return state.resizingType;
		},
		getIsResizing( state ) {
			return state.isResizing;
		},
	},
} );

register( resizeContentStore );

const ContentResizingPlugin = () => {
	// Holds the original text of the block being processed.
	const [ blockContentAsPlainText, setBlockContentAsPlainText ] =
		useState( '' );

	// Holds the currently selected block data.
	const [ selectedBlock, setSelectedBlock ] = useState( null );

	// Holds the GPT response array.
	const [ textArray, setTextArray ] = useState( [] );

	// Indicates if the modal window with the result is open/closed.
	const [ isModalOpen, setIsModalOpen ] = useState( false );

	// Modal title depending on resizing type.
	const [ modalTitle, setModalTitle ] = useState( '' );

	const { isMultiBlocksSelected, resizingType, isResizing } = useSelect(
		( __select ) => {
			return {
				isMultiBlocksSelected:
					__select( blockEditorStore ).hasMultiSelection(),
				resizingType: __select( resizeContentStore ).getResizingType(),
				isResizing: __select( resizeContentStore ).getIsResizing(),
			};
		}
	);

	// Sets required states before resizing content.
	useEffect( () => {
		if ( resizingType ) {
			( async () => {
				await resizeContent();
			} )();
		}
	}, [ resizingType ] );

	useEffect( () => {
		if ( 'grow' === resizingType ) {
			setModalTitle(
				_nx(
					'Expanded text suggestion',
					'Expanded text suggestions',
					textArray.length,
					'Modal title after expand content resizing.',
					'classifai'
				)
			);
		} else {
			setModalTitle(
				_nx(
					'Condensed text suggestion',
					'Condensed text suggestions',
					textArray.length,
					'Modal title after condense content resizing.',
					'classifai'
				)
			);
		}
	}, [ resizingType, textArray ] );

	// Triggers AJAX request to resize the content.
	useEffect( () => {
		if ( isResizing && selectedBlock ) {
			( async () => {
				const __textArray = await getResizedContent();
				setTextArray( __textArray );
				setIsModalOpen( true );
			} )();
		}
	}, [ isResizing, selectedBlock ] );

	// Resets to default states.
	function resetStates() {
		setSelectedBlock( null );
		setTextArray( [] );
		setIsModalOpen( false );
		dispatch( resizeContentStore ).setResizingType( null );
		setModalTitle( '' );
	}

	/**
	 * Refreshes results.
	 *
	 * @param {string} resizingType  Type of resizing. grow|shrink|null
	 * @param {Block}  selectedBlock The selected block.
	 */
	async function refreshResults( resizingType, selectedBlock ) {
		dispatch( resizeContentStore ).setResizingType( null );

		await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

		setSelectedBlock( selectedBlock );

		dispatch( resizeContentStore ).setResizingType( resizingType );
	}

	/**
	 * Triggered when either `Grow content` or `Shrink content` is clicked from
	 * the Block's "more options" menu.
	 *
	 * @return {void}
	 */
	async function resizeContent() {
		const block = select( blockEditorStore ).getSelectedBlock();
		const blockContent = block.attributes.content ?? '';

		setSelectedBlock( block );
		setBlockContentAsPlainText( toPlainText( blockContent ) );
		dispatch( resizeContentStore ).setIsResizing( true );
	}

	/**
	 * The AJAX callback that returns with an array of suggestion.
	 *
	 * @return {Array} Array of suggestions.
	 */
	async function getResizedContent() {
		let __textArray = [];
		const apiUrl = `${ wpApiSettings.root }classifai/v1/resize-content`;
		const postId = select( editorStore ).getCurrentPostId();
		const formData = new FormData();

		formData.append( 'id', postId );
		formData.append( 'content', blockContentAsPlainText );
		formData.append( 'resize_type', resizingType );

		dispatch( resizeContentStore ).setClientId( selectedBlock.clientId );

		const response = await fetch( apiUrl, {
			method: 'POST',
			body: formData,
			headers: new Headers( {
				'X-WP-Nonce': wpApiSettings.nonce,
			} ),
		} );

		if ( 200 === response.status ) {
			__textArray = await response.json();

			// Support calling a function from the response for browser AI.
			if (
				typeof __textArray === 'object' &&
				__textArray.hasOwnProperty( 'func' )
			) {
				const res = await browserAITextGeneration(
					__textArray.func,
					__textArray?.prompt,
					__textArray?.content
				);
				__textArray = [ res.trim() ];
			}
		} else {
			dispatch( resizeContentStore ).setIsResizing( false );
			dispatch( resizeContentStore ).setClientId( '' );
			resetStates();
		}

		dispatch( resizeContentStore ).setIsResizing( false );
		dispatch( resizeContentStore ).setClientId( '' );

		return __textArray;
	}

	/**
	 * Updates the text selection.
	 *
	 * @param {string} updateWith The content that will be used to replace the selection.
	 */
	async function updateContent( updateWith ) {
		const isDirty = await select( 'core/editor' ).isEditedPostDirty();
		const postId = select( 'core/editor' ).getCurrentPostId();
		const postType = select( 'core/editor' ).getCurrentPostType();

		dispatch( blockEditorStore ).updateBlockAttributes(
			selectedBlock.clientId,
			{
				content: updateWith,
			}
		);

		dispatch( blockEditorStore ).selectionChange(
			selectedBlock.clientId,
			'content',
			0,
			updateWith.length
		);
		resetStates();

		// If no edited values in post trigger save.
		if ( ! isDirty ) {
			await dispatch( 'core' ).saveEditedEntityRecord(
				'postType',
				postType,
				postId
			);
		}
	}

	// We don't want to use the reszing feature when multiple blocks are selected.
	// Nor do we want to use it when a block's content resizing is under process.
	if ( isMultiBlocksSelected || isResizing ) {
		return null;
	}

	// Result Modal JSX.
	const suggestionModal = ! isResizing && textArray.length && isModalOpen && (
		<Modal
			title={ modalTitle }
			isFullScreen={ false }
			className="classifai-content-resize__suggestion-modal"
			onRequestClose={ () => {
				setIsModalOpen( false );
				resetStates();
			} }
		>
			<div className="classifai-content-resize__result-wrapper">
				<table className="classifai-content-resize__result-table">
					<thead>
						<tr>
							<th>{ __( 'Suggestion', 'classifai' ) }</th>
							<th className="classifai-content-resize__stat-header">
								{ __( 'Stats', 'classifai' ) }
							</th>
							<th>{ __( 'Action', 'classifai' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ textArray.map( ( textItem, index ) => {
							const selectedTextWordCount = getWordCount(
								blockContentAsPlainText,
								'words'
							);
							const selectedTextCharCount = getCharacterCount(
								blockContentAsPlainText,
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
								<tr key={ index }>
									<td>{ textItem }</td>
									<td>
										<ResizeStat count={ wordDiff } />
										<ResizeStat
											count={ charDiff }
											countEntity="character"
										/>
									</td>
									<td>
										<Button
											text={ __(
												'Replace',
												'classifai'
											) }
											variant="secondary"
											onClick={ () =>
												updateContent( textItem )
											}
											tabIndex="0"
										/>
									</td>
								</tr>
							);
						} ) }
					</tbody>
				</table>
			</div>
			<br />
			<Button
				onClick={ () => refreshResults( resizingType, selectedBlock ) }
				variant="secondary"
			>
				{ __( 'Refresh results', 'classifai' ) }
			</Button>
			<DisableFeatureButton feature="feature_content_resizing" />
		</Modal>
	);

	return suggestionModal;
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

registerPlugin( 'classifai-plugin-content-resizing', {
	render: ContentResizingPlugin,
} );

const colorsArray = [ '#8c2525', '#ca4444', '#303030' ];

let timeoutId = 0;

function processAnimation( content = '', wrapperRef ) {
	if ( ! wrapperRef ) {
		return;
	}

	if ( ! select( resizeContentStore ).getIsResizing() ) {
		clearTimeout( timeoutId );
		return;
	}

	const charArray = content.split( ' ' );
	const randomWordIndexes = getRandomIndexesFromArray(
		charArray,
		charArray.length / 4
	);
	const formattedCharArray = charArray.map( ( char, index ) => {
		if ( randomWordIndexes.includes( index ) ) {
			const randomColorIndex = Math.floor( Math.random() * 5 );
			return `<span class="classifai-content-resize__blot" style="background-color: ${ colorsArray[ randomColorIndex ] }">${ char }</span>`;
		}

		return char;
	} );

	wrapperRef.current.innerHTML = formattedCharArray.join( ' ' );

	timeoutId = setTimeout( () => {
		requestAnimationFrame( () => processAnimation( content, wrapperRef ) );
	}, 1000 / 1.35 );
}

function getRandomIndexesFromArray( arr = [], maxIndexes = 10 ) {
	const indexes = Array.from( { length: arr.length }, ( _, index ) => index ); // Create an array of all indexes
	const randomIndexes = [];

	while ( randomIndexes.length < maxIndexes ) {
		const randomIndex = Math.floor( Math.random() * indexes.length );

		if ( ! randomIndexes.includes( randomIndex ) ) {
			randomIndexes.push( randomIndex );
		}
	}

	return randomIndexes;
}

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

		const mockWrapper = useRef();

		if ( currentClientId !== props.clientId ) {
			return <BlockEdit { ...props } />;
		}

		if ( 'core/paragraph' !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		const __plainTextContent = toPlainText( props.attributes.content );

		if ( select( resizeContentStore ).getIsResizing() ) {
			requestAnimationFrame( () =>
				processAnimation( __plainTextContent, mockWrapper )
			);
		}

		return (
			<>
				<div style={ { position: 'relative' } }>
					<div className="classifai-content-resize__overlay">
						<div className="classifai-content-resize__overlay-text">
							{ __( 'Processing data…', 'classifai' ) }
						</div>
					</div>
					<div
						id="classifai-content-resize__mock-content"
						ref={ mockWrapper }
					>
						{ __plainTextContent }
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

const withBlockControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { isMultiBlocksSelected, resizingType } = useSelect(
			( __select ) => {
				return {
					isMultiBlocksSelected:
						__select( blockEditorStore ).hasMultiSelection(),
					currentClientId:
						__select( resizeContentStore ).getClientId(),
					resizingType:
						__select( resizeContentStore ).getResizingType(),
				};
			}
		);

		if ( 'core/paragraph' !== props.name ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				{ ! resizingType && ! isMultiBlocksSelected ? (
					<BlockControls group="other">
						<ToolbarDropdownMenu
							icon={ aiIconSvg }
							className="classifai-resize-content-btn"
							controls={ [
								{
									title: __(
										'Expand this text',
										'classifai'
									),
									onClick: () => {
										dispatch(
											resizeContentStore
										).setResizingType( 'grow' );
									},
								},
								{
									title: __(
										'Condense this text',
										'classifai'
									),
									onClick: () => {
										dispatch(
											resizeContentStore
										).setResizingType( 'shrink' );
									},
								},
							] }
						/>
					</BlockControls>
				) : null }
				<BlockEdit { ...props } />
			</>
		);
	};
}, 'withBlockControl' );

wp.hooks.addFilter(
	'editor.BlockEdit',
	'resize-content/lock-block-editing',
	withBlockControls
);
