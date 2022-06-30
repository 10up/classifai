/* global ClassifAI */
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
	function showPreview() {
		const categoryThreshold = Number( document.querySelector( '#classifai-settings-category_threshold' ).value );
		const keywordThreshold = Number( document.querySelector( '#classifai-settings-keyword_threshold' ).value );
		const entityThreshold = Number( document.querySelector( '#classifai-settings-entity_threshold' ).value );
		const conceptThreshold = Number( document.querySelector( '#classifai-settings-concept_threshold' ).value );

		const thresholds = {
			'categories': categoryThreshold,
			'keywords': keywordThreshold,
			'entities': entityThreshold,
			'concepts': conceptThreshold,
		};

		const formData = new FormData();
		formData.append( 'action', 'get_post_classifier_preview_data' );
		formData.append( 'post_id', 14 );

		fetch( `${ ajaxurl }`, {
			method: 'POST',
			body: formData
		} ).then( response => {
			return response.json();
		} ).then( data => {
			const { categories = [], concepts = [], entities = [], keywords = [] } = data;
			const filteredItems = filterByScoreOrRelevance( { categories, concepts, entities, keywords }, thresholds );
			buildPreviewUI( filteredItems );
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
				htmlData += `<div class="row"><div class="tax-type">${ prop }</div>`;
				obj[ prop ].forEach( item => {
					htmlData += '<div class="cell">';
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

					htmlData += `${name}, ${rating * 100}%`;
					htmlData += '</div>';
				} );

				htmlData += '</div>';
			} );
		} );

		return htmlData;
	}

	const getClassifierDataBtn = document.getElementById( 'get-classifier-preview-data-btn' );
	getClassifierDataBtn.addEventListener( 'click', showPreview );
} )();
