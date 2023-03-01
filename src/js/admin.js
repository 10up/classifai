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
		const $disallowedTagsToggles = Array.from(
			document.querySelectorAll(
				'input[name="classifai_settings[tag_restrict_type]'
			)
		);
		const $disallowedTagsField = document.querySelector(
			'.classifai-disallowed-tags'
		);

		if ( ! $disallowedTagsToggles.length || ! $disallowedTagsField ) return;

		$disallowedTagsToggles.forEach( ( toggle ) => {
			toggle.addEventListener( 'change', ( e ) => {
				toggleField(
					$disallowedTagsField,
					'disallow' === e.target.value
				);
			} );
		} );
	};

	watsonCreds();
	disallowedTags();
} )();
