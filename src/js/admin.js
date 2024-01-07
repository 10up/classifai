/* global ClassifAI */
import { __ } from '@wordpress/i18n';
import '../scss/admin.scss';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';

import { createRoot, render } from '@wordpress/element';
import { UserSelector } from './components';

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
	const $userField = document.getElementById( 'username' );

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
			.getElementById( 'password' )
			.closest( 'tr' )
	) {
		[ $passwordFieldTitle ] = document
			.getElementById( 'password' )
			.closest( 'tr' )
			.getElementsByTagName( 'label' );
	} else if (
		document
			.getElementById( 'password' )
			.closest( '.classifai-setup-form-field' )
	) {
		[ $passwordFieldTitle ] = document
			.getElementById( 'password' )
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

// Role and user based access.
document.addEventListener( 'DOMContentLoaded', function () {
	function toogleAllowedRolesRow( e ) {
		const checkbox = e.target;
		const parentTr = checkbox.closest( 'tr.classifai-role-based-access' );
		const allowedRoles = parentTr.nextElementSibling.classList.contains(
			'allowed_roles_row'
		)
			? parentTr.nextElementSibling
			: null;
		if ( checkbox.checked ) {
			allowedRoles.classList.remove( 'hidden' );
		} else {
			allowedRoles.classList.add( 'hidden' );
		}
	}

	function toogleAllowedUsersRow( e ) {
		const checkbox = e.target;
		const parentTr = checkbox.closest( 'tr.classifai-user-based-access' );
		const allowedUsers = parentTr.nextElementSibling.classList.contains(
			'allowed_users_row'
		)
			? parentTr.nextElementSibling
			: null;
		if ( checkbox.checked ) {
			allowedUsers.classList.remove( 'hidden' );
		} else {
			allowedUsers.classList.add( 'hidden' );
		}
	}

	const roleBasedAccessCheckBoxes = document.querySelectorAll(
		'tr.classifai-role-based-access input[type="checkbox"]'
	);
	const userBasedAccessCheckBoxes = document.querySelectorAll(
		'tr.classifai-user-based-access input[type="checkbox"]'
	);

	if ( roleBasedAccessCheckBoxes ) {
		roleBasedAccessCheckBoxes.forEach( function ( e ) {
			e.addEventListener( 'change', toogleAllowedRolesRow );
			e.dispatchEvent( new Event( 'change' ) );
		} );
	}

	if ( userBasedAccessCheckBoxes ) {
		userBasedAccessCheckBoxes.forEach( function ( e ) {
			e.addEventListener( 'change', toogleAllowedUsersRow );
			e.dispatchEvent( new Event( 'change' ) );
		} );
	}
} );

// User Selector.
( () => {
	const userSearches = document.querySelectorAll(
		'.classifai-user-selector'
	);
	if ( ! userSearches ) {
		return;
	}

	userSearches.forEach( ( userSearch ) => {
		const id = userSearch.getAttribute( 'data-id' );
		const userElement = document.getElementById( id );
		const values = userElement.value?.split( ',' ) || [];
		const onChange = ( newValues ) => {
			userElement.value = newValues.join( ',' );
		};

		if ( createRoot ) {
			const root = createRoot( userSearch );
			root.render(
				<UserSelector value={ values } onChange={ onChange } />
			);
		} else {
			// Support for wp < 6.2
			render(
				<UserSelector value={ values } onChange={ onChange } />,
				userSearch
			);
		}
	} );
} )();

// Prompt fieldset.
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
	 * @since 2.4.0
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
			field.removeAttribute( 'readonly' );

			// Add index to field name.
			field.name = field.name.replace(
				/(\d+)/g,
				() => highestFieldIndexOfFieldset + 1
			);
		} );

		// Reset action buttons.
		actionButtons.forEach( ( button ) => {
			button.classList.remove( 'selected' );
			button.textContent = __( 'Set as default prompt', 'classifai' );
		} );
	}

	/**
	 * Attach event to fieldset.
	 *
	 * @since 2.4.0
	 * @param {Element} $newPromptFieldset
	 */
	function attachEventPromptFieldset( $newPromptFieldset ) {
		// Add event to remove prompt link
		const $removePromptFieldsetLink = $newPromptFieldset.querySelector(
			'a.action__remove_prompt'
		);

		$removePromptFieldsetLink.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			displayPromptRemovalModal( e.target );
		} );

		// Add event to set as default link.
		const $setAsDefaultLink = $newPromptFieldset.querySelector(
			'a.action__set_default'
		);

		$setAsDefaultLink.addEventListener( 'click', ( e ) => {
			e.preventDefault();

			// If already selected, do nothing.
			if ( e.target.classList.contains( 'selected' ) ) {
				return;
			}

			// Remove selected class from all buttons.
			const $settingRow = e.target.closest( 'tr' );
			const $setAsDefaultLinks = $settingRow.querySelectorAll(
				'.action__set_default'
			);
			$setAsDefaultLinks.forEach( ( link ) => {
				// Update text.
				if ( link.classList.contains( 'selected' ) ) {
					link.textContent = __(
						'Set as default prompt',
						'classifai'
					);
				}

				link.classList.remove( 'selected' );

				link
					.closest( 'fieldset' )
					.querySelector( '.js-setting-field__default' ).value = '';
			} );

			// Set selected class.
			e.target.classList.add( 'selected' );

			e.target.textContent = __( 'Default prompt', 'classifai' );

			// Set default value.
			$newPromptFieldset.querySelector(
				'.js-setting-field__default'
			).value = '1';
		} );
	}

	/**
	 * Handle prompt removal modal.
	 *
	 * @since 2.4.0
	 * @param {Element} removePromptLink
	 */
	function displayPromptRemovalModal( removePromptLink ) {
		jQuery( '#js-classifai--delete-prompt-modal' ).dialog( {
			modal: true,
			title: __( 'Remove Prompt', 'classifai' ),
			width: 550,
			buttons: [
				{
					text: __( 'Cancel', 'classifai' ),
					class: 'button-secondary',
					click() {
						jQuery( this ).dialog( 'close' );
					},
				},
				{
					text: __( 'Remove', 'classifai' ),
					class: 'button-primary',
					click() {
						const fieldset = removePromptLink.closest( 'fieldset' );
						const fieldsetContainer = fieldset.parentElement;
						const canResetPrompt =
							fieldset.querySelector(
								'.js-setting-field__default'
							).value === '1';
						const hasOnlySinglePrompt =
							2 ===
							fieldsetContainer.querySelectorAll( 'fieldset' )
								.length;

						fieldset.remove();

						// Set first prompt in list as default.
						if ( canResetPrompt ) {
							const setAsDefaultButton =
								fieldsetContainer.querySelector(
									'fieldset .action__set_default'
								);

							setAsDefaultButton.click();
						}

						// Hide remove button if only single fieldset is left.
						if ( hasOnlySinglePrompt ) {
							fieldsetContainer.querySelector(
								'.action__remove_prompt'
							).style.display = 'none';
						}

						jQuery( this ).dialog( 'close' );
					},
					style: 'margin-left: 10px;',
				},
			],
		} );
	}

	/**
	 * Add a new fieldset.
	 *
	 * @since 2.4.0
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

		$newPromptFieldset
			.querySelector( '.classifai-original-prompt' )
			.remove();

		$newPromptFieldset.querySelector(
			'.action__remove_prompt'
		).style.display = 'block';

		$sibling.insertAdjacentElement( 'afterend', $newPromptFieldset );

		return $newPromptFieldset;
	}
} )();

/**
 * Feature-first refactor settings field:
 * @param {Object} $ jQuery object
 */
( function ( $ ) {
	$( function () {
		const providerSelectEl = $( 'select#provider' );

		providerSelectEl.on( 'change', function () {
			const providerId = $( this ).val();
			const providerRows = $( '.classifai-provider-field' );
			const providerClass = `.provider-scope-${ providerId }`;

			providerRows.addClass( 'hidden' );
			providerRows.find( ':input' ).prop( 'disabled', true );

			$( providerClass ).removeClass( 'hidden' );
			$( providerClass ).find( ':input' ).prop( 'disabled', false );
		} );

		// Trigger 'change' on page load.
		providerSelectEl.trigger( 'change' );
	} );
} )( jQuery );
