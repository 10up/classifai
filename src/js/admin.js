/* global ClassifAI */
import 'choices.js';
import Choices from 'choices.js';
import '../scss/admin.scss';

( () => {
	const $toggler = document.getElementById( 'classifai-waston-cred-toggle' );
	const $userField = document.getElementById( 'classifai-settings-watson_username' );

	if ( null === $toggler || null === $userField ) return;

	const $userFieldWrapper = $userField.closest( 'tr' );
	const [$passwordFieldTitle] = document.getElementById( 'classifai-settings-watson_password' ).closest( 'tr' ).getElementsByTagName( 'label' );

	if ( null === $toggler ) return;

	$toggler.addEventListener( 'click', e => {
		e.preventDefault();
		$userFieldWrapper.classList.toggle( 'hidden' );

		if ( $userFieldWrapper.classList.contains( 'hidden' ) ) {
			$toggler.innerText = ClassifAI.use_password;
			$passwordFieldTitle.innerText = ClassifAI.api_key;
			$userField.value = 'apikey';
			return;
		}

		$toggler.innerText = ClassifAI.use_key;
		$passwordFieldTitle.innerText = ClassifAI.api_password;
	} );

	/**
	 * Live preview features.
	 */
	function showPreview( e ) {
		/*
		 * Category thresholds. 
		 */
		const categoryThreshold = Number( document.querySelector( '#classifai-settings-category_threshold' ).value );
		const keywordThreshold = Number( document.querySelector( '#classifai-settings-keyword_threshold' ).value );
		const entityThreshold = Number( document.querySelector( '#classifai-settings-entity_threshold' ).value );
		const conceptThreshold = Number( document.querySelector( '#classifai-settings-concept_threshold' ).value );

		/*
		 * Feature statuses.
		 */
		const categoryStatus = document.getElementById( 'classifai-settings-category' ).checked;
		const keywordStatus = document.getElementById( 'classifai-settings-keyword' ).checked;
		const entityStatus = document.getElementById( 'classifai-settings-entity' ).checked;
		const conceptStatus = document.getElementById( 'classifai-settings-concept' ).checked;

		const postId = document.getElementById( 'classifai-preview-post-selector' ).value;

		const previewWrapper = document.getElementById( 'classifai-post-preview-wrapper' );
		const thresholds = {};

		if ( categoryStatus ) {
			thresholds.categories = categoryThreshold;
		}

		if ( keywordStatus ) {
			thresholds.keywords = keywordThreshold;
		}

		if ( entityStatus ) {
			thresholds.entities = entityThreshold;
		}

		if ( conceptStatus ) {
			thresholds.concepts = conceptThreshold;
		}

		e.target.closest( '.button' ).classList.add( 'get-classifier-preview-data-btn--loading' );

		const formData = new FormData();
		formData.append( 'action', 'get_post_classifier_preview_data' );
		formData.append( 'post_id', postId );

		fetch( `${ ajaxurl }`, {
			method: 'POST',
			body: formData
		} ).then( response => {
			return response.json();
		} ).then( data => {
			const { categories = [], concepts = [], entities = [], keywords = [] } = data;
			const dataToFilter = {};

			if ( categoryStatus ) {
				dataToFilter.categories = categories;
			}
	
			if ( keywordStatus ) {
				dataToFilter.keywords = keywords;
			}
	
			if ( entityStatus ) {
				dataToFilter.entities = entities;
			}
	
			if ( conceptStatus ) {
				dataToFilter.concepts = concepts;
			}


			const filteredItems = filterByScoreOrRelevance( dataToFilter, thresholds );
			const htmlData = buildPreviewUI( filteredItems );
			previewWrapper.style.display = 'block';
			previewWrapper.innerHTML = htmlData;

			e.target.closest( '.button' ).classList.remove( 'get-classifier-preview-data-btn--loading' );
		} );
	}

	/**
	 * Filters response data depending on the threshold value.
	 *
	 * @param {object} data Response data from NLU.
	 * @param {object} thresholds Object containing threshold values for various taxnomy types.
	 * @returns {array}
	 */
	function filterByScoreOrRelevance( data = {}, thresholds ) {
		const filteredItems = Object.keys( data ).map( key => ( { [ key ] : data[ key ].filter( item => {
			if ( item?.score && ( item.score * 100 ) > thresholds[ key ] ) {
				return item;
			} else if ( item?.relevance && ( item.relevance * 100 ) > thresholds[ key ] ) {
				return item;
			}
		} ) } ) );

		return filteredItems;
	}

	/**
	 * Builds user readable HTML data from the response by NLU.
	 *
	 * @param {array} filteredItems Array of data that needs to be rendered.
	 * @return {string}
	 */
	function buildPreviewUI( filteredItems = [] ) {
		let htmlData = '';

		filteredItems.forEach( obj => {
			Object.keys( obj ).forEach( prop => {
				htmlData += `<div class="tax-row"><div class="tax-type">${ prop }</div>`;
				obj[ prop ].forEach( item => {
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

					const width = 300 + ( 300 * rating );
					rating = ( rating * 100 ).toFixed( 2 );
					name = name.split( '/' ).filter( i => '' !== i ).join( ', ' );

					htmlData += `<div class="tax-cell" style="width: ${width}px">`;
					htmlData += `<span class="tax-score">${rating}%</span> <span class="tax-label">${name}</span>`;
					htmlData += '</div>';
				} );

				htmlData += '</div>';
			} );
		} );

		return htmlData;
	}

	const getClassifierDataBtn = document.getElementById( 'get-classifier-preview-data-btn' );
	getClassifierDataBtn.addEventListener( 'click', showPreview );

	/*
	 * Post selector Choices.js 
	 */
	const selectEl = document.getElementById( 'classifai-preview-post-selector' );
	const selectElChoices = new Choices( selectEl, {
		noResultsText: '',
	} );

	/**
	 * Searches the post by input text.
	 *
	 * @param {Object} event Choices.js's 'search' event object.
	 */
	function searchPosts( event ) {
		/*
		 * Post types. 
		 */
		const postTypes = []
			.slice
			.call( document.querySelectorAll( 'input[name*="classifai_watson_nlu[post_types"]:checked' ) )
			.map( type => type.id.split( '-' ).at( -1 ) );

		/*
		 * Post statuses. 
		 */
		const postStatuses = []
			.slice
			.call( document.querySelectorAll( 'input[name*="classifai_watson_nlu[post_statuses"]:checked' ) )
			.map( status => status.id.split( '-' ).at( -1 ) );

		const formData = new FormData();
		formData.append( 'post_types', postTypes );
		formData.append( 'post_status', postStatuses );
		formData.append( 'search', event.detail.value );
		formData.append( 'action', 'get_post_search_results' );

		fetch( `${ ajaxurl }`, {
			method: 'POST',
			body: formData
		} ).then( response => {
			return response.json();
		} ).then( data => {
			const { data: posts } = data;

			selectElChoices.setChoices(
				posts.map( post => ( { value: post.ID, label: post.post_title } ) ),
				'value',
				'label',
				true,
			);
		} );
	}

	const searchPostsDebounced = debounce( searchPosts, 300 );

	selectEl.addEventListener( 'search', searchPostsDebounced );

	/**
	 * Function to debounce an input function.
	 *
	 * @param {Function} func The function to debounce.
	 * @param {integer} wait Debounce period.
	 * @param {boolean} immediate Debounce immediately.
	 * @returns 
	 */
	function debounce( func, wait, immediate ) {
		let timeout;

		return function() {
			const context = this, args = arguments;

			/**
			 * Debounced function.
			 */
			const later = function() {
				timeout = null;
				if ( ! immediate ) func.apply( context, args );
			};
			const callNow = immediate && !timeout;
			clearTimeout( timeout );
			timeout = setTimeout( later, wait );
			if ( callNow ) func.apply( context, args );
		};
	}
} )();
