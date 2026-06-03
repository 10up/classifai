/**
 * TaxonomyControls Component file.
 * This file inspired by Gutenberg TaxonomyControls component.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/query/edit/inspector-controls/taxonomy-controls.js
 */

/**
 * WordPress dependencies
 */
import { FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { getEntitiesInfo, useTaxonomies } from './utils';
import {
	useState,
	Fragment,
	useRef,
	useEffect,
	useCallback,
} from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

const termsPerPage = -1;

// Helper function to get the term id based on user input in terms `FormTokenField`.
const getTermIdByTermValue = ( termsMappedByName, termValue ) => {
	// First we check for exact match by `term.id` or case sensitive `term.name` match.
	const termId = termValue?.id || termsMappedByName[ termValue ]?.id;

	if ( termId ) {
		return termId;
	}

	/**
	 * Here we make an extra check for entered terms in a non case sensitive way,
	 * to match user expectations, due to `FormTokenField` behaviour that shows
	 * suggestions which are case insensitive.
	 *
	 * Although WP tries to discourage users to add terms with the same name (case insensitive),
	 * it's still possible if you manually change the name, as long as the terms have different slugs.
	 * In this edge case we always apply the first match from the terms list.
	 */
	const termValueLower = termValue.toLocaleLowerCase();

	for ( const term in termsMappedByName ) {
		if ( term.toLocaleLowerCase() === termValueLower ) {
			return termsMappedByName[ term ].id;
		}
	}
};

const TaxonomyControls = ( { onChange, query } ) => {
	const taxonomies = useTaxonomies( query.contentPostType );
	const featureTaxonomies = query.featureTaxonomies || [];
	const taxTermsAI = query.taxTermsAI || [];
	const [ newTermsInfo, setNewTermsInfo ] = useState( {} );

	// State for inline editing AI tokens
	const [ editingToken, setEditingToken ] = useState( null );
	const [ editValue, setEditValue ] = useState( '' );
	const tokenFieldRefs = useRef( {} );
	const inlineInputRef = useRef( null );

	const appendAIPrefix = ( terms, slug ) => {
		if (
			undefined !== terms &&
			undefined !== terms.mapById &&
			taxTermsAI[ slug ]
		) {
			Object.keys( terms.mapById ).forEach( ( term ) => {
				if ( taxTermsAI[ slug ].includes( terms.mapById[ term ].id ) ) {
					// do not add prefix if already added
					if ( terms.mapById[ term ].name.indexOf( '[AI]' ) === -1 ) {
						terms.mapById[ term ].name =
							'[AI] ' + terms.mapById[ term ].name;
					}
				}
			} );
		}

		return terms;
	};

	let taxonomiesInfo = useSelect( ( select ) => {
		const { getEntityRecords } = select( coreStore );
		const termsQuery = { per_page: termsPerPage };
		const _taxonomiesInfo = taxonomies?.map( ( { slug, name } ) => {
			const _terms = getEntityRecords( 'taxonomy', slug, termsQuery );
			let terms = getEntitiesInfo( _terms );

			// Append "[AI]" prefix
			if ( 'post_tag' === slug ) {
				slug = 'tags';
			}
			if ( 'category' === slug ) {
				slug = 'categories';
			}
			terms = appendAIPrefix( terms, slug );

			const termData = {
				slug,
				name,
				terms,
			};

			return termData;
		} );
		return _taxonomiesInfo;
	} );

	// Update the object with newly created terms.
	if ( Object.keys( newTermsInfo ).length > 0 ) {
		taxonomiesInfo = newTermsInfo;
	}

	/**
	 * Handle clicking on an [AI] token to edit it inline.
	 * Uses an overlay input positioned on top of the token for smooth editing.
	 *
	 * @param {string}  taxonomySlug The taxonomy slug.
	 * @param {number}  termId       The term ID being edited.
	 * @param {string}  termName     The term name (with [AI] prefix).
	 * @param {Element} tokenEl      The token DOM element.
	 */
	const handleAITokenClick = useCallback(
		( taxonomySlug, termId, termName, tokenEl ) => {
			// Extract the term name without the [AI] prefix
			const cleanName = termName.replace( /^\[AI\]\s*/, '' );

			// Get token position for overlay
			const tokenRect = tokenEl.getBoundingClientRect();
			const wrapperEl = tokenEl.closest(
				'.classifai-taxonomy-field-wrapper'
			);
			const wrapperRect = wrapperEl
				? wrapperEl.getBoundingClientRect()
				: { left: 0, top: 0 };

			// Store the editing state with position info
			setEditingToken( {
				taxonomySlug,
				termId,
				originalName: termName,
				cleanName,
				tokenEl,
				position: {
					top: tokenRect.top - wrapperRect.top,
					left: tokenRect.left - wrapperRect.left,
					width: tokenRect.width,
					height: tokenRect.height,
				},
			} );
			setEditValue( cleanName );

			// Add editing class to token for visual feedback
			tokenEl.classList.add( 'classifai-ai-token--editing' );
		},
		[]
	);

	/**
	 * Handle input change for inline editing.
	 *
	 * @param {Event} e Input event.
	 */
	const handleInlineInputChange = useCallback( ( e ) => {
		setEditValue( e.target.value );
	}, [] );

	// Use refs to store latest values for callbacks to avoid stale closures
	const editingTokenRef = useRef( editingToken );
	const editValueRef = useRef( editValue );

	useEffect( () => {
		editingTokenRef.current = editingToken;
	}, [ editingToken ] );

	useEffect( () => {
		editValueRef.current = editValue;
	}, [ editValue ] );

	/**
	 * Commit the inline edit - uses refs to get latest values.
	 */
	const commitInlineEdit = useCallback( () => {
		const currentEditingToken = editingTokenRef.current;
		const currentEditValue = editValueRef.current;

		if ( ! currentEditingToken ) {
			return;
		}

		const { taxonomySlug, termId, tokenEl } = currentEditingToken;
		const newValue = currentEditValue.trim();

		// Clean up
		if ( tokenEl ) {
			tokenEl.classList.remove( 'classifai-ai-token--editing' );
		}
		inlineInputRef.current = null;

		// If empty, just cancel
		if ( ! newValue ) {
			setEditingToken( null );
			setEditValue( '' );
			return;
		}

		// Apply the edit
		applyInlineEditRef.current?.( taxonomySlug, termId, newValue );
	}, [] );

	/**
	 * Cancel the inline edit.
	 */
	const cancelInlineEdit = useCallback( () => {
		const currentEditingToken = editingTokenRef.current;
		if ( currentEditingToken?.tokenEl ) {
			currentEditingToken.tokenEl.classList.remove(
				'classifai-ai-token--editing'
			);
		}
		inlineInputRef.current = null;
		setEditingToken( null );
		setEditValue( '' );
	}, [] );

	/**
	 * Handle keydown for inline editing.
	 *
	 * @param {KeyboardEvent} e Keyboard event.
	 */
	const handleInlineInputKeydown = useCallback(
		( e ) => {
			// Stop propagation to prevent FormTokenField from handling these keys
			e.stopPropagation();

			if ( e.key === 'Enter' ) {
				e.preventDefault();
				commitInlineEdit();
			} else if ( e.key === 'Escape' ) {
				e.preventDefault();
				cancelInlineEdit();
			}
		},
		[ commitInlineEdit, cancelInlineEdit ]
	);

	/**
	 * Handle blur for inline editing - commits the edit.
	 */
	const handleInlineInputBlur = useCallback( () => {
		// Small delay to allow click events on the input to process first
		setTimeout( () => {
			commitInlineEdit();
		}, 150 );
	}, [ commitInlineEdit ] );

	/**
	 * Apply the inline edit - removes the AI term and adds the new one.
	 * Stored in a ref to avoid stale closure issues.
	 */
	const applyInlineEditRef = useRef( null );

	applyInlineEditRef.current = async ( taxonomySlug, termId, newValue ) => {
		// Find the original term info
		const taxonomyInfo = taxonomiesInfo?.find(
			( { slug } ) => slug === taxonomySlug
		);
		const originalTerm = taxonomyInfo?.terms?.mapById?.[ termId ];
		const cleanOriginalName = originalTerm?.name?.replace(
			/^\[AI\]\s*/,
			''
		);

		// If value hasn't changed, just close
		if ( newValue === cleanOriginalName ) {
			setEditingToken( null );
			setEditValue( '' );
			return;
		}

		// Get current terms and remove the AI term
		const currentTerms = query.taxQuery[ taxonomySlug ] || [];
		const termValues = Object.values( currentTerms );
		const newTermValues = termValues.filter( ( id ) => id !== termId );

		// Check if the new term already exists
		const existingTerm = taxonomyInfo?.terms?.mapByName?.[ newValue ];

		if ( existingTerm ) {
			// Term exists, just add its ID
			newTermValues.push( existingTerm.id );

			const newTaxQuery = {
				...query.taxQuery,
				[ taxonomySlug ]: newTermValues.reduce(
					( acc, id, idx ) => ( { ...acc, [ idx ]: id } ),
					{}
				),
			};

			onChange( { taxQuery: newTaxQuery } );
		} else {
			// Term doesn't exist, create it via API
			const request = {
				path: `/wp/v2/${ taxonomySlug }`,
				data: { name: newValue, taxonomy: taxonomySlug },
				method: 'POST',
			};

			try {
				const response = await wp.apiRequest( request );
				if ( response && response.id ) {
					// Add the new term ID
					newTermValues.push( response.id );

					const newTaxQuery = {
						...query.taxQuery,
						[ taxonomySlug ]: newTermValues.reduce(
							( acc, id, idx ) => ( { ...acc, [ idx ]: id } ),
							{}
						),
					};

					// Update taxonomiesInfo with the new term
					const newTerm = {
						id: response.id,
						name: newValue,
						taxonomy: taxonomySlug,
						count: 0,
						description: '',
					};

					const updatedTaxonomiesInfo = taxonomiesInfo.map(
						( taxoInfo ) => {
							if ( taxoInfo.slug === taxonomySlug ) {
								const terms = {
									...taxoInfo.terms,
									entities: [
										...taxoInfo.terms.entities,
										newTerm,
									],
									mapById: {
										...taxoInfo.terms.mapById,
										[ newTerm.id ]: newTerm,
									},
									mapByName: {
										...taxoInfo.terms.mapByName,
										[ newTerm.name ]: newTerm,
									},
									names: [
										...taxoInfo.terms.names,
										newTerm.name,
									],
								};
								return { ...taxoInfo, terms };
							}
							return taxoInfo;
						}
					);

					setNewTermsInfo( updatedTaxonomiesInfo );
					onChange( { taxQuery: newTaxQuery } );
				}
			} catch ( error ) {
				// eslint-disable-next-line no-console
				console.error( 'Error creating term:', error );
			}
		}

		setEditingToken( null );
		setEditValue( '' );
	};

	/**
	 * Mark [AI] tokens as editable and attach direct click handlers.
	 * Uses MutationObserver to handle dynamically added tokens.
	 */
	useEffect( () => {
		const observers = [];

		const handleTokenTextClick = ( e, taxonomySlug ) => {
			// Don't process if already editing
			if ( editingTokenRef.current ) {
				return;
			}

			// Only handle clicks on the text part, not the X button
			const isRemoveButton = e.target.closest( 'button' );
			if ( isRemoveButton ) {
				return; // Let the remove button work normally
			}

			// Find the token and text from the click target
			const tokenTextEl = e.target.closest(
				'.components-form-token-field__token-text'
			);
			const tokenEl = e.target.closest(
				'.components-form-token-field__token'
			);

			if ( ! tokenTextEl || ! tokenEl ) {
				return;
			}

			const visibleTextEl = tokenTextEl.querySelector(
				'span[aria-hidden="true"]'
			);
			const tokenText = visibleTextEl
				? visibleTextEl.textContent
				: tokenTextEl.textContent || '';

			if ( ! tokenText.includes( '[AI]' ) ) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			// Find the term ID from the token
			const taxonomyInfo = taxonomiesInfo?.find(
				( { slug } ) => slug === taxonomySlug
			);
			if ( taxonomyInfo ) {
				const termEntry = Object.entries(
					taxonomyInfo.terms.mapById
				).find( ( [ , term ] ) => term.name === tokenText );

				if ( termEntry ) {
					handleAITokenClick(
						taxonomySlug,
						parseInt( termEntry[ 0 ], 10 ),
						tokenText,
						tokenEl
					);
				}
			}
		};

		const markEditableTokens = ( fieldRef ) => {
			if ( ! fieldRef ) {
				return;
			}

			// Find all tokens that contain [AI] text
			const tokens = fieldRef.querySelectorAll(
				'.components-form-token-field__token'
			);

			tokens.forEach( ( token ) => {
				const tokenTextEl = token.querySelector(
					'.components-form-token-field__token-text'
				);
				const visibleTextEl = tokenTextEl?.querySelector(
					'span[aria-hidden="true"]'
				);
				const tokenText = visibleTextEl
					? visibleTextEl.textContent
					: tokenTextEl?.textContent || '';

				if ( tokenText.includes( '[AI]' ) ) {
					// Add clickable class for styling
					token.classList.add( 'classifai-ai-token--editable' );

					// Add tooltip
					if ( tokenTextEl ) {
						tokenTextEl.title = __(
							'Click to edit this term',
							'classifai'
						);
					}
				}
			} );
		};

		// Set up observers and initial marking for each taxonomy field
		Object.keys( tokenFieldRefs.current ).forEach( ( taxonomySlug ) => {
			const fieldRef = tokenFieldRefs.current[ taxonomySlug ];
			if ( ! fieldRef ) {
				return;
			}

			// Initial marking
			markEditableTokens( fieldRef );

			// Add a single click handler at the field level using event delegation
			if ( ! fieldRef.dataset.clickHandlerAttached ) {
				fieldRef.dataset.clickHandlerAttached = 'true';
				fieldRef.addEventListener(
					'click',
					( e ) => handleTokenTextClick( e, taxonomySlug ),
					true // Capture phase
				);
			}

			// Watch for DOM changes to re-mark new tokens
			// eslint-disable-next-line no-undef
			const observer = new MutationObserver( () => {
				markEditableTokens( fieldRef );
			} );

			observer.observe( fieldRef, {
				childList: true,
				subtree: true,
			} );

			observers.push( observer );
		} );

		return () => {
			observers.forEach( ( observer ) => observer.disconnect() );
		};
	}, [ taxonomiesInfo, query.taxQuery, handleAITokenClick ] );

	const onTermsChange = ( taxonomySlug ) => async ( newTermValues ) => {
		const taxonomyInfo = taxonomiesInfo.find(
			( { slug } ) => slug === taxonomySlug
		);

		if ( ! taxonomyInfo ) {
			return;
		}
		let newTerm = {};
		const termData = await Promise.all(
			newTermValues.map( async ( termValue ) => {
				const termId = getTermIdByTermValue(
					taxonomyInfo.terms.mapByName,
					termValue
				);

				if ( termId ) {
					return {
						[ termValue.value ]: termId,
					};
				}
				const term = {
					name: termValue,
					taxonomy: taxonomySlug,
				};

				const request = {
					path: `/wp/v2/${ taxonomySlug }`,
					data: term,
					method: 'POST',
				};

				const response = await wp
					.apiRequest( request )
					.catch( ( error ) => {
						// eslint-disable-next-line no-console
						console.log( 'Error', error );
						return null;
					} );

				if ( response && response.id ) {
					newTerm = {
						id: response.id,
						name: termValue,
						taxonomy: taxonomySlug,
						count: 0,
						description: '',
					};

					// Update taxonomiesInfo with new term.
					const updatedTaxonomiesInfo = taxonomiesInfo.map(
						( taxoInfo ) => {
							if ( taxoInfo.slug === taxonomySlug ) {
								// Append newTerm to taxoInfo.terms.
								const terms = {
									...taxoInfo.terms,
									entities: [
										...taxoInfo.terms.entities,
										newTerm,
									],
									mapById: {
										...taxoInfo.terms.mapById,
										[ newTerm.id ]: newTerm,
									},
									mapByName: {
										...taxoInfo.terms.mapByName,
										[ newTerm.name ]: newTerm,
									},
									names: [
										...taxoInfo.terms.names,
										newTerm.name,
									],
								};

								return {
									...taxoInfo,
									terms,
								};
							}

							return taxoInfo;
						}
					);

					setNewTermsInfo( updatedTaxonomiesInfo );

					return {
						[ termValue ]: response.id,
					}; // Create an object with the term name as the key and the ID as the value
				}
				return null; // Handle creation failure
			} )
		);

		const termDataObject = termData.reduce( ( accumulator, item ) => {
			if ( item ) {
				return {
					...accumulator,
					...item,
				}; // Merge objects to create a single object with term names as keys and IDs as values
			}
			return accumulator;
		}, {} );

		const newTaxQuery = {
			...query.taxQuery,
			[ taxonomySlug ]: termDataObject,
		};

		onChange( {
			taxQuery: newTaxQuery,
		} );
	};

	// Returns only the existing term ids in proper format to be
	// used in `FormTokenField`. This prevents the component from
	// crashing in the editor, when non existing term ids were provided.
	const getExistingTaxQueryValue = ( taxonomySlug ) => {
		const taxonomyInfo = taxonomiesInfo.find(
			( { slug } ) => slug === taxonomySlug
		);

		if ( ! taxonomyInfo ) {
			return [];
		}

		let termIds = query.taxQuery[ taxonomySlug ] || [];
		termIds = Object.values( termIds );

		return termIds.reduce( ( accumulator, termId ) => {
			const term = taxonomyInfo.terms.mapById[ termId ];
			if ( term ) {
				// Decode HTML entities.
				const textarea = document.createElement( 'textarea' );
				textarea.innerHTML = term.name;
				accumulator.push( {
					id: termId,
					value: textarea.value,
				} );
			}
			return accumulator;
		}, [] );
	};

	/**
	 * Render the inline edit input overlay.
	 *
	 * @param {string} taxonomySlug The taxonomy slug to check if editing.
	 * @return {JSX.Element|null} The input overlay or null.
	 */
	const renderInlineEditInput = ( taxonomySlug ) => {
		if ( ! editingToken || editingToken.taxonomySlug !== taxonomySlug ) {
			return null;
		}

		const { position } = editingToken;

		return (
			<input
				ref={ ( el ) => {
					if ( el && ! inlineInputRef.current ) {
						inlineInputRef.current = el;
						// Focus and select after mount
						setTimeout( () => {
							el.focus();
							el.setSelectionRange(
								el.value.length,
								el.value.length
							);
						}, 0 );
					}
				} }
				type="text"
				className="classifai-inline-edit-input"
				value={ editValue }
				onChange={ handleInlineInputChange }
				onKeyDown={ handleInlineInputKeydown }
				onBlur={ handleInlineInputBlur }
				style={ {
					position: 'absolute',
					top: position?.top || 0,
					left: position?.left || 0,
					minWidth: Math.max( position?.width || 100, 100 ),
					height: position?.height || 24,
					zIndex: 1000,
				} }
				// eslint-disable-next-line jsx-a11y/no-autofocus
				autoFocus
			/>
		);
	};

	return (
		<>
			{ !! taxonomiesInfo?.length &&
				taxonomiesInfo.map( ( { slug, name, terms } ) => {
					if ( ! terms?.names?.length || query?.isLoading ) {
						return null;
					}

					// if none of the terms?.names has "[AI]" prefix, skip the iteration
					let hasAI = false;
					if ( query.taxTermsAI ) {
						// Return if this is not a feature taxonomy
						if ( ! featureTaxonomies.includes( slug ) ) {
							return null;
						}

						Object.keys( terms.mapById ).forEach( ( term ) => {
							if (
								terms.mapById[ term ].name.indexOf( '[AI]' ) !==
								-1
							) {
								hasAI = true;
							}
						} );
					}

					return (
						<Fragment key={ slug }>
							<div
								ref={ ( el ) => {
									tokenFieldRefs.current[ slug ] = el;
								} }
								className="classifai-taxonomy-field-wrapper"
								style={ { position: 'relative' } }
							>
								{ renderInlineEditInput( slug ) }
								<FormTokenField
									key={ slug }
									label={ name }
									value={ getExistingTaxQueryValue( slug ) }
									suggestions={ terms.names }
									onChange={ onTermsChange( slug ) }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
							</div>
							{ ! hasAI && (
								<>
									<p
										style={ { color: '#cc1818' } }
										key={ slug }
									>
										{ sprintf(
											/* translators: %s: taxonomy name */
											__(
												'ClassifAI has no new recommendations for %s',
												'classifai'
											),
											name
										) }
									</p>
								</>
							) }
							<hr />
						</Fragment>
					);
				} ) }
		</>
	);
};

export default TaxonomyControls;
