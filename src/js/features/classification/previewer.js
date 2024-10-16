import Choices from 'choices.js';
import './previewer.scss';

( () => {
	let featureStatuses = {};

	const nonceEl = document.getElementById( 'classifai-previewer-nonce' );

	if ( ! nonceEl ) {
		return;
	}

	const providerSelect = document.getElementById( 'provider' );
	const provider = providerSelect.value;

	const previewWatson = () => {
		if ( 'ibm_watson_nlu' !== provider ) {
			return;
		}

		const getClassifierDataBtn = document.getElementById(
			'get-classifier-preview-data-btn'
		);
		getClassifierDataBtn.addEventListener( 'click', showPreviewWatson );

		/** Previewer nonce. */
		const previewerNonce = nonceEl.value;

		/** Feature statuses. */
		featureStatuses = {
			categoriesStatus: document.getElementById( 'category' ).checked,
			keywordsStatus: document.getElementById( 'keyword' ).checked,
			entitiesStatus: document.getElementById( 'entity' ).checked,
			conceptsStatus: document.getElementById( 'concept' ).checked,
		};

		const plurals = {
			category: 'categories',
			keyword: 'keywords',
			entity: 'entities',
			concept: 'concepts',
		};

		document
			.querySelectorAll( '#category, #keyword, #entity, #concept' )
			.forEach( ( item ) => {
				item.addEventListener( 'change', ( e ) => {
					if ( 'category' === e.target.id ) {
						featureStatuses.categoriesStatus = e.target.checked;
					}

					if ( 'keyword' === e.target.id ) {
						featureStatuses.keywordsStatus = e.target.checked;
					}

					if ( 'entity' === e.target.id ) {
						featureStatuses.entitiesStatus = e.target.checked;
					}

					if ( 'concept' === e.target.id ) {
						featureStatuses.conceptsStatus = e.target.checked;
					}

					const taxType = e.target.id.split( '-' ).at( -1 );

					if ( e.target.checked ) {
						document
							.querySelector(
								`.tax-row--${ plurals[ taxType ] }`
							)
							.classList.remove( 'tax-row--hide' );
					} else {
						document
							.querySelector(
								`.tax-row--${ plurals[ taxType ] }`
							)
							.classList.add( 'tax-row--hide' );
					}
				} );
			} );

		/**
		 * Live preview features.
		 *
		 * @param {Object} e The event object.
		 */
		function showPreviewWatson( e ) {
			/** Category thresholds. */
			const categoryThreshold = Number(
				document.querySelector( '#category_threshold' ).value
			);
			const keywordThreshold = Number(
				document.querySelector( '#keyword_threshold' ).value
			);
			const entityThreshold = Number(
				document.querySelector( '#entity_threshold' ).value
			);
			const conceptThreshold = Number(
				document.querySelector( '#concept_threshold' ).value
			);

			const postId = document.getElementById(
				'classifai-preview-post-selector'
			).value;

			const previewWrapper = document.getElementById(
				'classifai-post-preview-wrapper'
			);
			const thresholds = {
				categories: categoryThreshold,
				keywords: keywordThreshold,
				entities: entityThreshold,
				concepts: conceptThreshold,
			};

			e.target
				.closest( '.button' )
				.classList.add( 'get-classifier-preview-data-btn--loading' );

			const formData = new FormData();
			formData.append( 'action', 'get_post_classifier_preview_data' );
			formData.append( 'post_id', postId );
			formData.append( 'nonce', previewerNonce );

			fetch( `${ ajaxurl }`, {
				method: 'POST',
				body: formData,
			} )
				.then( ( response ) => {
					return response.json();
				} )
				.then( ( data ) => {
					if ( ! data.success ) {
						previewWrapper.style.display = 'block';
						previewWrapper.innerHTML = data.data;
						e.target
							.closest( '.button' )
							.classList.remove(
								'get-classifier-preview-data-btn--loading'
							);
						return;
					}

					const {
						data: {
							categories = [],
							concepts = [],
							entities = [],
							keywords = [],
						},
					} = data;

					const dataToFilter = {
						categories,
						keywords,
						entities,
						concepts,
					};

					const filteredItems = filterByScoreOrRelevance(
						dataToFilter,
						thresholds
					);
					const htmlData = buildPreviewUI( filteredItems );
					previewWrapper.style.display = 'block';
					previewWrapper.innerHTML = htmlData;

					e.target
						.closest( '.button' )
						.classList.remove(
							'get-classifier-preview-data-btn--loading'
						);
				} );
		}

		/**
		 * Filters response data depending on the threshold value.
		 *
		 * @param {Object} data       Response data from NLU.
		 * @param {Object} thresholds Object containing threshold values for various taxnomy types.
		 * @return {Array} Sorted data.
		 */
		function filterByScoreOrRelevance( data = {}, thresholds ) {
			const filteredItems = Object.keys( data ).map( ( key ) => ( {
				[ key ]: data[ key ].filter( ( item ) => {
					if ( item?.score && item.score * 100 > thresholds[ key ] ) {
						return item;
					} else if (
						item?.relevance &&
						item.relevance * 100 > thresholds[ key ]
					) {
						return item;
					}

					return item;
				} ),
			} ) );

			return filteredItems;
		}
	};
	previewWatson();

	const previewEmbeddings = () => {
		if ( 'ibm_watson_nlu' === provider ) {
			return;
		}

		const getClassifierDataBtn = document.getElementById(
			'get-classifier-preview-data-btn'
		);
		getClassifierDataBtn.addEventListener( 'click', showPreviewEmeddings );

		/** Previewer nonce. */
		const previewerNonce = nonceEl.value;

		/**
		 * Live preview features.
		 *
		 * @param {Object} e The event object.
		 */
		function showPreviewEmeddings( e ) {
			const postId = document.getElementById(
				'classifai-preview-post-selector'
			).value;

			const previewWrapper = document.getElementById(
				'classifai-post-preview-wrapper'
			);

			// clear previewWrapper.
			previewWrapper.innerHTML = '';

			e.target
				.closest( '.button' )
				.classList.add( 'get-classifier-preview-data-btn--loading' );

			const formData = new FormData();
			formData.append(
				'action',
				'get_post_classifier_embeddings_preview_data'
			);
			formData.append( 'post_id', postId );
			formData.append( 'nonce', previewerNonce );

			fetch( `${ ajaxurl }`, {
				method: 'POST',
				body: formData,
			} )
				.then( ( response ) => {
					return response.json();
				} )
				.then( ( data ) => {
					if ( ! data.success ) {
						previewWrapper.style.display = 'block';
						previewWrapper.innerHTML = data.data;
						e.target
							.closest( '.button' )
							.classList.remove(
								'get-classifier-preview-data-btn--loading'
							);
						return;
					}

					const fragment = buildPreviewUIForEmbeddings( data.data );
					previewWrapper.style.display = 'block';
					previewWrapper.replaceChildren( fragment );

					// remove all .tax-row--hide
					document
						.querySelectorAll( '.tax-row--hide' )
						.forEach( ( item ) => {
							item.classList.remove( 'tax-row--hide' );
						} );

					e.target
						.closest( '.button' )
						.classList.remove(
							'get-classifier-preview-data-btn--loading'
						);
				} );
		}
	};
	previewEmbeddings();

	function buildPreviewUIForEmbeddings( filteredItems ) {
		// Create a document fragment to hold the generated DOM elements.
		const fragment = document.createDocumentFragment();

		// Iterate over the categories in filteredItems.
		Object.keys( filteredItems ).forEach( ( categoryKey ) => {
			const category = filteredItems[ categoryKey ];

			// Create the category row container.
			const taxRowDiv = document.createElement( 'div' );
			taxRowDiv.className = `tax-row tax-row--${ categoryKey } ${
				featureStatuses[ `${ categoryKey }Status` ]
					? ''
					: 'tax-row--hide'
			}`;

			// Create and append the category label div.
			const taxTypeDiv = document.createElement( 'div' );
			taxTypeDiv.className = `tax-type tax-type--${ categoryKey }`;
			taxTypeDiv.textContent = category.label;
			taxRowDiv.appendChild( taxTypeDiv );

			// Iterate over the items in the category.
			category.data.forEach( ( item ) => {
				let rating = item?.score || 0;
				let name = item?.label || '';

				const width = 300 + 300 * rating;
				rating = ( rating * 100 ).toFixed( 2 );
				name = name
					.split( '/' )
					.filter( ( i ) => '' !== i )
					.join( ', ' );

				// Create the item cell.
				const taxCellDiv = document.createElement( 'div' );
				taxCellDiv.className = 'tax-cell';
				taxCellDiv.style.width = `${ width }px`;

				// Create and append the rating span.
				const taxScoreSpan = document.createElement( 'span' );
				taxScoreSpan.className = 'tax-score';
				taxScoreSpan.textContent = `${ rating }%`;
				taxCellDiv.appendChild( taxScoreSpan );

				// Create and append the label span.
				const taxLabelSpan = document.createElement( 'span' );
				taxLabelSpan.className = 'tax-label';
				taxLabelSpan.textContent = name;
				taxCellDiv.appendChild( taxLabelSpan );

				// Append the cell to the category row.
				taxRowDiv.appendChild( taxCellDiv );
			} );

			// Append the category row to the fragment.
			fragment.appendChild( taxRowDiv );
		} );

		return fragment;
	}

	/**
	 * Builds user readable HTML data from the response by NLU.
	 *
	 * @param {Array} filteredItems Array of data that needs to be rendered.
	 * @return {string} HTML preview string.
	 */
	function buildPreviewUI( filteredItems = [] ) {
		let htmlData = '';
		// check if filteredItems.forEach type is function
		if ( ! filteredItems.forEach ) {
			return '';
		}

		filteredItems.forEach( ( obj ) => {
			Object.keys( obj ).forEach( ( prop ) => {
				htmlData += `<div class="tax-row tax-row--${ prop } ${
					featureStatuses[ `${ prop }Status` ] ? '' : 'tax-row--hide'
				}"><div class="tax-type tax-type--${ prop }">${ prop }</div>`;
				obj[ prop ].forEach( ( item ) => {
					let rating = 0;
					let name = 0;

					if ( item?.score ) {
						rating = item.score;
					} else if ( item?.relevance ) {
						rating = item.relevance;
					}

					if ( item?.text ) {
						name = item.text;
					} else if ( item?.label ) {
						name = item.label;
					}

					const width = 300 + 300 * rating;
					rating = ( rating * 100 ).toFixed( 2 );
					name = name
						.split( '/' )
						.filter( ( i ) => '' !== i )
						.join( ', ' );

					htmlData += `<div class="tax-cell" style="width: ${ width }px">`;
					htmlData += `<span class="tax-score">${ rating }%</span> <span class="tax-label">${ name }</span>`;
					htmlData += '</div>';
				} );

				htmlData += '</div>';
			} );
		} );

		return htmlData;
	}

	/*
	 * Post selector Choices.js
	 */
	const selectEl = document.getElementById(
		'classifai-preview-post-selector'
	);
	const selectElChoices = new Choices( selectEl, {
		noResultsText: '',
		allowHTML: true,
	} );

	/**
	 * Searches the post by input text.
	 *
	 * @param {Object} event Choices.js's 'search' event object.
	 */
	function searchPosts( event ) {
		/** Previewer nonce. */
		const previewerNonce = nonceEl.value;

		/*
		 * Post types.
		 */
		const postTypes = [].slice
			.call(
				document.querySelectorAll(
					'input[name*="classifai_watson_nlu[post_types"]:checked'
				)
			)
			.map( ( type ) => type.id.split( '-' ).at( -1 ) );

		/*
		 * Post statuses.
		 */
		const postStatuses = [].slice
			.call(
				document.querySelectorAll(
					'input[name*="classifai_watson_nlu[post_statuses"]:checked'
				)
			)
			.map( ( status ) => status.id.split( '-' ).at( -1 ) );

		const formData = new FormData();
		formData.append( 'post_types', postTypes );
		formData.append( 'post_status', postStatuses );
		formData.append( 'search', event.detail.value );
		formData.append( 'action', 'classifai_get_post_search_results' );
		formData.append( 'nonce', previewerNonce );

		fetch( `${ ajaxurl }`, {
			method: 'POST',
			body: formData,
		} )
			.then( ( response ) => {
				return response.json();
			} )
			.then( ( data ) => {
				const { data: posts } = data;

				selectElChoices.setChoices(
					posts.map( ( post ) => ( {
						value: post.ID,
						label: post.post_title,
					} ) ),
					'value',
					'label',
					true
				);
			} );
	}

	const searchPostsDebounced = debounce( searchPosts, 300 );

	selectEl.addEventListener( 'search', searchPostsDebounced );

	/**
	 * Function to debounce an input function.
	 *
	 * @param {Function} func      The function to debounce.
	 * @param {number}   wait      Debounce period.
	 * @param {boolean}  immediate Debounce immediately.
	 * @return {Function} Returns a debounced function.
	 */
	function debounce( func, wait, immediate ) {
		let timeout;

		return function () {
			const context = this,
				args = arguments;

			/** Debounced function. */
			const later = function () {
				timeout = null;
				if ( ! immediate ) {
					func.apply( context, args );
				}
			};
			const callNow = immediate && ! timeout;
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );
			if ( callNow ) {
				func.apply( context, args );
			}
		};
	}
} )();

document.addEventListener( 'DOMContentLoaded', function () {
	// Display "Classify Post" button only when "Process content on update" is unchecked (Classic Editor).
	const classifaiNLUCheckbox = document.getElementById(
		'classifai-process-content'
	);
	if ( classifaiNLUCheckbox ) {
		classifaiNLUCheckbox.addEventListener( 'change', function () {
			const classifyButton = document.querySelector(
				'.classifai-clasify-post-wrapper'
			);
			if ( this.checked === true ) {
				classifyButton.style.display = 'none';
			} else {
				classifyButton.style.display = 'block';
			}
		} );
		classifaiNLUCheckbox.dispatchEvent( new Event( 'change' ) );
	}

	// Display audio preview only when "Enable audio generation" is checked (Classic Editor).
	const classifaiAudioGenerationCheckbox = document.getElementById(
		'classifai_synthesize_speech'
	);
	const classifaiAudioPreview = document.getElementById(
		'classifai-audio-preview'
	);
	if ( classifaiAudioGenerationCheckbox && classifaiAudioPreview ) {
		classifaiAudioGenerationCheckbox.addEventListener(
			'change',
			function () {
				if ( this.checked === true ) {
					classifaiAudioPreview.style.display = 'block';
				} else {
					classifaiAudioPreview.style.display = 'none';
				}
			}
		);
	}
} );
