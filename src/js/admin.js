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
	const $addNewPromptFieldsetButton = document.querySelectorAll(
		'button.js-classifai-add-prompt-fieldset'
	);
	if ( $addNewPromptFieldsetButton.length ) {
		$addNewPromptFieldsetButton.forEach( ( button ) => {
			button.addEventListener( 'click', ( e ) => {
				e.preventDefault();

				addNewFieldSet( e.target.previousElementSibling );
			} );
		} );
	}

	// Attach event to existing prompt fieldsets.
	const $promptFieldsets = document.querySelectorAll(
		'.classifai-field-type-prompt-setting'
	);
	if ( $promptFieldsets.length ) {
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
	 * @param {Element} $parentRow
	 */
	function resetInputFields( $fieldset, $parentRow ) {
		const $allFieldsets = $parentRow.querySelectorAll( 'fieldset' );
		const $lastFieldset = Array.from( $allFieldsets ).pop();
		const highestFieldIndexOfFieldset = parseInt(
			$lastFieldset.querySelector( 'input' ).name.match( /\d+/ ).pop()
		);
		const fields = $fieldset.querySelectorAll( 'input, textarea' );
		const actionButtons = $fieldset.querySelectorAll(
			'.actions-rows .action__set_default'
		);

		// Reset form fields.
		fields.forEach( ( field ) => {
			field.value = '';

			// Add index to field name.
			field.name = field.name.replace(
				/(\d+)/g,
				() => highestFieldIndexOfFieldset + 1
			);
		} );

		// Reset action buttons.
		actionButtons.forEach( ( button ) => {
			button.classList.remove( 'selected' );
		} );
	}

	/**
	 * Attach event to fieldset.
	 *
	 * @since x.x.x
	 * @param {Element} $newPromptFieldset
	 */
	function attachEventPromptFieldset( $newPromptFieldset ) {
		// Add event to remove button.
		const $removePromptFieldsetButton = $newPromptFieldset.querySelector(
			'.action__remove_prompt'
		);
		$removePromptFieldsetButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			e.target.closest( 'fieldset' ).remove();
		} );

		// Add event to set as default button.
		const $setAsDefaultButton = $newPromptFieldset.querySelector(
			'.action__set_default'
		);
		$setAsDefaultButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			// If already selected, do nothing.
			if ( e.target.classList.contains( 'selected' ) ) {
				return;
			}

			// Remove selected class from all buttons.
			const $settingRow = e.target.closest( 'tr' );
			const $setAsDefaultButtons = $settingRow.querySelectorAll(
				'.action__set_default'
			);
			$setAsDefaultButtons.forEach( ( button ) => {
				button.classList.remove( 'selected' );
				button
					.closest( 'fieldset' )
					.querySelector( '.js-setting-field__default' ).value = '';
			} );

			// Set selected class.
			e.target.classList.add( 'selected' );

			// Set default value.
			$newPromptFieldset.querySelector( '.js-setting-field__default' ).value = 'true';
		} );
	}

	/**
	 * Add a new fieldset.
	 *
	 * @since x.x.x
	 * @param {Element} $sibling
	 *
	 * @return {Element} $newPromptFieldset
	 */
	function addNewFieldSet( $sibling ) {
		const $promptFieldsetTemplate = $sibling.parentElement.querySelector(
			'.classifai-field-type-prompt-setting'
		);
		const $newPromptFieldset = $promptFieldsetTemplate.cloneNode( true );

		resetInputFields( $newPromptFieldset, $sibling.closest( 'tr' ) );
		attachEventPromptFieldset( $newPromptFieldset );

		$sibling.insertAdjacentElement( 'afterend', $newPromptFieldset );

		return $newPromptFieldset;
	}
} )();
