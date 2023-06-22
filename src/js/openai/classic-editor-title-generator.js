import apiFetch from '@wordpress/api-fetch';

const classifaiChatGPTData = window.classifaiChatGPTData || {};
let scriptData = classifaiChatGPTData.enabledFeatures.reduce( ( acc, cur ) => ( { [ cur.feature ] : cur } ), {} );

console.log(scriptData)

( function( $ ) {
	$( document ).ready( () => {
		if ( scriptData?.title ) {
			generateTitleInit();
		}
	} );

	function generateTitleInit() {
		// Adds button to the UI.
		// $( '<a>', {
		// 	href: '#',
		// 	id: 'classifai-openai__title-generate-btn',
		// 	text: scriptData?.title?.buttonText ?? '',
		// 	class: 'button',
		// } ).appendTo( '#titlewrap' );

		$( '<span />', {
			text: scriptData?.title?.buttonText ?? '',
			'class': 'classifai-openai__title-generate-btn--text',
		} )
		.wrap( '<div class="button" id="classifai-openai__title-generate-btn" />' )
		.parent()
		.append( $( '<span />', {
			'class': 'classifai-openai__title-generate-btn--spinner',
		} ) )
		.appendTo( '#titlewrap' )
	
		const postId = $( '#post_ID' ).val();

		const hidePopup = () => {
			$( '#classifai-openai__results' ).removeClass( 'classifai-openai--fade-in' ).delay(300).fadeOut(0);
		}

		const generateTitle = ( e ) => {
			e.preventDefault();

			$( '#classifai-openai__results-content' ).html( '' );
			const generateTextEl = $( '.classifai-openai__title-generate-btn--text' );
			const spinnerEl = $( '.classifai-openai__title-generate-btn--spinner' );

			generateTextEl.css( 'opacity', '0' );
			spinnerEl.show();

			const path = scriptData.title?.path + postId;

			apiFetch( {
				path,
			} )
			.then( ( result ) => {
				generateTextEl.css( 'opacity', '1' );
				spinnerEl.hide();

				result.forEach( ( title ) => {
					$( '<textarea>', {
						text: title
					} )
					.wrap( '<div class="classifai-openai__result-item" />' )
					.parent()
					.append( $( '<button />', {
						text: 'Select',
						type: 'button',
						'class': 'button classifai-openai__select-title',
					} ) )
					.appendTo( '#classifai-openai__results-content' );
				} );

				$( '#classifai-openai__results' ).show().addClass( 'classifai-openai--fade-in' );
			} );
		};

		$( document ).on( 'click', '#classifai-openai__title-generate-btn', generateTitle );
		$( document ).on( 'click', '#classifai-openai__overlay', hidePopup )

		const resultWrapper = $( '#classifai-openai__results' );
		resultWrapper
			.find( '#classifai-openai__results-title' )
			.text( scriptData.title.modalTitle );
	}
} ( jQuery ) );
