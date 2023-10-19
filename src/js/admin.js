/* global ClassifAI */
import '../scss/admin.scss';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';

document.addEventListener( 'DOMContentLoaded', function () {
	const template = document.getElementById( 'help-menu-template' );
	if ( ! template ) {
		return;
	}
	const container = document.createElement( 'div' );
	container.appendChild( document.importNode( template.content, true ) );

	tippy( '.classifai-help', {
		allowHTML: true,
		content: container.innerHTML,
		trigger: 'click', // mouseenter, click, focus; manual.
		placement: 'bottom-end',
		arrow: true,
		animation: 'scale',
		duration: [ 250, 200 ],
		theme: 'light',
		interactive: true,
	} );
} );

( () => {
	const $toggler = document.getElementById( 'classifai-waston-cred-toggle' );
	const $userField = document.getElementById(
		'classifai-settings-watson_username'
	);

	if ( $toggler === null || $userField === null ) {
		return;
	}

	let $userFieldWrapper = null;
	let $passwordFieldTitle = null;
	if ( $userField.closest( 'tr' ) ) {
		$userFieldWrapper = $userField.closest( 'tr' );
	} else if ( $userField.closest( '.classifai-setup-form-field' ) ) {
		$userFieldWrapper = $userField.closest( '.classifai-setup-form-field' );
	}

	if (
		document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( 'tr' )
	) {
		[ $passwordFieldTitle ] = document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( 'tr' )
			.getElementsByTagName( 'label' );
	} else if (
		document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( '.classifai-setup-form-field' )
	) {
		[ $passwordFieldTitle ] = document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( '.classifai-setup-form-field' )
			.getElementsByTagName( 'label' );
	}

	$toggler.addEventListener( 'click', ( e ) => {
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
} )();

( () => {
	// Attach event to add new prompt button.
	const $addNewPromptFieldsetButton = document.querySelectorAll( 'button.js-classifai-add-prompt-fieldset' );
	if ( $addNewPromptFieldsetButton.length ) {
		$addNewPromptFieldsetButton.forEach( ( button ) => {
			button.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				addNewFieldSet(e.target.previousElementSibling);
			} );
		} );
	}

	// Attach event to existing prompt fieldsets.
	const $promptFieldsets = document.querySelectorAll( '.classifai-field-type-prompt-setting' );
	if( $promptFieldsets.length ) {
		$promptFieldsets.forEach( ( $promptFieldset ) => {
			attachEventPromptFieldset( $promptFieldset );
		} );
	}

	// ------------------
	// Helper function
	// ------------------

	/**
	 * Reset all input fields in a fieldset.
	 *
	 * @since x.x.x
	 * @param {Element} $fieldset
	 */
	function resetInputFields( $fieldset ) {
		const fields = $fieldset.querySelectorAll( 'input, textarea' );

		for ( let i = 0; i < fields.length; i++ ) {
			const field = fields[i];

			field.value = '';
		}
	}

	/**
	 * Attach event to fieldset.
	 *
	 * @since x.x.x
	 * @param {Element} $newPromptFieldset
	 */
	function attachEventPromptFieldset( $newPromptFieldset ) {
		// Add event to remove button.
		const $removePromptFieldsetButton = $newPromptFieldset.querySelector( '.action__remove_prompt' );
		if( $removePromptFieldsetButton ){
			$removePromptFieldsetButton.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				e.target.closest( 'fieldset' ).remove();
			} );
		}
	}

	/**
	 * Add a new fieldset.
	 *
	 * @since x.x.x
	 * @param {Element} $sibling
	 *
	 * @returns {Element} $newPromptFieldset
	 */
	function addNewFieldSet( $sibling ) {
		const $promptFieldsetTemplate = document.querySelector( '.classifai-field-type-prompt-setting' );
		const $newPromptFieldset = $promptFieldsetTemplate.cloneNode( true );

		resetInputFields( $newPromptFieldset );
		attachEventPromptFieldset( $newPromptFieldset );

		$sibling.insertAdjacentElement( 'afterend', $newPromptFieldset );

		return $newPromptFieldset;
	}
})();
