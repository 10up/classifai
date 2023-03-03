/* global ClassifAI */

( () => {
	/**
	 * Toggle HTML field visibility
	 *
	 * @param {HTMLElement} field   HTML element to toggle
	 * @param {any}         visible Null to auto toggle, true to show, false to hide
	 * @return {void}
	 */
	const toggleField = ( field, visible = null ) => {
		// Hide field
		if ( visible === false ) {
			field.classList.add( 'hidden' );
			return;
		}

		// Show field
		if ( visible === true ) {
			field.classList.remove( 'hidden' );
			return;
		}

		// Auto toggle
		if ( field.classList.contains( 'hidden' ) ) {
			field.classList.remove( 'hidden' );
		} else {
			field.classList.add( 'hidden' );
		}
	};

	/**
	 * Toggle the Watson API credential fields
	 */
	const watsonCreds = () => {
		const $watsonCredToggle = document.getElementById(
			'classifai-waston-cred-toggle'
		);
		const $userField = document.getElementById(
			'classifai-settings-watson_username'
		);

		if ( $watsonCredToggle === null || $userField === null ) return;

		const $userFieldWrapper = $userField.closest( 'tr' );
		const [ $passwordFieldTitle ] = document
			.getElementById( 'classifai-settings-watson_password' )
			.closest( 'tr' )
			.getElementsByTagName( 'label' );

		$watsonCredToggle.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			toggleField( $userFieldWrapper );

			if ( $userFieldWrapper.classList.contains( 'hidden' ) ) {
				$watsonCredToggle.innerText = ClassifAI.use_password;
				$passwordFieldTitle.innerText = ClassifAI.api_key;
				$userField.value = 'apikey';
				return;
			}

			$watsonCredToggle.innerText = ClassifAI.use_key;
			$passwordFieldTitle.innerText = ClassifAI.api_password;
		} );
	};

	/**
	 * Toggle the Disallowed Tags field based on chosen restriction type
	 */
	const disallowedTags = () => {
		const $disallowedTagsSelect = document.querySelector(
			'select[name="classifai_computer_vision[filter_tags_type]'
		);
		const $allowedTagsField = document.querySelector(
			'.classifai-allowed-tags'
		);
		const $disabledTagsField = document.querySelector(
			'.classifai-disabled-tags'
		);

		// If fields aren't available, bail.
		if (
			! $disallowedTagsSelect ||
			! $allowedTagsField ||
			! $disabledTagsField
		) {
			return;
		}

		$disallowedTagsSelect.addEventListener( 'change', ( e ) => {
			const { value } = e.target.options[ e.target.selectedIndex ];

			toggleField( $allowedTagsField, 'allowed' === value );
			toggleField( $disabledTagsField, 'disabled' === value );

			e.target.dispatchEvent( new Event( 'filteredTagsTypeChanged' ) );
		} );
	};

	watsonCreds();
	disallowedTags();
} )();
